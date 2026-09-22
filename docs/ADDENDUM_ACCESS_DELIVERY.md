# ADDENDUM — Access-Grant Delivery (no downloads)

Nothing is downloaded. Every product (TikTok Business Manager / agency account, Facebook BM,
ad account and similar) is delivered by **an admin granting access on the platform to the
email the customer gives**.

1. Customer clicks **Buy Now**, enters their **email address** — access is given to this email —
   chooses a quantity and a payment method, then checks out.
2. Customer pays (wallet / bKash / Nagad / Rocket / shurjoPay).
3. Order enters the admin's **"Awaiting access"** queue.
4. Admin goes to the platform (TikTok / Meta / Google) and **invites that email** to the
   Business Manager / account.
5. Admin clicks **"Mark access granted"**. The order becomes **Completed**.
6. Customer gets an email and a portal notification: *"Access has been granted to
   you@example.com. Check your inbox for the invitation and accept it."*
7. Customer confirms receipt, or reports a problem, which asks the admin to re-invite.

`delivery_email` is **the email that receives platform access**. It is the most important
field on the order.

## Not built, by decision

No product_items / code pools, no product files, no download routes, tokens, limits or
expiry; no "reveal code" feature and no code import. `products.stock` stays the number the
admin types, and the storefront's "7 pcs" pill and "Out of Stock" button work unchanged.

## Access lifecycle

| Step | Trigger | Order status | Item access_status |
|---|---|---|---|
| Paid | `OrderPaid` | `pending` | `awaiting` |
| Admin starts | "Start" / assign to me | `processing` | `in_progress` |
| Access given | "Mark access granted" | `completed` when **all** items granted | `granted` |
| Customer confirms | "I received access" | `completed` | `confirmed` |
| Problem | Customer reports | stays `completed`; admin alerted | `problem_reported` |
| Re-invite | Admin "Re-invite done" | `completed` | `re_granted` |
| Replacement (in warranty) | Admin "Replace" | `completed` | `replaced` |
| Refund | Existing refund flow | `refunded` | `revoked` |

Rules:

- On `OrderPaid`, auto-assign round-robin among admins holding `fulfill_orders`, and notify
  them. Idempotent: return + IPN must not create two assignments or two notifications.
- The customer may edit `delivery_email` **only while `awaiting`**; the change is highlighted
  for the admin and written as an `email_changed` event. Later, only an admin may change it,
  with a reason.
- If the customer neither confirms nor reports within `auto_confirm_days` (default 3), the
  system marks the item `confirmed`.
- "Access removed" may only be reported while `now() <= warranty_until`.
- A scheduled job every 5 minutes flags items `awaiting`/`in_progress` past
  `fulfillment_sla_minutes`; they turn red in the queue and raise one Telegram alert.
- Every step writes `access_events` and `order_status_histories` and sends a notification.

## Permissions

`fulfill_orders`, `change_delivery_email`, `replace_access`, `view_access_reference`.
`access_reference` is never visible in the customer panel or in any email.
