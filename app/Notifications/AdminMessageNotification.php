<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Models\AdminMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class AdminMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly AdminMessage $message) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $this->message->send_email ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): TemplatedMail
    {
        return new TemplatedMail(
            subjectLine: $this->message->subject,
            bodyMarkdown: strip_tags($this->message->body, '<p><br><strong><em><ul><ol><li><a>'),
            actionUrl: url('/customer'),
            actionText: 'Open your account',
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title($this->message->subject)
            ->body(str($this->message->body)->stripTags()->limit(160)->value())
            ->icon('heroicon-o-megaphone')
            ->getDatabaseMessage();
    }
}
