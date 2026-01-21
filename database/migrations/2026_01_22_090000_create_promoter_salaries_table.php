<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promoter_salaries', function (Blueprint $table) {
            $table->id('salary_id');
            $table->unsignedBigInteger('promoter_id');
            $table->integer('amount');
            $table->date('salary_period')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('comment', 255)->nullable();
            $table->timestamps();

            $table->index('promoter_id');
            $table->foreign('promoter_id')->references('promoter_id')->on('promoters');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promoter_salaries');
    }
};
