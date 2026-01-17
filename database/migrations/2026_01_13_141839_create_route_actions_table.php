<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_actions', function (Blueprint $table) {
            $table->id('route_action_id');

            $table->date('action_date');

            $table->unsignedBigInteger('promoter_id');
            $table->unsignedBigInteger('route_id');

            // макет/листовка пока без FK, потому что модель листовок ещё не делали
            $table->unsignedBigInteger('leaflet_id')->nullable();

            $table->unsignedInteger('leaflets_count')->default(0);
            $table->unsignedInteger('cards_count')->default(0);
            $table->unsignedInteger('boxes_done')->default(0);

            $table->string('action_comment', 255)->nullable();

            // ВАЖНО: users в Laravel по умолчанию имеет PK = id
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('promoter_id')->references('promoter_id')->on('promoters');
            $table->foreign('route_id')->references('route_id')->on('routes');
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['action_date', 'promoter_id']);
            $table->index(['action_date', 'route_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_actions');
    }
};
