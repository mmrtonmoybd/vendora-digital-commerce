<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MessageAudience;
use App\Enums\UserStatus;
use App\Models\AdminMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\AdminMessageNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Broadcasts a message to a resolved audience.
 *
 * The audience is resolved in chunks and the pivot rows are inserted in bulk, so sending to
 * every customer does not hold a connection open building one giant array in memory.
 */
final class AdminMessageService
{
    /** @return Builder<User> */
    public function recipientQuery(AdminMessage $message): Builder
    {
        $meta = $message->audience_meta ?? [];

        $query = User::query()->where('status', UserStatus::Active->value);

        return match ($message->audience) {
            MessageAudience::All => $query,
            MessageAudience::SelectedUsers => $query->whereIn('id', $meta['user_ids'] ?? []),
            MessageAudience::BuyersOfProduct => $query->whereIn('id', $this->buyerIdsForProducts($meta['product_ids'] ?? [])),
            MessageAudience::BuyersOfCategory => $query->whereIn('id', $this->buyerIdsForCategories($meta['category_ids'] ?? [])),
        };
    }

    public function previewCount(AdminMessage $message): int
    {
        return $this->recipientQuery($message)->count();
    }

    public function send(AdminMessage $message): int
    {
        if ($message->isSent()) {
            return $message->recipient_count;
        }

        $total = 0;

        $this->recipientQuery($message)->chunkById(500, function ($users) use ($message, &$total): void {
            $now = now();

            $rows = $users->map(fn (User $user): array => [
                'admin_message_id' => $message->getKey(),
                'user_id' => $user->getKey(),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('admin_message_user')->insertOrIgnore($rows);

            Notification::send($users, new AdminMessageNotification($message));

            $total += count($rows);
        });

        $message->forceFill([
            'sent_at' => now(),
            'recipient_count' => $total,
        ])->save();

        return $total;
    }

    public function markRead(AdminMessage $message, User $user): void
    {
        $message->users()->updateExistingPivot($user->getKey(), ['read_at' => now()]);
    }

    /** @param array<int, int> $productIds */
    private function buyerIdsForProducts(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        return Order::query()
            ->paid()
            ->whereNotNull('user_id')
            ->whereHas('items', fn (Builder $q) => $q->whereIn('product_id', $productIds))
            ->distinct()
            ->pluck('user_id')
            ->all();
    }

    /** @param array<int, int> $categoryIds */
    private function buyerIdsForCategories(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $productIds = Product::whereIn('category_id', $categoryIds)->pluck('id')->all();

        return $this->buyerIdsForProducts($productIds);
    }
}
