<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['hotels', 'rooms', 'reserves'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedInteger('external_id')->nullable()->unique()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['hotels', 'rooms', 'reserves'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('external_id');
            });
        }
    }
};
