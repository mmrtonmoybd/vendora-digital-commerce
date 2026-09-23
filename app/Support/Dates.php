<?php

declare(strict_types=1);

namespace App\Support;

/**
 * One way of writing a date, everywhere.
 *
 * Twelve-hour, day first, month by name: "13 Aug, 2026 01:22 pm". Named here rather than
 * repeated at every call site so the admin panel, the customer panel, the invoice and the
 * storefront cannot drift apart — which they had, with four different formats between them.
 *
 * Day-first is deliberate: the shop's customers and staff are in Bangladesh, where
 * 03/08 means the third of August, and a month-first format is read wrong rather than
 * read as unfamiliar.
 */
final class Dates
{
    /** 13 Aug, 2026 01:22 pm */
    public const DATETIME = 'd M, Y h:i a';

    /** 13 Aug, 2026 */
    public const DATE = 'd M, Y';

    /** 01:22 pm */
    public const TIME = 'h:i a';
}
