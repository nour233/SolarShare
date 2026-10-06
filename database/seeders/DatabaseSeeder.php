<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // "role" is not fillable, so it is set with forceFill.
        $admin = User::firstOrNew(['email' => 'admin@solarshare.test']);
        $admin->forceFill([
            'name' => 'Admin',
            'password' => 'password',
            'role' => 'admin',
            'email_verified_at' => now(),
        ])->save();
    }
}
