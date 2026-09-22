<?php

declare(strict_types=1);

namespace App\Notifications;

use Filament\Notifications\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Feeds the Filament bell in both panels. */
final class GenericDatabaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $url = null,
        public readonly string $icon = 'heroicon-o-bell',
        public readonly string $color = 'primary',
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon)
            ->iconColor($this->color)
            ->when($this->url !== null, fn ($n) => $n->actions([
                Action::make('view')->label('View')->url($this->url)->markAsRead(),
            ]))
            ->getDatabaseMessage();
    }
}
