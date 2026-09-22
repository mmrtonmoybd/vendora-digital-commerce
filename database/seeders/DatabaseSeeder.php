<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminSeeder::class,
            OrderSourceSeeder::class,
            EmailTemplateSeeder::class,
            DemoParitySeeder::class,
        ]);

        // Orders, deposits, tickets and commissions spread over 90 days, so the dashboards
        // and reports have something to show. Opt-in via SEED_DEMO=true, because a real
        // shop does not want fabricated orders in its books.
        if (filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOL)) {
            $this->call(DemoActivitySeeder::class);
        }

        // Thumbnails are drawn from each record's own gradient and icon, so the catalogue
        // has artwork without reaching for the network or a placeholder service.
        $this->command?->call('demo:images');
    }
}
