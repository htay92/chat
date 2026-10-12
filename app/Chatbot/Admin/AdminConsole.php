<?php

namespace App\Chatbot\Admin;

use App\Chatbot\OperatorAccess;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminConsole
{
    public function run(Command $command, string $action): int
    {
        try {
            $operator = app(OperatorAccess::class)->identity();
            $user = $action === 'create' ? new User : User::findOrFail($command->argument('user'));
            if ($action === 'create') {
                $user->name = $command->ask('Admin name');
                $user->email = $command->ask('Admin email');
                Validator::make($user->getAttributes(), ['name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users,email'])->validate();
            }
            if (in_array($action, ['create', 'password-reset'], true)) {
                $password = $command->secret('Password (hidden)');
                $confirmation = $command->secret('Confirm password (hidden)');
                if (! is_string($password) || ! mb_check_encoding($password, 'UTF-8') || mb_strlen($password) < 12
                    || mb_strlen($password) > 128 || (config('hashing.driver') === 'bcrypt' && strlen($password) > 72)
                    || str_contains($password, "\0") || $password !== $confirmation) {
                    throw new \InvalidArgumentException;
                }
                $user->password = $password;
            }
            if (! $command->confirm('Confirm admin '.$action.'?')) {
                return 0;
            }
            $audit = ['operator' => $operator, 'action' => 'admin-'.$action, 'resource_id' => $user->id, 'outcome' => 'started', 'time' => now('UTC')->toISOString()];
            Log::channel('chatbot_audit')->info('chatbot_admin_operator', $audit);
            DB::transaction(function () use ($user, $action) {
                if ($user->exists) {
                    User::lockForUpdate()->findOrFail($user->id);
                }
                if (in_array($action, ['create', 'grant', 'revoke'], true)) {
                    $user->is_chatbot_admin = $action !== 'revoke';
                }
                $user->save();
                if (in_array($action, ['revoke', 'password-reset'], true)) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }
            });
            $audit['resource_id'] = $user->id;
            $audit['outcome'] = 'completed';
            Log::channel('chatbot_audit')->info('chatbot_admin_operator', $audit);
            $command->info('Admin operation completed.');

            return 0;
        } catch (\Throwable) {
            $command->error('Admin operation failed. Check operator access, input and service health.');

            return 1;
        }
    }
}
