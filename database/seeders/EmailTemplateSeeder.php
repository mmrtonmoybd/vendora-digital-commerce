<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * The transactional emails, editable in the admin panel.
 *
 * Bodies are Markdown; {{ variable }} placeholders are substituted at send time and the
 * available names are stored alongside each template so the editor can show them.
 */
final class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $key => $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'available_variables' => $template['variables'],
                    'is_active' => true,
                ],
            );
        }
    }

    /** @return array<string, array{name: string, subject: string, body: string, variables: array<int, string>}> */
    private function templates(): array
    {
        $orderVars = ['name', 'order_number', 'amount', 'items', 'delivery_email', 'url'];

        return [
            'order_placed' => [
                'name' => 'Order placed',
                'subject' => 'We received your order {{ order_number }}',
                'body' => "Hi {{ name }},\n\nThanks for your order **{{ order_number }}**.\n\n- Items: {{ items }}\n- Total: {{ amount }}\n- Access email: {{ delivery_email }}\n\nWe will email you again the moment your payment is confirmed.",
                'variables' => $orderVars,
            ],
            'order_proof_submitted' => [
                'name' => 'Payment proof received',
                'subject' => 'We are checking your payment for {{ order_number }}',
                'body' => "Hi {{ name }},\n\nWe have your transaction details for order **{{ order_number }}** and our team is verifying the payment now. This usually takes a few minutes during support hours.",
                'variables' => $orderVars,
            ],
            'order_paid_awaiting_access' => [
                'name' => 'Payment received, access on the way',
                'subject' => 'Payment received for {{ order_number }}',
                'body' => "Hi {{ name }},\n\nYour payment of **{{ amount }}** is confirmed.\n\nWe will give {{ access_role }} access to **{{ delivery_email }}** within about {{ sla }}. You will get another email as soon as the invitation is sent.",
                'variables' => [...$orderVars, 'access_role', 'sla', 'product_name'],
            ],
            'access_granted' => [
                'name' => 'Access granted',
                'subject' => 'Access granted for {{ product_name }}',
                'body' => "Hi {{ name }},\n\nAccess to **{{ product_name }}** has been granted to **{{ delivery_email }}**.\n\nCheck that inbox for an invitation from {{ platform }} and accept it.\n\n{{ invite_instructions }}\n\n{{ admin_delivery_note }}",
                'variables' => ['name', 'product_name', 'delivery_email', 'platform', 'access_role', 'invite_instructions', 'admin_delivery_note', 'order_number'],
            ],
            'access_re_invited' => [
                'name' => 'Invitation re-sent',
                'subject' => 'We sent your invitation again',
                'body' => "Hi {{ name }},\n\nWe have sent the invitation to **{{ delivery_email }}** again. Please check your inbox and your spam folder.",
                'variables' => ['name', 'delivery_email', 'product_name'],
            ],
            'access_replaced' => [
                'name' => 'Access replaced',
                'subject' => 'A replacement has been shared with you',
                'body' => "Hi {{ name }},\n\nWe have shared a replacement for **{{ product_name }}** with **{{ delivery_email }}**. Accept the new invitation to continue.",
                'variables' => ['name', 'delivery_email', 'product_name'],
            ],
            'access_problem_received' => [
                'name' => 'Problem report received',
                'subject' => 'We are looking into your access problem',
                'body' => "Hi {{ name }},\n\nThanks for letting us know. Our team is looking at order **{{ order_number }}** now and will get back to you shortly.",
                'variables' => $orderVars,
            ],
            'access_email_changed' => [
                'name' => 'Delivery email changed',
                'subject' => 'Your delivery email was updated',
                'body' => "Hi {{ name }},\n\nThe access email on order **{{ order_number }}** is now **{{ delivery_email }}**. If this was not you, reply to this email straight away.",
                'variables' => $orderVars,
            ],
            'order_completed' => [
                'name' => 'Order completed',
                'subject' => 'Your order {{ order_number }} is complete',
                'body' => "Hi {{ name }},\n\nOrder **{{ order_number }}** is complete.\n\n{{ delivery_note }}\n\nIf anything is not working, reply here or open a ticket from your account.",
                'variables' => [...$orderVars, 'delivery_note'],
            ],
            'order_cancelled' => [
                'name' => 'Order cancelled',
                'subject' => 'Order {{ order_number }} was cancelled',
                'body' => "Hi {{ name }},\n\nOrder **{{ order_number }}** has been cancelled. If you were charged, the amount has been returned to your wallet.",
                'variables' => $orderVars,
            ],
            'order_refunded' => [
                'name' => 'Order refunded',
                'subject' => 'Refund issued for {{ order_number }}',
                'body' => "Hi {{ name }},\n\nWe have refunded **{{ amount }}** for order **{{ order_number }}** to your wallet. You can spend it on your next order or request a withdrawal.",
                'variables' => $orderVars,
            ],
            'deposit_submitted' => [
                'name' => 'Deposit submitted',
                'subject' => 'We received your deposit {{ deposit_number }}',
                'body' => "Hi {{ name }},\n\nWe have your deposit of **{{ amount }}** via {{ method }} and are verifying it now.",
                'variables' => ['name', 'deposit_number', 'amount', 'method'],
            ],
            'deposit_approved' => [
                'name' => 'Deposit approved',
                'subject' => 'Your wallet has been topped up',
                'body' => "Hi {{ name }},\n\n**{{ amount }}** has been added to your wallet. Your balance is now **{{ balance }}**.",
                'variables' => ['name', 'deposit_number', 'amount', 'balance'],
            ],
            'deposit_rejected' => [
                'name' => 'Deposit rejected',
                'subject' => 'We could not verify your deposit',
                'body' => "Hi {{ name }},\n\nWe could not verify deposit **{{ deposit_number }}**.\n\nReason: {{ reason }}\n\nReply to this email with a screenshot and we will take another look.",
                'variables' => ['name', 'deposit_number', 'amount', 'reason'],
            ],
            'invoice_created' => [
                'name' => 'Invoice created',
                'subject' => 'You have a new invoice: {{ invoice_number }}',
                'body' => "Hi {{ name }},\n\nA new invoice for **{{ product_name }}** is waiting for you.\n\n- Amount: {{ amount }}\n- Due: {{ due }}",
                'variables' => ['name', 'invoice_number', 'product_name', 'amount', 'due'],
            ],
            'invoice_paid' => [
                'name' => 'Invoice paid',
                'subject' => 'Payment received for {{ invoice_number }}',
                'body' => "Hi {{ name }},\n\nThanks — we have received **{{ amount }}** for **{{ product_name }}**. Your order is now in our fulfilment queue.",
                'variables' => ['name', 'invoice_number', 'product_name', 'amount'],
            ],
            'ticket_replied' => [
                'name' => 'Ticket reply',
                'subject' => 'Reply on ticket {{ ticket_number }}',
                'body' => "Hi {{ name }},\n\nOur team replied to **{{ subject }}**:\n\n> {{ message }}",
                'variables' => ['name', 'ticket_number', 'subject', 'message'],
            ],
            'withdrawal_processed' => [
                'name' => 'Withdrawal processed',
                'subject' => 'Update on withdrawal {{ request_number }}',
                'body' => "Hi {{ name }},\n\nYour withdrawal of **{{ amount }}** is now **{{ status }}**.\n\n{{ note }}",
                'variables' => ['name', 'request_number', 'amount', 'status', 'note'],
            ],
            'admin_message' => [
                'name' => 'Message from the team',
                'subject' => '{{ subject }}',
                'body' => "Hi {{ name }},\n\n{{ message }}",
                'variables' => ['name', 'subject', 'message'],
            ],
            'welcome' => [
                'name' => 'Welcome',
                'subject' => 'Welcome aboard',
                'body' => "Hi {{ name }},\n\nYour account is ready. You now have a wallet, order history and a referral code: **{{ referral_code }}**.\n\nShare your referral link and earn wallet credit on every friend's order.",
                'variables' => ['name', 'referral_code'],
            ],
        ];
    }
}
