<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $perUser = 5;
        $account = Account::create(['name' => 'Nikard']);

        User::factory()->create([
            'account_id' => $account->id,
            'first_name' => 'Monika',
            'last_name' => 'Bombol-Lagha',
            'email' => 'monika@example.com',
            'password' => Hash::make('secret'),
            'owner' => true,
        ]);

        User::factory($perUser)->create(['account_id' => $account->id]);

        User::query()->each(function (User $user) use ($perUser) {
            Task::factory()
                ->count($perUser)
                ->state(fn () => ['uuid' => (string) Str::uuid()])
                ->create([
                    'user_id' => $user->id,
                ]);
        });
    }
}
