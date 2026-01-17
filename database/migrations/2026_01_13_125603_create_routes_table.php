<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id('route_id');

            $table->string('route_code')->unique();          // KO-1, KO-2...
            $table->string('route_district')->nullable();    // район/название

            $table->enum('route_type', ['city', 'private', 'mixed'])->default('city');

            $table->boolean('is_active')->default(true);

            $table->integer('boxes_count')->default(0);
            $table->integer('entrances_count')->default(0);

            $table->string('route_comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
