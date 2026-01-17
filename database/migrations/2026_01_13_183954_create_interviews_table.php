<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->increments('interview_id');

            $table->date('interview_date')->index();
            $table->string('candidate_name', 255);
            $table->string('candidate_phone', 50)->nullable();

            // откуда пришёл: авито / hh / знакомые / улица и тд
            $table->string('source', 120)->nullable();

            // статус: planned / came / no_show / hired / rejected
            $table->string('status', 30)->default('planned')->index();

            $table->string('comment', 255)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
