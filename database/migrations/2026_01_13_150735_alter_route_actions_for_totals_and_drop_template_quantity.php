<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) в route_actions добавим общие количества
        Schema::table('route_actions', function (Blueprint $table) {
            if (!Schema::hasColumn('route_actions', 'leaflets_total')) {
                $table->unsignedInteger('leaflets_total')->default(0)->after('route_id');
            }
            if (!Schema::hasColumn('route_actions', 'posters_total')) {
                $table->unsignedInteger('posters_total')->default(0)->after('leaflets_total');
            }
            if (!Schema::hasColumn('route_actions', 'poster_variant')) {
                $table->string('poster_variant', 255)->nullable()->after('posters_total'); 
                // например: "70-80 стендов"
            }
        });

        // 2) в pivot убираем quantity (оставляем просто связь)
        Schema::table('route_action_templates', function (Blueprint $table) {
            if (Schema::hasColumn('route_action_templates', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }

    public function down(): void
    {
        // вернуть quantity
        Schema::table('route_action_templates', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(0);
        });

        // убрать totals
        Schema::table('route_actions', function (Blueprint $table) {
            if (Schema::hasColumn('route_actions', 'leaflets_total')) $table->dropColumn('leaflets_total');
            if (Schema::hasColumn('route_actions', 'posters_total')) $table->dropColumn('posters_total');
            if (Schema::hasColumn('route_actions', 'poster_variant')) $table->dropColumn('poster_variant');
        });
    }
};
