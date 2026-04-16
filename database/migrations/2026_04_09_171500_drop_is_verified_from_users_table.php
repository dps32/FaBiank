<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_verified')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'pgsql', 'sqlite'], true)) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_verified');
            });

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_verified')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false);
        });
    }
};
