<?php

declare(strict_types=1);

namespace App\Services;

use App\Settings\SmsSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * A provider-agnostic SMS sender.
 *
 * The admin pastes their gateway's URL and a JSON parameter template; {to}, {message},
 * {api_key} and {sender_id} are substituted at send time. No provider name appears in the
 * codebase, so switching vendors is a settings change.
 */
final class SmsService
{
    public function __construct(private readonly SmsSettings $settings) {}

    public function send(?string $to, string $message): bool
    {
        if (! $this->settings->enabled || ! $to || $this->settings->endpoint === '') {
            return false;
        }

        $params = $this->buildParams($to, $message);

        try {
            $response = $this->settings->method === 'POST'
                ? Http::timeout(10)->asForm()->post($this->settings->endpoint, $params)
                : Http::timeout(10)->get($this->settings->endpoint, $params);

            if (! $response->successful()) {
                Log::warning('sms.failed', ['status' => $response->status()]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('sms.exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    /** @return array<string, string> */
    private function buildParams(string $to, string $message): array
    {
        $template = json_decode($this->settings->params_template, true);

        if (! is_array($template)) {
            return ['to' => $to, 'message' => $message];
        }

        $replacements = [
            '{to}' => $to,
            '{message}' => $message,
            '{api_key}' => $this->settings->api_key,
            '{sender_id}' => $this->settings->sender_id,
        ];

        return array_map(
            fn ($value): string => strtr((string) $value, $replacements),
            $template,
        );
    }
}
