<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promoters', function (Blueprint $table) {
            // делаем с запасом под +7, пробелы, скобки
            $table->string('promoter_phone', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('promoters', function (Blueprint $table) {
            $table->string('promoter_phone', 10)->nullable()->change();
        });
    }
};
