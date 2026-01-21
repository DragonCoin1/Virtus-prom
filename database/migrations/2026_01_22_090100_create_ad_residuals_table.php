<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_residuals', function (Blueprint $table) {
            $table->id('ad_residual_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('ad_type', 50);
            $table->unsignedInteger('ad_amount');
            $table->unsignedInteger('remaining_amount');
            $table->date('received_at')->index();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->foreign('branch_id')->references('branch_id')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_residuals');
    }
};
