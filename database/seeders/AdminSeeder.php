<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('seeding.admin.email');
        // A generated password is printed once rather than a guessable default being left
        // in place on a server nobody revisits.
        $password = (string) config('seeding.admin.password') ?: Str::password(16);

        $admin = Admin::firstOrNew(['email' => $email]);
        $isNew = ! $admin->exists;

        $admin->fill([
            'name' => (string) config('seeding.admin.name'),
            'status' => UserStatus::Active,
        ]);

        if ($isNew) {
            $admin->password = $password;
        }

        $admin->save();
        $admin->syncRoles(['super_admin']);

        if ($isNew) {
            $this->command?->newLine();
            $this->command?->info("Super admin created: {$email}");
            $this->command?->warn("Password: {$password}");
            $this->command?->comment('Save this now — it is not shown again.');
        }
    }
}
