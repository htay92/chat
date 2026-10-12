<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class AdminTestCase extends ChatbotTestCase
{
    protected function setUp(): void
    {
        $this->transactional = false;
        parent::setUp();
        // Parent verifies all connection identities before any schema/session operation.
        if (! Schema::hasTable('users')) {
            (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        }
        foreach (glob(database_path('migrations/*chatbot_admin*.php')) as $file) {
            (require $file)->up();
        }
        DB::beginTransaction();
        $this->transactional = true;
    }
}
