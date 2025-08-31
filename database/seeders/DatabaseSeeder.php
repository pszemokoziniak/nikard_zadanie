<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $perUser = 5;
        $account = Account::create(['name' => 'Nikard']);

        $owner = User::factory()->create([
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

        // --- Stały token Sanctum: Bearer 1|hwO9b15wmUR0XGjPVRYwnFp5vpflu2JJbej75AJjc17a249c ---
        $plain = 'hwO9b15wmUR0XGjPVRYwnFp5vpflu2JJbej75AJjc17a249c';
        $hashed = hash('sha256', $plain);

        $pat = new PersonalAccessToken;
        $pat->forceFill([
            'id' => 1,
            'tokenable_type' => User::class,
            'tokenable_id' => $owner->id,
            'name' => 'api-fixed',
            'token' => $hashed,
            'abilities' => ['tasks:read'],
        ])->save();

        $this->command?->info("Fixed API token zapisany w DB dla {$owner->email}. Użyj Bearer: {$pat->id}|{$plain}");
    }
}
