<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_module_access', function (Blueprint $table) {
            $table->id('access_id');

            $table->unsignedBigInteger('role_id');
            $table->string('module_code')->index(); // например: promoters, routes, interviews

            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit')->default(false);

            $table->unique(['role_id', 'module_code']);

            $table->foreign('role_id')->references('role_id')->on('roles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_module_access');
    }
};
