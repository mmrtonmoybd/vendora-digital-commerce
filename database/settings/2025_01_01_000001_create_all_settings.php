<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Seeds every settings group with a working default.
 *
 * Credentials (shurjoPay, Google, SMTP, SMS, Telegram) start empty on purpose: they are
 * entered by the administrator in the panel, never carried in code or in .env.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Vendora');
        $this->migrator->add('general.tagline', 'Digital products, delivered in minutes.');
        $this->migrator->add('general.logo', null);
        $this->migrator->add('general.logo_footer', null);
        $this->migrator->add('general.favicon', null);
        $this->migrator->add('general.copyright_text', '© '.date('Y').' Vendora. All rights reserved.');
        $this->migrator->add('general.support_hours', 'Support 9AM – 2AM');

        $this->migrator->add('theme.brand_hex', '#dc2626');
        $this->migrator->add('theme.admin_primary_hex', '#dc2626');
        $this->migrator->add('theme.hero_autoplay_seconds', 5);
        $this->migrator->add('theme.home_products_per_section', 4);

        $this->migrator->add('contact.phone', '096 0000 0000');
        $this->migrator->add('contact.email', 'support@example.com');
        $this->migrator->add('contact.whatsapp', '');
        $this->migrator->add('contact.telegram', '');
        $this->migrator->add('contact.facebook', '');
        $this->migrator->add('contact.youtube', '');
        $this->migrator->add('contact.discord', '');
        $this->migrator->add('contact.address', 'Dhaka, Bangladesh');
        $this->migrator->add('contact.map_embed_url', '');
        $this->migrator->add('contact.floating_support_target', 'whatsapp');
        $this->migrator->add('contact.floating_support_message', 'Hi! I need help with an order.');

        $this->migrator->add('commerce.currency_symbol', '৳');
        $this->migrator->add('commerce.currency_code', 'BDT');
        $this->migrator->add('commerce.order_prefix', 'VD');
        $this->migrator->add('commerce.guest_checkout_enabled', true);
        $this->migrator->add('commerce.auto_cancel_unpaid_minutes', 120);
        $this->migrator->add('commerce.low_stock_threshold', 5);
        $this->migrator->add('commerce.max_cart_items', 20);
        $this->migrator->add('commerce.restore_stock_on_refund', true);

        $this->migrator->add('shurjopay.enabled', false);
        $this->migrator->add('shurjopay.mode', 'sandbox');
        $this->migrator->add('shurjopay.api_url', 'https://sandbox.shurjopayment.com');
        $this->migrator->add('shurjopay.username', '');
        $this->migrator->addEncrypted('shurjopay.password', '');
        $this->migrator->add('shurjopay.prefix', 'VD');
        $this->migrator->add('shurjopay.currency', 'BDT');
        $this->migrator->add('shurjopay.ssl_verify', true);
        $this->migrator->add('shurjopay.fee_bps', 0);
        $this->migrator->add('shurjopay.customer_city', 'Dhaka');
        $this->migrator->add('shurjopay.customer_postcode', '1000');
        $this->migrator->add('shurjopay.customer_country', 'Bangladesh');

        $this->migrator->add('affiliate.enabled', true);
        $this->migrator->add('affiliate.commission_type', 'percent');
        $this->migrator->add('affiliate.commission_value', 5);
        $this->migrator->add('affiliate.trigger', 'first_order');
        $this->migrator->add('affiliate.approval', 'auto_on_complete');
        $this->migrator->add('affiliate.min_withdrawal', 50000);
        $this->migrator->add('affiliate.cookie_days', 30);

        $this->migrator->add('auth.google_login_enabled', false);
        $this->migrator->add('auth.google_client_id', '');
        $this->migrator->addEncrypted('auth.google_client_secret', '');
        $this->migrator->add('auth.require_email_verification', true);
        $this->migrator->add('auth.registration_enabled', true);

        $this->migrator->add('mail.mailer', 'log');
        $this->migrator->add('mail.host', '');
        $this->migrator->add('mail.port', 587);
        $this->migrator->add('mail.username', '');
        $this->migrator->addEncrypted('mail.password', '');
        $this->migrator->add('mail.encryption', 'tls');
        $this->migrator->add('mail.from_address', 'no-reply@example.com');
        $this->migrator->add('mail.from_name', 'Vendora');

        $this->migrator->add('sms.enabled', false);
        $this->migrator->add('sms.endpoint', '');
        $this->migrator->add('sms.method', 'GET');
        $this->migrator->addEncrypted('sms.api_key', '');
        $this->migrator->add('sms.sender_id', '');
        $this->migrator->add('sms.params_template', '{"api_key":"{api_key}","to":"{to}","msg":"{message}"}');
        $this->migrator->add('sms.on_order_completed', false);
        $this->migrator->add('sms.on_deposit_approved', false);

        $this->migrator->add('telegram.enabled', false);
        $this->migrator->addEncrypted('telegram.bot_token', '');
        $this->migrator->add('telegram.chat_id', '');
        $this->migrator->add('telegram.on_new_order', true);
        $this->migrator->add('telegram.on_payment_proof', true);
        $this->migrator->add('telegram.on_new_deposit', true);
        $this->migrator->add('telegram.on_new_ticket', true);
        $this->migrator->add('telegram.on_new_topup', false);

        $this->migrator->add('seo.default_title', 'Buy digital products instantly');
        $this->migrator->add('seo.default_description', 'Buy digital products in Bangladesh with instant delivery.');
        $this->migrator->add('seo.og_image', null);
        $this->migrator->add('seo.ga4_id', '');
        $this->migrator->add('seo.meta_pixel_id', '');
        $this->migrator->add('seo.custom_head_html', '');
        $this->migrator->add('seo.custom_body_html', '');
        $this->migrator->add('seo.robots_txt', "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /customer\n");

        $this->migrator->add('security.admin_ip_allowlist', '');
        $this->migrator->add('security.force_two_factor_for_super_admin', false);
        $this->migrator->add('security.admin_session_timeout_minutes', 240);
        $this->migrator->add('security.daily_summary_email_enabled', false);
    }
};
