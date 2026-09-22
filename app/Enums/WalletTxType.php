<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletTxType: string
{
    case Deposit = 'deposit';
    case OrderPayment = 'order_payment';
    case OrderRefund = 'order_refund';
    case InvoicePayment = 'invoice_payment';
    case ReferralBonus = 'referral_bonus';
    case Withdrawal = 'withdrawal';
    case WithdrawalReversal = 'withdrawal_reversal';
    case AdminCredit = 'admin_credit';
    case AdminDebit = 'admin_debit';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::OrderPayment => 'Order payment',
            self::OrderRefund => 'Order refund',
            self::InvoicePayment => 'Invoice payment',
            self::ReferralBonus => 'Referral bonus',
            self::Withdrawal => 'Withdrawal',
            self::WithdrawalReversal => 'Withdrawal reversed',
            self::AdminCredit => 'Admin credit',
            self::AdminDebit => 'Admin debit',
        };
    }

    public function color(): string
    {
        return $this->isCredit() ? 'success' : 'danger';
    }

    public function icon(): string
    {
        return $this->isCredit() ? 'heroicon-o-arrow-down-left' : 'heroicon-o-arrow-up-right';
    }

    /** Whether this type adds to the balance. The ledger row's sign is the source of truth. */
    public function isCredit(): bool
    {
        return in_array($this, [
            self::Deposit,
            self::OrderRefund,
            self::ReferralBonus,
            self::WithdrawalReversal,
            self::AdminCredit,
        ], true);
    }
}
