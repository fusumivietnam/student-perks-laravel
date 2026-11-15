<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Browser-only login fixture; never included in DatabaseSeeder or preview scripts.
 * Requires an explicit marker and the loopback-only disposable Compose endpoint.
 */
class BrowserCiAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (env('E2E_TEST_STACK') !== '1'
            || config('app.url') !== 'http://127.0.0.1:18080'
            || ! in_array(config('app.env'), ['local', 'testing'], true)) {
            throw new RuntimeException('Browser CI admin fixture is restricted to the disposable CI stack.');
        }

        $admin = User::firstOrCreate(
            ['email' => 'browser-admin@ci.invalid'],
            ['name' => 'Browser CI Admin', 'password' => 'ci-browser-password-only'],
        );

        $admin->forceFill(['is_admin' => true])->save();

        $regular = User::firstOrCreate(
            ['email' => 'browser-user@ci.invalid'],
            ['name' => 'Browser CI User', 'password' => 'ci-browser-password-only'],
        );
        $regular->forceFill(['is_admin' => false])->save();

        $this->command?->info('Disposable CI browser accounts created. Never use for real users or deployment.');
    }
}
