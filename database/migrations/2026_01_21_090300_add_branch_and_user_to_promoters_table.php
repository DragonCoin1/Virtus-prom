<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promoters', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('promoter_id');
            $table->unsignedBigInteger('user_id')->nullable()->after('branch_id');

            $table->index('branch_id');
            $table->index('user_id');

            $table->foreign('branch_id')->references('branch_id')->on('branches');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('promoters', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn(['branch_id', 'user_id']);
        });
    }
};
