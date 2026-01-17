<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promoters', function (Blueprint $table) {
            $table->id('promoter_id');

            $table->string('promoter_full_name', 255);
            $table->char('promoter_phone', 10)->nullable(); // 10 цифр, без +7
            $table->enum('promoter_status', ['active', 'trainee', 'paused', 'fired'])->default('active');

            $table->date('hired_at')->nullable();
            $table->date('fired_at')->nullable();

            $table->string('promoter_comment', 255)->nullable();

            $table->timestamps();

            $table->unique('promoter_phone');
            $table->index('promoter_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promoters');
    }
};