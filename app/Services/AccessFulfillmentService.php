<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccessEventType;
use App\Enums\AccessStatus;
use App\Enums\OrderStatus;
use App\Enums\ProblemReason;
use App\Enums\ProblemReportStatus;
use App\Exceptions\DomainException;
use App\Models\AccessEvent;
use App\Models\AccessProblemReport;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Settings\FulfillmentSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Delivery in this business is a person granting access on a platform, not a file download.
 *
 * Everything here turns on one field: the delivery email the customer typed at checkout is
 * the address that will be invited to the Business Manager. That is why it can be corrected
 * by the customer while nothing has happened yet, is locked to staff once work has started,
 * and every change is written to the item's own timeline.
 */
final class AccessFulfillmentService
{
    public function __construct(
        private readonly FulfillmentSettings $settings,
        private readonly OrderService $orders,
        private readonly TicketService $tickets,
        private readonly NotificationDispatcher $notifications,
    ) {}

    /**
     * A paid order enters the queue.
     *
     * Idempotent on purpose: the return URL, the IPN and the reconcile job can all report
     * the same payment, and none of them may assign the order twice or send a second alert.
     */
    public function onOrderPaid(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            /** @var Order $locked */
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->assigned_at !== null) {
                return;
            }

            foreach ($locked->items as $item) {
                if ($item->access_status !== AccessStatus::Awaiting) {
                    continue;
                }

                $item->forceFill(['access_status' => AccessStatus::Awaiting])->save();
            }

            $admin = $this->settings->auto_assign_enabled ? $this->nextAdmin() : null;

            $locked->forceFill([
                'assigned_admin_id' => $admin?->getKey(),
                'assigned_at' => now(),
            ])->save();

            if ($admin !== null) {
                foreach ($locked->items as $item) {
                    $this->event($item, AccessEventType::Assigned, "Assigned to {$admin->name}.", null);
                }
            }
        }, 3);

        $order->refresh();

        $this->notifications->toAdmins(
            'New order awaiting access',
            "{$order->order_number} — grant access to {$order->delivery_email}",
            url('/admin/access-queue'),
            'heroicon-o-key',
        );

        $this->notifyCustomerAwaiting($order);
    }

    /** Staff picks the work up. */
    public function start(OrderItem $item, Admin $admin): OrderItem
    {
        if (! $item->access_status->isOpen()) {
            throw new DomainException('This item is not waiting for access.');
        }

        $item->forceFill(['access_status' => AccessStatus::InProgress])->save();
        $this->event($item, AccessEventType::Started, 'Fulfilment started.', $admin);

        $order = $item->order;

        if ($order !== null && $order->status === OrderStatus::Pending) {
            $this->orders->startProcessing($order, $admin);
        }

        return $item;
    }

    public function assign(Order $order, Admin $admin): Order
    {
        $order->forceFill(['assigned_admin_id' => $admin->getKey(), 'assigned_at' => now()])->save();

        foreach ($order->items as $item) {
            $this->event($item, AccessEventType::Assigned, "Assigned to {$admin->name}.", $admin);
        }

        return $order;
    }

    /**
     * Access has been given on the platform.
     *
     * `$reference` records which Business Manager or ad account was shared. It is staff-only:
     * it names an internal asset and is never rendered in the portal or in an email.
     */
    public function grant(
        OrderItem $item,
        Admin $admin,
        ?string $customerNote = null,
        ?string $reference = null,
        bool $sendEmail = true,
    ): OrderItem {
        if ($item->access_status->isLive() && $item->access_status !== AccessStatus::ProblemReported) {
            throw new DomainException('Access has already been granted for this item.');
        }

        $warrantyDays = (int) ($item->product_snapshot['warranty_days'] ?? $item->product?->warranty_days ?? 0);

        DB::transaction(function () use ($item, $admin, $customerNote, $reference, $warrantyDays): void {
            $item->forceFill([
                'access_status' => AccessStatus::Granted,
                'granted_at' => now(),
                'granted_by' => $admin->getKey(),
                'access_reference' => $reference ?: $item->access_reference,
                'admin_delivery_note' => $customerNote ?: $item->admin_delivery_note,
                'warranty_until' => $warrantyDays > 0 ? now()->addDays($warrantyDays) : null,
            ])->save();

            $this->event($item, AccessEventType::Granted, $customerNote ?: 'Access granted.', $admin, [
                'delivery_email' => $item->delivery_email,
            ]);
        });

        $this->completeOrderIfFullyGranted($item->order, $admin);

        if ($sendEmail) {
            $this->notifyAccessGranted($item);
        }

        return $item->refresh();
    }

    /** The admin sent the invitation again after the customer said it never arrived. */
    public function reInvite(OrderItem $item, Admin $admin, ?string $note = null): OrderItem
    {
        $item->forceFill([
            'access_status' => AccessStatus::ReGranted,
            'granted_at' => now(),
            'granted_by' => $admin->getKey(),
        ])->save();

        $this->event($item, AccessEventType::ReInvited, $note ?: 'Invitation sent again.', $admin);
        $this->resolveOpenReports($item, $admin, $note ?: 'Invitation sent again.');

        $this->notifyCustomer($item, 'access_re_invited', 'Invitation re-sent', "We have sent the invitation to {$item->delivery_email} again.");

        return $item;
    }

    /** Within warranty, the lost access is replaced with a different asset. */
    public function replace(OrderItem $item, Admin $admin, string $reference, ?string $note = null): OrderItem
    {
        $item->forceFill([
            'access_status' => AccessStatus::Replaced,
            'granted_at' => now(),
            'granted_by' => $admin->getKey(),
            'access_reference' => $reference,
            'admin_delivery_note' => $note ?: $item->admin_delivery_note,
        ])->save();

        $this->event($item, AccessEventType::Replaced, $note ?: 'Access replaced.', $admin);
        $this->resolveOpenReports($item, $admin, $note ?: 'Access replaced.');

        $this->notifyCustomer($item, 'access_replaced', 'Access replaced', "A replacement has been shared with {$item->delivery_email}.");

        return $item;
    }

    public function revoke(OrderItem $item, ?Admin $admin = null, ?string $reason = null): OrderItem
    {
        $item->forceFill(['access_status' => AccessStatus::Revoked])->save();
        $this->event($item, AccessEventType::Revoked, $reason ?: 'Access revoked.', $admin);

        return $item;
    }

    /** Every item on a refunded order loses its access. */
    public function revokeForOrder(Order $order, ?Admin $admin = null, ?string $reason = null): void
    {
        foreach ($order->items as $item) {
            if ($item->access_status !== AccessStatus::Revoked) {
                $this->revoke($item, $admin, $reason ?: 'Order refunded.');
            }
        }
    }

    /**
     * Change the address that will be invited.
     *
     * The customer may only do this while nothing has been granted yet. Afterwards an admin
     * must do it with a reason, because the invitation has already gone somewhere.
     */
    public function changeDeliveryEmail(OrderItem $item, string $email, Admin|User $actor, ?string $reason = null): OrderItem
    {
        $isCustomer = $actor instanceof User;

        if ($isCustomer && ! $item->access_status->allowsCustomerEmailChange()) {
            throw new DomainException('We have already started work on this order. Please open a ticket to change the email.');
        }

        if ($isCustomer && $item->order?->user_id !== $actor->getKey()) {
            throw new DomainException('That order does not belong to you.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Enter a valid delivery email.');
        }

        $old = $item->delivery_email;

        if ($old === $email) {
            return $item;
        }

        $item->forceFill(['delivery_email' => $email])->save();

        $this->event(
            $item,
            AccessEventType::EmailChanged,
            $reason ?: "Delivery email changed from {$old} to {$email}.",
            $actor,
            ['from' => $old, 'to' => $email],
        );

        $this->notifications->toAdmins(
            'Delivery email changed',
            "Order {$item->order?->order_number}: {$old} → {$email}",
            url('/admin/access-queue'),
            'heroicon-o-envelope',
        );

        return $item;
    }

    /** The customer says the invitation arrived and they accepted it. */
    public function confirm(OrderItem $item, ?User $actor = null): OrderItem
    {
        if (! $item->access_status->isLive()) {
            throw new DomainException('This item has no access to confirm yet.');
        }

        $item->forceFill([
            'access_status' => AccessStatus::Confirmed,
            'confirmed_at' => now(),
        ])->save();

        $this->event($item, AccessEventType::CustomerConfirmed, 'Customer confirmed access.', $actor);

        return $item;
    }

    /**
     * The customer reports that something is wrong.
     *
     * A report opens a support ticket as well, so the conversation happens in one place
     * instead of a queue nobody watches.
     */
    public function reportProblem(
        OrderItem $item,
        ProblemReason $reason,
        ?string $message,
        ?string $screenshotPath,
        ?User $user,
        ?string $guestEmail = null,
    ): AccessProblemReport {
        if ($reason->isWarrantyBound() && $item->warranty_until !== null && $item->warranty_until->isPast()) {
            throw new DomainException('The replacement period for this item ended on '.$item->warranty_until->toFormattedDateString().'. Please open a support ticket.');
        }

        return DB::transaction(function () use ($item, $reason, $message, $screenshotPath, $user, $guestEmail): AccessProblemReport {
            $ticket = null;

            if ($user !== null && $item->order !== null) {
                $ticket = $this->tickets->open(
                    $user,
                    $item->order,
                    "Access problem — {$item->name()}",
                    $reason->label().($message ? "\n\n".$message : ''),
                    $screenshotPath ? [$screenshotPath] : [],
                    'high',
                );
            }

            $report = AccessProblemReport::create([
                'order_item_id' => $item->getKey(),
                'user_id' => $user?->getKey(),
                'guest_email' => $user === null ? $guestEmail : null,
                'reason' => $reason,
                'message' => $message,
                'screenshot' => $screenshotPath,
                'status' => ProblemReportStatus::Open,
                'ticket_id' => $ticket?->getKey(),
            ]);

            $item->forceFill(['access_status' => AccessStatus::ProblemReported])->save();

            $this->event($item, AccessEventType::ProblemReported, $reason->label(), $user, ['message' => $message]);

            $this->notifications->toAdmins(
                'Access problem reported',
                "{$item->order?->order_number}: {$reason->label()}",
                url('/admin/access-problem-reports'),
                'heroicon-o-exclamation-triangle',
            );

            return $report;
        });
    }

    /** Customers may only ask so often; each request costs a staff member real time. */
    public function assertReInviteAllowed(OrderItem $item): void
    {
        $key = "reinvite:{$item->getKey()}:".now()->toDateString();
        $count = (int) Cache::get($key, 0);

        if ($count >= $this->settings->reinvite_daily_limit) {
            throw new DomainException('You have already asked for a re-invite today. Our team is on it.');
        }

        Cache::put($key, $count + 1, now()->endOfDay());
    }

    public function resolveReport(AccessProblemReport $report, Admin $admin, string $note, bool $resolved = true): AccessProblemReport
    {
        $report->forceFill([
            'status' => $resolved ? ProblemReportStatus::Resolved : ProblemReportStatus::Rejected,
            'resolution_note' => $note,
            'resolved_by' => $admin->getKey(),
            'resolved_at' => now(),
        ])->save();

        if ($report->user !== null) {
            $this->notifications->notifyCustomer(
                $report->user,
                $resolved ? 'Your access problem is resolved' : 'Update on your access report',
                $note,
                url('/customer'),
            );
        }

        return $report;
    }

    /**
     * Mark items confirmed when the customer has gone quiet.
     *
     * Most customers accept the invitation and never come back to tell us. Leaving those
     * items open forever would make the fulfilment report meaningless.
     */
    public function autoConfirmStale(): int
    {
        $cutoff = now()->subDays($this->settings->auto_confirm_days);

        $items = OrderItem::query()
            ->whereIn('access_status', [AccessStatus::Granted->value, AccessStatus::ReGranted->value, AccessStatus::Replaced->value])
            ->whereNotNull('granted_at')
            ->where('granted_at', '<=', $cutoff)
            ->get();

        foreach ($items as $item) {
            $item->forceFill([
                'access_status' => AccessStatus::Confirmed,
                'confirmed_at' => now(),
            ])->save();

            $this->event($item, AccessEventType::CustomerConfirmed, 'Auto-confirmed after '.$this->settings->auto_confirm_days.' days.', null);
        }

        return $items->count();
    }

    /**
     * Flag orders whose fulfilment clock has run out.
     *
     * The alert fires once per order: a queue that pings every five minutes is a queue
     * people mute.
     *
     * @return int number of orders newly flagged
     */
    public function flagSlaBreaches(): int
    {
        $orders = Order::query()
            ->awaitingAccess()
            ->where('sla_alerted', false)
            ->whereNotNull('paid_at')
            ->with('items')
            ->get()
            ->filter(fn (Order $order): bool => $order->items->contains(fn (OrderItem $item): bool => $item->isBreachingSla()));

        foreach ($orders as $order) {
            $order->forceFill(['sla_alerted' => true])->save();

            $this->notifications->toAdmins(
                'Access SLA breached',
                "{$order->order_number} has been waiting longer than its delivery target.",
                url('/admin/access-queue'),
                'heroicon-o-exclamation-triangle',
            );
        }

        return $orders->count();
    }

    /** The order is done only when every unit on it has been granted. */
    private function completeOrderIfFullyGranted(?Order $order, Admin $admin): void
    {
        if ($order === null) {
            return;
        }

        $order->load('items');

        $allGranted = $order->items->every(
            fn (OrderItem $item): bool => $item->access_status->isLive() || $item->access_status === AccessStatus::Revoked,
        );

        if (! $allGranted || $order->status === OrderStatus::Completed) {
            return;
        }

        $this->orders->complete($order, $order->delivery_note, $admin);
    }

    /** Round-robin across the staff who actually fulfil orders. */
    private function nextAdmin(): ?Admin
    {
        $admins = Admin::query()
            ->where('status', 'active')
            ->get()
            ->filter(fn (Admin $admin): bool => $admin->can('fulfill_orders') || $admin->isSuperAdmin())
            ->values();

        if ($admins->isEmpty()) {
            return null;
        }

        $index = (int) Cache::increment('fulfilment:round-robin');

        return $admins[$index % $admins->count()];
    }

    private function resolveOpenReports(OrderItem $item, Admin $admin, string $note): void
    {
        foreach ($item->problemReports()->open()->get() as $report) {
            $this->resolveReport($report, $admin, $note);
        }
    }

    private function notifyCustomerAwaiting(Order $order): void
    {
        $item = $order->items->first();

        if ($item === null) {
            return;
        }

        $variables = [
            'order_number' => $order->order_number,
            'delivery_email' => $order->delivery_email,
            'access_role' => $item->accessRole() ?: 'account',
            'sla' => $item->slaMinutes().' minutes',
            'product_name' => $item->name(),
        ];

        if ($order->user !== null) {
            $this->notifications->toCustomer(
                $order->user,
                'order_paid_awaiting_access',
                $variables,
                url('/customer/orders'),
                'View your order',
                'heroicon-o-clock',
                // The receipt rides along with the payment confirmation, so nobody has to
                // go looking for it.
                invoiceOrderNumber: $order->order_number,
            );
        }
    }

    private function notifyAccessGranted(OrderItem $item): void
    {
        $order = $item->order;

        if ($order === null) {
            return;
        }

        $variables = [
            'order_number' => $order->order_number,
            'product_name' => $item->name(),
            'delivery_email' => $item->delivery_email,
            'platform' => $item->platformName() ?: 'the platform',
            'access_role' => $item->accessRole() ?: 'account',
            'invite_instructions' => strip_tags((string) $item->inviteInstructions()),
            'admin_delivery_note' => strip_tags((string) $item->admin_delivery_note),
        ];

        if ($order->user !== null) {
            $this->notifications->toCustomer(
                $order->user,
                'access_granted',
                $variables,
                route('order.pay', $order),
                'Open your order',
                'heroicon-o-key',
            );
        }
    }

    private function notifyCustomer(OrderItem $item, string $templateKey, string $title, string $body): void
    {
        $user = $item->order?->user;

        if ($user === null) {
            return;
        }

        $this->notifications->notifyCustomer($user, $title, $body, route('order.pay', $item->order));
    }

    /** @param array<string, mixed> $meta */
    private function event(OrderItem $item, AccessEventType $type, ?string $note, ?Model $actor, array $meta = []): AccessEvent
    {
        return AccessEvent::create([
            'order_item_id' => $item->getKey(),
            'type' => $type,
            'note' => $note,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'meta' => $meta ?: null,
        ]);
    }
}
