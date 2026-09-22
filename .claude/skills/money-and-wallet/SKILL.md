---
name: money-and-wallet
description: Use whenever code touches prices, totals, wallet balance, deposits, refunds, commissions, invoices or payments.
---

# Money and wallet rules

- Money is integer paisa (`unsignedBigInteger` / `int`). Use `App\Support\Money` for math and formatting.
- Never mutate `users.balance` directly. Only `WalletService::credit()`/`debit()` may change it, inside
  `DB::transaction` with `lockForUpdate` on the user row, writing a `wallet_transactions` ledger row
  with `balance_after` and an `idempotency_key` (unique).
- Every state change on orders/deposits/payments goes through its Service class, never inline in
  controllers, Livewire components or Filament actions.
- Payment success is decided only by a server-side verification call to the gateway, never by
  return-URL query params. Verified amount and currency must equal the expected amount.
- Every money-changing admin action: confirmation modal, reason/note, activity log, customer notification.
- Percentages are basis points (`fee_bps`), never floats. `Money::percentOfBps()` rounds half up.
