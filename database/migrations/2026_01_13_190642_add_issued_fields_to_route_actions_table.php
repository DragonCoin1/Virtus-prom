<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_actions', function (Blueprint $table) {
            if (!Schema::hasColumn('route_actions', 'leaflets_issued')) {
                $table->unsignedInteger('leaflets_issued')->default(0)->after('leaflets_total');
            }
            if (!Schema::hasColumn('route_actions', 'posters_issued')) {
                $table->unsignedInteger('posters_issued')->default(0)->after('posters_total');
            }
            if (!Schema::hasColumn('route_actions', 'cards_issued')) {
                $table->unsignedInteger('cards_issued')->default(0)->after('cards_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_actions', function (Blueprint $table) {
            if (Schema::hasColumn('route_actions', 'leaflets_issued')) $table->dropColumn('leaflets_issued');
            if (Schema::hasColumn('route_actions', 'posters_issued')) $table->dropColumn('posters_issued');
            if (Schema::hasColumn('route_actions', 'cards_issued')) $table->dropColumn('cards_issued');
        });
    }
};
