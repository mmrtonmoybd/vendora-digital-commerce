<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\TemplatedMail;
use App\Models\Admin;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\GenericDatabaseNotification;
use App\Settings\TelegramSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * One place that knows how a business event reaches a human.
 *
 * Templates come from the database so support staff can reword an email without a deploy,
 * and every channel is optional and configured in the panel — nothing is hard-coded.
 */
final class NotificationDispatcher
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly TelegramSettings $telegram,
    ) {}

    /**
     * Send a templated email plus the in-panel bell notification.
     *
     * @param  array<string, string|int|null>  $variables
     */
    public function toCustomer(
        User $user,
        string $templateKey,
        array $variables = [],
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?string $icon = null,
    ): void {
        $template = $this->template($templateKey);

        if ($template === null) {
            Log::warning('notification.missing_template', ['key' => $templateKey]);

            return;
        }

        $variables = ['name' => $user->name, 'site_name' => config('app.name')] + $variables;

        $subject = $template->render('subject', $variables);
        $body = $template->render('body', $variables);

        Mail::to($user->email)->queue(new TemplatedMail($subject, $body, $variables, $actionUrl, $actionText));

        $user->notify(new GenericDatabaseNotification(
            title: $subject,
            body: str($body)->stripTags()->limit(160)->value(),
            url: $actionUrl,
            icon: $icon ?? 'heroicon-o-bell',
        ));
    }

    /**
     * The same templated email, to an address with no account behind it.
     *
     * Guest checkout is supported, so order and access mail has to reach people who will
     * never sign in. There is no bell notification to write — the signed link in the email
     * is their only way back to the order.
     *
     * @param  array<string, string|int|null>  $variables
     */
    public function guestEmail(
        string $email,
        string $templateKey,
        array $variables = [],
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?string $fallbackSubject = null,
        ?string $fallbackBody = null,
    ): void {
        $template = $this->template($templateKey);

        $subject = $template?->render('subject', $variables) ?? $fallbackSubject;
        $body = $template?->render('body', $variables) ?? $fallbackBody;

        if ($subject === null || $body === null) {
            Log::warning('notification.missing_template', ['key' => $templateKey]);

            return;
        }

        Mail::to($email)->queue(new TemplatedMail($subject, $body, $variables, $actionUrl, $actionText));
    }

    /** Bell notification only, no email. */
    public function notifyCustomer(User $user, string $title, string $body, ?string $url = null, ?string $icon = null): void
    {
        $user->notify(new GenericDatabaseNotification($title, $body, $url, $icon ?? 'heroicon-o-bell'));
    }

    /** Alert the back office: the Filament bell for every admin, plus Telegram if enabled. */
    public function toAdmins(string $title, string $body, ?string $url = null, ?string $icon = null, bool $telegram = true): void
    {
        $admins = Admin::query()->where('status', 'active')->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new GenericDatabaseNotification($title, $body, $url, $icon ?? 'heroicon-o-bell-alert'));
        }

        if ($telegram) {
            $this->telegram($title.' — '.$body.($url ? "\n".$url : ''));
        }
    }

    public function sms(User $user, string $message): void
    {
        $this->sms->send($user->phone, $message);
    }

    /** Fire-and-forget: a Telegram outage must never break an order. */
    public function telegram(string $message): void
    {
        if (! $this->telegram->enabled || $this->telegram->bot_token === '' || $this->telegram->chat_id === '') {
            return;
        }

        try {
            Http::timeout(8)->post("https://api.telegram.org/bot{$this->telegram->bot_token}/sendMessage", [
                'chat_id' => $this->telegram->chat_id,
                'text' => $message,
                'disable_web_page_preview' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('telegram.failed', ['message' => $e->getMessage()]);
        }
    }

    private function template(string $key): ?EmailTemplate
    {
        return Cache::remember(
            "email-template:{$key}",
            now()->addMinutes(30),
            fn (): ?EmailTemplate => EmailTemplate::where('key', $key)->where('is_active', true)->first(),
        );
    }
}
