<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use App\Services\PdfService;
use App\Settings\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
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
     * @param  string|null  $invoiceOrderNumber  attach this order's invoice, by number
     */
    public function __construct(
        public readonly string $subjectLine,
        public readonly string $bodyMarkdown,
        public readonly array $variables = [],
        public readonly ?string $actionUrl = null,
        public readonly ?string $actionText = null,
        public readonly ?string $invoiceOrderNumber = null,
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

    /**
     * The invoice, rendered at send time rather than at queue time.
     *
     * Carrying 30KB of PDF through the queue payload for every receipt is wasteful, and a
     * mail that sat in a failed-jobs table for a day would attach a stale document. The
     * order number is enough; the PDF is built when the message actually goes out.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->invoiceOrderNumber === null) {
            return [];
        }

        $order = Order::query()
            ->where('order_number', $this->invoiceOrderNumber)
            ->with(['items.product.platform', 'paymentMethod', 'user', 'coupon'])
            ->first();

        if ($order === null || ! $order->isPaid()) {
            return [];
        }

        return [
            Attachment::fromData(
                fn (): string => PdfService::render('pdf.invoice', ['order' => $order, 'print' => false]),
                "invoice-{$order->order_number}.pdf",
            )->withMime('application/pdf'),
        ];
    }
}
