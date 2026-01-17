<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_actions', function (Blueprint $table) {
            if (Schema::hasColumn('route_actions', 'leaflet_id')) {
                $table->dropColumn('leaflet_id');
            }
            if (Schema::hasColumn('route_actions', 'leaflets_count')) {
                $table->dropColumn('leaflets_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_actions', function (Blueprint $table) {
            $table->unsignedBigInteger('leaflet_id')->nullable();
            $table->unsignedInteger('leaflets_count')->default(0);
        });
    }
};
