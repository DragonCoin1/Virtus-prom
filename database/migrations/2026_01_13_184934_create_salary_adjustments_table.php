<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_adjustments', function (Blueprint $table) {
            $table->increments('salary_adjustment_id');

            $table->unsignedInteger('promoter_id')->index();
            $table->date('adj_date')->index();

            // может быть + и -
            $table->integer('amount')->default(0);

            $table->string('comment', 255)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_adjustments');
    }
};
