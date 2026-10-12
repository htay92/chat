<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\AdminTestCase;

class ProvisioningTest extends AdminTestCase
{
    public function test_password_bounds_follow_the_configured_hash_algorithm(): void
    {
        config(['hashing.driver' => 'argon2id']);
        Hash::forgetDrivers();
        $password = str_repeat('x', 80);
        $this->artisan('chatbot:admin:create')
            ->expectsQuestion('Admin name', 'Operator')->expectsQuestion('Admin email', 'argon@example.test')
            ->expectsQuestion('Password (hidden)', $password)->expectsQuestion('Confirm password (hidden)', $password)
            ->expectsConfirmation('Confirm admin create?', 'yes')->assertSuccessful();
        $this->assertTrue(Hash::check($password, User::firstOrFail()->password));
    }

    public function test_default_denied_capability_and_confirmed_grant_revoke(): void
    {
        $user = User::create(['name' => 'Operator', 'email' => 'operator@example.test', 'password' => 'a-safe-password', 'is_chatbot_admin' => true]);
        $this->assertFalse($user->fresh()->is_chatbot_admin);
        $this->artisan('chatbot:admin:grant', ['user' => $user->id])->expectsConfirmation('Confirm admin grant?', 'no')->assertSuccessful();
        $this->assertFalse($user->fresh()->is_chatbot_admin);
        $this->artisan('chatbot:admin:grant', ['user' => $user->id])->expectsConfirmation('Confirm admin grant?', 'yes')->assertSuccessful();
        $this->assertTrue($user->fresh()->is_chatbot_admin);
        DB::table('sessions')->insert(['id' => 'synthetic-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->artisan('chatbot:admin:revoke', ['user' => $user->id])->expectsConfirmation('Confirm admin revoke?', 'yes')->assertSuccessful();
        $this->assertFalse($user->fresh()->is_chatbot_admin);
        $this->assertSame(0, DB::table('sessions')->count());
    }

    public function test_protected_creation_reset_and_denied_operator(): void
    {
        $this->artisan('chatbot:admin:create')
            ->expectsQuestion('Admin name', 'Operator')->expectsQuestion('Admin email', 'operator@example.test')
            ->expectsQuestion('Password (hidden)', 'a-safe-password')->expectsQuestion('Confirm password (hidden)', 'a-safe-password')
            ->expectsConfirmation('Confirm admin create?', 'yes')->assertSuccessful();
        $user = User::firstOrFail();
        $this->assertTrue($user->is_chatbot_admin);
        $this->assertTrue(Hash::check('a-safe-password', $user->password));
        $this->artisan('chatbot:admin:password-reset', ['user' => $user->id])
            ->expectsQuestion('Password (hidden)', 'another-safe-password')->expectsQuestion('Confirm password (hidden)', 'another-safe-password')
            ->expectsConfirmation('Confirm admin password-reset?', 'yes')->assertSuccessful();
        $this->assertTrue(Hash::check('another-safe-password', $user->fresh()->password));
        config(['chatbot.operator_users' => []]);
        $this->artisan('chatbot:admin:grant', ['user' => $user->id])->assertFailed();
    }
}
