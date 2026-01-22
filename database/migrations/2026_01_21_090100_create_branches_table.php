<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id('branch_id');
            $table->string('branch_name', 255);
            $table->unsignedBigInteger('city_id');
            $table->boolean('is_active')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index('city_id');
            $table->foreign('city_id')->references('city_id')->on('cities');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
