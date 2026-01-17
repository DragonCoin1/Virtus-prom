<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_templates', function (Blueprint $table) {
            $table->id('template_id');
            $table->string('template_name', 255); // A, B, ЕН+ФИО и т.п.
            $table->enum('template_type', ['leaflet', 'poster'])->default('leaflet'); // leaflet=листовка, poster=расклейка
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['template_name', 'template_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_templates');
    }
};
