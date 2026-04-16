<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users CHANGE sms_code two_factor_secret VARCHAR(255) NULL');

            return;
        }

        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            DB::statement('ALTER TABLE users RENAME COLUMN sms_code TO two_factor_secret');

            return;
        }

        Schema::table('users', function ($table) {
            $table->renameColumn('sms_code', 'two_factor_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users CHANGE two_factor_secret sms_code VARCHAR(255) NULL');

            return;
        }

        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            DB::statement('ALTER TABLE users RENAME COLUMN two_factor_secret TO sms_code');

            return;
        }

        Schema::table('users', function ($table) {
            $table->renameColumn('two_factor_secret', 'sms_code');
        });
    }
};
