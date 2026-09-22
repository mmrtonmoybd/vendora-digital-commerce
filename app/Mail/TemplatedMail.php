<?php

declare(strict_types=1);

namespace App\Mail;

use App\Settings\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Every transactional email in the system, rendered from a row in email_templates.
 *
 * Laravel's own Markdown mail theme does the styling; the only branding is the site name
 * and the button colour, so nothing here competes with the storefront design.
 */
final class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string|int|null>  $variables
     */
    public function __construct(
        public readonly string $subjectLine,
        public readonly string $bodyMarkdown,
        public readonly array $variables = [],
        public readonly ?string $actionUrl = null,
        public readonly ?string $actionText = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.templated',
            with: [
                'bodyMarkdown' => $this->bodyMarkdown,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
                'siteName' => app(GeneralSettings::class)->site_name,
            ],
        );
    }
}
