<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_adjustments', function (Blueprint $table) {
            $table->unsignedInteger('city_id')->nullable()->after('promoter_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('salary_adjustments', function (Blueprint $table) {
            $table->dropColumn('city_id');
        });
    }
};
