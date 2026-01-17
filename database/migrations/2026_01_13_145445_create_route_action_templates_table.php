<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_action_templates', function (Blueprint $table) {
            $table->id('route_action_template_id');

            $table->unsignedBigInteger('route_action_id');
            $table->unsignedBigInteger('template_id');

            $table->unsignedInteger('quantity')->default(0); // сколько штук этого макета

            $table->timestamps();

            $table->foreign('route_action_id')->references('route_action_id')->on('route_actions')->onDelete('cascade');
            $table->foreign('template_id')->references('template_id')->on('ad_templates');

            $table->unique(['route_action_id', 'template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_action_templates');
    }
};
