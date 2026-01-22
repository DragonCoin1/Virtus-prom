<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('city_id')->nullable()->after('role_id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('city_id');

            $table->index('city_id');
            $table->index('branch_id');

            $table->foreign('city_id')->references('city_id')->on('cities');
            $table->foreign('branch_id')->references('branch_id')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['city_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn(['city_id', 'branch_id']);
        });
    }
};
