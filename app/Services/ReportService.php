<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccessStatus;
use App\Enums\DepositStatus;
use App\Enums\OrderPaymentStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Every report figure in the system.
 *
 * All of it is aggregate SQL. A report that loops over rows in PHP is one that stops
 * working the month the shop gets busy, which is exactly the month somebody needs it.
 * Results are cached per filter for five minutes, because two people opening the same
 * report in the same meeting should not cost two full scans.
 */
final class ReportService
{
    private const CACHE_SECONDS = 300;

    /**
     * A product's name, preferring the live record and falling back to the snapshot frozen
     * on the order item, so a renamed or deleted product still reports what it sold.
     */
    private const PRODUCT_NAME_EXPRESSION = "COALESCE(products.name, JSON_UNQUOTE(JSON_EXTRACT(order_items.product_snapshot, '$.name')))";

    /** The payment states that count as money actually received. */
    private const PAID = [OrderPaymentStatus::Paid->value, OrderPaymentStatus::PartiallyRefunded->value];

    /**
     * Headline sales figures for a period.
     *
     * @return array<string, int|float>
     */
    public function salesSummary(Carbon $from, Carbon $to): array
    {
        return $this->remember('sales-summary', $from, $to, function () use ($from, $to): array {
            $row = (array) DB::table('orders')
                ->whereIn('payment_status', self::PAID)
                ->whereBetween('paid_at', [$from, $to])
                ->selectRaw('
                    COUNT(*) as orders,
                    COALESCE(SUM(subtotal), 0) as gross,
                    COALESCE(SUM(discount), 0) as discounts,
                    COALESCE(SUM(fee), 0) as fees,
                    COALESCE(SUM(refunded_amount), 0) as refunds,
                    COALESCE(SUM(total - refunded_amount), 0) as net
                ')
                ->first();

            $orders = (int) ($row['orders'] ?? 0);

            return [
                'orders' => $orders,
                'gross' => (int) ($row['gross'] ?? 0),
                'discounts' => (int) ($row['discounts'] ?? 0),
                'fees' => (int) ($row['fees'] ?? 0),
                'refunds' => (int) ($row['refunds'] ?? 0),
                'net' => (int) ($row['net'] ?? 0),
                // Integer division would quietly lose paisa on the average order value.
                'average_order' => $orders > 0 ? (int) round(((int) $row['net']) / $orders) : 0,
            ];
        });
    }

    /**
     * Revenue and orders bucketed by day or month.
     *
     * @return Collection<int, object{bucket: string, revenue: int, orders: int}>
     */
    public function salesOverTime(Carbon $from, Carbon $to, bool $monthly = false): Collection
    {
        return $this->remember('sales-over-time:'.($monthly ? 'm' : 'd'), $from, $to, function () use ($from, $to, $monthly): Collection {
            $format = $monthly ? '%Y-%m' : '%Y-%m-%d';

            return DB::table('orders')
                ->whereIn('payment_status', self::PAID)
                ->whereBetween('paid_at', [$from, $to])
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get([
                    DB::raw("DATE_FORMAT(paid_at, '{$format}') as bucket"),
                    DB::raw('SUM(total - refunded_amount) as revenue'),
                    DB::raw('COUNT(*) as orders'),
                ]);
        });
    }

    /**
     * Per-product sales, best first.
     *
     * Reads the order items rather than the products table, so a product that has since
     * been renamed or deleted still shows what it sold.
     *
     * @return Collection<int, object>
     */
    public function productPerformance(Carbon $from, Carbon $to, int $limit = 50): Collection
    {
        return $this->remember("product-performance:{$limit}", $from, $to, fn (): Collection => DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('orders.payment_status', self::PAID)
            ->whereBetween('orders.paid_at', [$from, $to])
            // Grouped by the expression itself, not its alias: under ONLY_FULL_GROUP_BY
            // MySQL cannot prove product_snapshot is functionally dependent on the alias.
            ->groupBy(DB::raw(self::PRODUCT_NAME_EXPRESSION), 'products.id', 'products.stock')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get([
                DB::raw(self::PRODUCT_NAME_EXPRESSION.' as name'),
                'products.id as product_id',
                'products.stock as stock',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(order_items.line_total) as revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as orders'),
            ]));
    }

    /** @return Collection<int, object> */
    public function categoryPerformance(Carbon $from, Carbon $to): Collection
    {
        return $this->remember('category-performance', $from, $to, fn (): Collection => DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereIn('orders.payment_status', self::PAID)
            ->whereBetween('orders.paid_at', [$from, $to])
            ->groupBy('name')
            ->orderByDesc('revenue')
            ->get([
                DB::raw("COALESCE(categories.name, 'Uncategorised') as name"),
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(order_items.line_total) as revenue'),
            ]));
    }

    /** Money taken per payment method, and what share of it each one carries. */
    public function paymentMethodMix(Carbon $from, Carbon $to): Collection
    {
        return $this->remember('payment-mix', $from, $to, fn (): Collection => DB::table('orders')
            ->leftJoin('payment_methods', 'orders.payment_method_id', '=', 'payment_methods.id')
            ->whereIn('orders.payment_status', self::PAID)
            ->whereBetween('orders.paid_at', [$from, $to])
            ->groupBy('name')
            ->orderByDesc('revenue')
            ->get([
                DB::raw("COALESCE(payment_methods.name, 'Unknown') as name"),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(orders.total - orders.refunded_amount) as revenue'),
            ]));
    }

    /**
     * How the gateway itself is behaving.
     *
     * @return array<string, int|float>
     */
    public function gatewayHealth(Carbon $from, Carbon $to): array
    {
        return $this->remember('gateway-health', $from, $to, function () use ($from, $to): array {
            $row = (array) DB::table('payments')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw("
                    COUNT(*) as attempts,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                    AVG(CASE WHEN verified_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, verified_at) END) as avg_verify_seconds
                ")
                ->first();

            $attempts = (int) ($row['attempts'] ?? 0);
            $paid = (int) ($row['paid'] ?? 0);

            return [
                'attempts' => $attempts,
                'paid' => $paid,
                'failed' => (int) ($row['failed'] ?? 0),
                'cancelled' => (int) ($row['cancelled'] ?? 0),
                'expired' => (int) ($row['expired'] ?? 0),
                'success_rate' => $attempts > 0 ? round($paid / $attempts * 100, 1) : 0.0,
                'avg_verify_seconds' => (int) round((float) ($row['avg_verify_seconds'] ?? 0)),
            ];
        });
    }

    /**
     * How long fulfilment is actually taking, and how often it misses the target.
     *
     * p90 is computed in SQL by offsetting into the ordered set rather than pulling every
     * row back to sort in PHP.
     *
     * @return array<string, int|float>
     */
    public function fulfillmentSummary(Carbon $from, Carbon $to): array
    {
        return $this->remember('fulfilment', $from, $to, function () use ($from, $to): array {
            $base = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereNotNull('order_items.granted_at')
                ->whereNotNull('orders.paid_at')
                ->whereBetween('order_items.granted_at', [$from, $to]);

            $row = (array) (clone $base)
                ->selectRaw('
                    COUNT(*) as granted,
                    AVG(TIMESTAMPDIFF(MINUTE, orders.paid_at, order_items.granted_at)) as avg_minutes
                ')
                ->first();

            $granted = (int) ($row['granted'] ?? 0);

            $p90 = 0;

            if ($granted > 0) {
                $offset = (int) floor($granted * 0.9);
                $offset = min($offset, max(0, $granted - 1));

                $p90 = (int) ((clone $base)
                    ->orderBy('minutes')
                    ->offset($offset)
                    ->limit(1)
                    ->value(DB::raw('TIMESTAMPDIFF(MINUTE, orders.paid_at, order_items.granted_at) as minutes')) ?? 0);
            }

            $breaches = (int) (clone $base)
                ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
                ->whereRaw('TIMESTAMPDIFF(MINUTE, orders.paid_at, order_items.granted_at) > COALESCE(products.fulfillment_sla_minutes, 30)')
                ->count();

            $problems = (int) DB::table('access_problem_reports')
                ->whereBetween('created_at', [$from, $to])
                ->count();

            return [
                'granted' => $granted,
                'avg_minutes' => (int) round((float) ($row['avg_minutes'] ?? 0)),
                'p90_minutes' => $p90,
                'sla_breaches' => $breaches,
                'sla_breach_rate' => $granted > 0 ? round($breaches / $granted * 100, 1) : 0.0,
                'problem_reports' => $problems,
                'awaiting_now' => (int) DB::table('order_items')
                    ->whereIn('access_status', [AccessStatus::Awaiting->value, AccessStatus::InProgress->value])
                    ->count(),
            ];
        });
    }

    /** Grants per staff member, so workload is visible rather than assumed. */
    public function fulfillmentByAdmin(Carbon $from, Carbon $to): Collection
    {
        return $this->remember('fulfilment-by-admin', $from, $to, fn (): Collection => DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('admins', 'order_items.granted_by', '=', 'admins.id')
            ->whereNotNull('order_items.granted_at')
            ->whereBetween('order_items.granted_at', [$from, $to])
            ->groupBy('name')
            ->orderByDesc('granted')
            ->get([
                DB::raw("COALESCE(admins.name, 'System') as name"),
                DB::raw('COUNT(*) as granted'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, orders.paid_at, order_items.granted_at)) as avg_minutes'),
            ]));
    }

    /** Which platform's traffic turned into money. */
    public function sourcePerformance(Carbon $from, Carbon $to): Collection
    {
        return $this->remember('source-performance', $from, $to, fn (): Collection => DB::table('orders')
            ->leftJoin('order_sources', 'orders.order_source_id', '=', 'order_sources.id')
            ->whereIn('orders.payment_status', self::PAID)
            ->whereBetween('orders.paid_at', [$from, $to])
            ->groupBy('name')
            ->orderByDesc('revenue')
            ->get([
                DB::raw("COALESCE(order_sources.name, 'Direct') as name"),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(orders.total - orders.refunded_amount) as revenue'),
                DB::raw('AVG(orders.total - orders.refunded_amount) as average_order'),
            ]));
    }

    /**
     * Customers, and how many of them come back.
     *
     * @return array<string, int|float>
     */
    public function customerSummary(Carbon $from, Carbon $to): array
    {
        return $this->remember('customers', $from, $to, function () use ($from, $to): array {
            $new = (int) DB::table('users')->whereBetween('created_at', [$from, $to])->count();

            $buyers = DB::table('orders')
                ->whereIn('payment_status', self::PAID)
                ->whereBetween('paid_at', [$from, $to])
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->select('user_id', DB::raw('COUNT(*) as orders'));

            $counts = (array) DB::query()
                ->fromSub($buyers, 'b')
                ->selectRaw('COUNT(*) as buyers, SUM(CASE WHEN orders > 1 THEN 1 ELSE 0 END) as repeat_buyers')
                ->first();

            $totalBuyers = (int) ($counts['buyers'] ?? 0);

            return [
                'new_customers' => $new,
                'buyers' => $totalBuyers,
                'repeat_buyers' => (int) ($counts['repeat_buyers'] ?? 0),
                'repeat_rate' => $totalBuyers > 0 ? round(((int) $counts['repeat_buyers']) / $totalBuyers * 100, 1) : 0.0,
                'wallet_liability' => (int) DB::table('users')->sum('balance'),
            ];
        });
    }

    /** @return Collection<int, object> */
    public function topCustomers(Carbon $from, Carbon $to, int $limit = 20): Collection
    {
        return $this->remember("top-customers:{$limit}", $from, $to, fn (): Collection => DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereIn('orders.payment_status', self::PAID)
            ->whereBetween('orders.paid_at', [$from, $to])
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('spent')
            ->limit($limit)
            ->get([
                'users.name',
                'users.email',
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(orders.total - orders.refunded_amount) as spent'),
            ]));
    }

    /**
     * Deposits taken, and how long staff took to process them.
     *
     * @return array<string, int|float>
     */
    public function depositSummary(Carbon $from, Carbon $to): array
    {
        return $this->remember('deposits', $from, $to, function () use ($from, $to): array {
            $row = (array) DB::table('deposits')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = '".DepositStatus::Completed->value."' THEN amount ELSE 0 END) as completed_value,
                    SUM(CASE WHEN status = '".DepositStatus::Completed->value."' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = '".DepositStatus::Pending->value."' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = '".DepositStatus::Rejected->value."' THEN 1 ELSE 0 END) as rejected,
                    SUM(fee) as fees,
                    AVG(CASE WHEN processed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, processed_at) END) as avg_minutes
                ")
                ->first();

            return [
                'total' => (int) ($row['total'] ?? 0),
                'completed' => (int) ($row['completed'] ?? 0),
                'completed_value' => (int) ($row['completed_value'] ?? 0),
                'pending' => (int) ($row['pending'] ?? 0),
                'rejected' => (int) ($row['rejected'] ?? 0),
                'fees' => (int) ($row['fees'] ?? 0),
                'avg_minutes' => (int) round((float) ($row['avg_minutes'] ?? 0)),
            ];
        });
    }

    /** @return array<string, int> */
    public function couponUsage(Carbon $from, Carbon $to): Collection
    {
        return $this->remember('coupons', $from, $to, fn (): Collection => DB::table('coupon_usages')
            ->join('coupons', 'coupon_usages.coupon_id', '=', 'coupons.id')
            ->join('orders', 'coupon_usages.order_id', '=', 'orders.id')
            ->whereBetween('coupon_usages.created_at', [$from, $to])
            ->groupBy('coupons.code')
            ->orderByDesc('discount_given')
            ->get([
                'coupons.code',
                DB::raw('COUNT(*) as uses'),
                DB::raw('SUM(coupon_usages.discount) as discount_given'),
                DB::raw('SUM(orders.total) as revenue_attributed'),
            ]));
    }

    /** Cache per report per period. The key includes the dates, so a new range is a new key. */
    private function remember(string $key, Carbon $from, Carbon $to, \Closure $resolver): mixed
    {
        $signature = sprintf('reports:%s:%s:%s', $key, $from->timestamp, $to->timestamp);

        return Cache::remember($signature, now()->addSeconds(self::CACHE_SECONDS), $resolver);
    }
}
