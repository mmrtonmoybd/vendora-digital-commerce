<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccessStatus;
use App\Enums\DepositStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Enums\WalletTxType;
use App\Models\Admin;
use App\Models\Coupon;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\OrderSource;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Orders, deposits and tickets spread over 90 days.
 *
 * Only runs when SEED_DEMO=true. It exists so the dashboards, reports and the access queue
 * have something to show on a fresh install; a real shop must never find invented orders in
 * its books.
 */
final class DemoActivitySeeder extends Seeder
{
    public function run(): void
    {
        $customers = $this->customers();
        $products = Product::with(['category', 'platform', 'fields'])->get();
        $methods = PaymentMethod::all();
        $sources = OrderSource::all();
        $wallet = app(WalletService::class);

        if ($products->isEmpty() || $methods->isEmpty()) {
            $this->command?->warn('Seed the catalogue first.');

            return;
        }

        // Give everyone a float to spend, through the ledger rather than by writing the
        // balance column, so the demo data reconciles like real data.
        foreach ($customers as $customer) {
            $wallet->credit(
                $customer,
                random_int(50, 400) * 100,
                WalletTxType::AdminCredit,
                null,
                "demo-float:{$customer->getKey()}",
                'Opening demo balance',
            );
        }

        $this->seedOrders($customers, $products, $methods, $sources);
        $this->seedDeposits($customers, $methods);
        $this->seedTickets($customers);

        $this->command?->info('Demo activity seeded.');
    }

    /** @return Collection<int, User> */
    private function customers(): Collection
    {
        $names = [
            'Moshiur Rahman' => 'moshiur@example.com',
            'Sadia Rahman' => 'sadia@example.com',
            'Tanvir Ahmed' => 'tanvir@example.com',
            'Nusrat Hossain' => 'nusrat@example.com',
            'Rafiq Islam' => 'rafiq@example.com',
        ];

        return collect($names)->map(fn (string $email, string $name): User => User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'phone' => '01'.random_int(300000000, 999999999),
            ],
        ))->values();
    }

    /**
     * @param  Collection<int, User>  $customers
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, PaymentMethod>  $methods
     * @param  Collection<int, OrderSource>  $sources
     */
    private function seedOrders(Collection $customers, Collection $products, Collection $methods, Collection $sources): void
    {
        $coupon = Coupon::first();
        $statuses = [
            [OrderStatus::Completed, OrderPaymentStatus::Paid, AccessStatus::Confirmed],
            [OrderStatus::Completed, OrderPaymentStatus::Paid, AccessStatus::Granted],
            [OrderStatus::Processing, OrderPaymentStatus::Paid, AccessStatus::InProgress],
            [OrderStatus::Pending, OrderPaymentStatus::Paid, AccessStatus::Awaiting],
            [OrderStatus::PendingPayment, OrderPaymentStatus::Submitted, AccessStatus::Awaiting],
            [OrderStatus::PendingPayment, OrderPaymentStatus::Unpaid, AccessStatus::Awaiting],
            [OrderStatus::Cancelled, OrderPaymentStatus::Unpaid, AccessStatus::Revoked],
            [OrderStatus::Refunded, OrderPaymentStatus::Refunded, AccessStatus::Revoked],
            [OrderStatus::Completed, OrderPaymentStatus::Paid, AccessStatus::ProblemReported],
        ];

        foreach (range(1, 60) as $index) {
            $placedAt = now()->subDays(random_int(0, 90))->subHours(random_int(0, 23));
            [$status, $paymentStatus, $accessStatus] = $statuses[array_rand($statuses)];

            $customer = $customers->random();
            $product = $products->random();
            $method = $methods->random();
            $quantity = random_int($product->min_qty, max($product->min_qty, min(3, $product->max_qty)));

            $subtotal = $product->price * $quantity;
            $discount = $coupon !== null && $index % 7 === 0 ? $coupon->discountFor($subtotal) : 0;
            $fee = $method->feeFor($subtotal - $discount);
            $total = max(0, $subtotal - $discount + $fee);

            $isPaid = in_array($paymentStatus, [OrderPaymentStatus::Paid, OrderPaymentStatus::Refunded], true);

            $order = Order::create([
                'order_number' => 'VD-'.$placedAt->format('ym').'-'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                'user_id' => $customer->getKey(),
                'delivery_email' => $customer->email,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'fee' => $fee,
                'total' => $total,
                'refunded_amount' => $paymentStatus === OrderPaymentStatus::Refunded ? $total : 0,
                'coupon_id' => $discount > 0 ? $coupon?->getKey() : null,
                'payment_method_id' => $method->getKey(),
                'payment_status' => $paymentStatus,
                'status' => $status,
                'customer_phone' => $customer->phone,
                'manual_sender_number' => $method->isManual() ? $customer->phone : null,
                'manual_transaction_id' => $method->isManual() ? Str::upper(Str::random(10)) : null,
                'order_source_id' => $sources->isEmpty() ? null : $sources->random()->getKey(),
                'first_touch_source_id' => $sources->isEmpty() ? null : $sources->random()->getKey(),
                'paid_at' => $isPaid ? $placedAt->copy()->addMinutes(random_int(2, 40)) : null,
                'completed_at' => $status === OrderStatus::Completed ? $placedAt->copy()->addHours(1) : null,
                'assigned_admin_id' => $isPaid ? Admin::value('id') : null,
                'assigned_at' => $isPaid ? $placedAt->copy()->addMinutes(3) : null,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            $grantedAt = $accessStatus->isLive() ? $placedAt->copy()->addMinutes(random_int(5, 90)) : null;

            $order->items()->create([
                'product_id' => $product->getKey(),
                'product_snapshot' => $product->toSnapshot(),
                'unit_price' => $product->price,
                'quantity' => $quantity,
                'line_total' => $subtotal,
                'delivery_email' => $customer->email,
                'field_values' => $product->fields->mapWithKeys(fn ($field): array => [$field->key => 'demo'])->all(),
                'access_status' => $accessStatus,
                'granted_at' => $grantedAt,
                'granted_by' => $grantedAt ? Admin::value('id') : null,
                'confirmed_at' => $accessStatus === AccessStatus::Confirmed ? $grantedAt?->copy()->addHours(2) : null,
                'warranty_until' => $grantedAt?->copy()->addDays((int) ($product->warranty_days ?? 7)),
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            $order->histories()->create([
                'from_status' => null,
                'to_status' => $status,
                'note' => 'Seeded demo order.',
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);
        }
    }

    /**
     * @param  Collection<int, User>  $customers
     * @param  Collection<int, PaymentMethod>  $methods
     */
    private function seedDeposits(Collection $customers, Collection $methods): void
    {
        $manual = $methods->filter(fn (PaymentMethod $m): bool => $m->isManual());

        if ($manual->isEmpty()) {
            return;
        }

        $statuses = [DepositStatus::Completed, DepositStatus::Pending, DepositStatus::Rejected];

        foreach (range(1, 18) as $index) {
            $createdAt = now()->subDays(random_int(0, 60));
            $method = $manual->random();
            $customer = $customers->random();
            $amount = random_int(5, 50) * 10000;
            $status = $statuses[array_rand($statuses)];

            Deposit::create([
                'deposit_number' => 'DP-'.$createdAt->format('ym').'-'.Str::upper(Str::random(6)),
                'user_id' => $customer->getKey(),
                'payment_method_id' => $method->getKey(),
                'amount' => $amount,
                'fee' => $method->feeFor($amount),
                'payable' => $amount + $method->feeFor($amount),
                'sender_number' => $customer->phone,
                'transaction_id' => Str::upper(Str::random(10)),
                'status' => $status,
                'reject_reason' => $status === DepositStatus::Rejected ? 'Transaction ID not found on our statement.' : null,
                'processed_at' => $status === DepositStatus::Pending ? null : $createdAt->copy()->addHours(1),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    /** @param  Collection<int, User>  $customers */
    private function seedTickets(Collection $customers): void
    {
        $tickets = app(TicketService::class);

        $subjects = [
            'I did not get the invitation' => 'I paid an hour ago but nothing has arrived in my inbox.',
            'Wrong email on my order' => 'I typed my email wrong at checkout, can you change it?',
            'Access disappeared' => 'I had access yesterday and today it is gone.',
        ];

        foreach ($customers->take(3) as $index => $customer) {
            $subject = array_keys($subjects)[$index];

            $ticket = $tickets->open(
                $customer,
                Order::where('user_id', $customer->getKey())->first(),
                $subject,
                $subjects[$subject],
                [],
                ['normal', 'high', 'urgent'][$index],
            );

            if ($index === 0) {
                $tickets->reply($ticket, Admin::first(), 'Checking this now — I can see the payment, re-sending the invitation.');
            }
        }

        Ticket::query()->update(['created_at' => now()->subDays(random_int(1, 20))]);
    }
}
