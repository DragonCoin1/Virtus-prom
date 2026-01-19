<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            if (!Schema::hasColumn('routes', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('route_comment');
                $table->index('sort_order');
            }
        });

        if (Schema::hasColumn('routes', 'sort_order')) {
            DB::table('routes')
                ->where('sort_order', 0)
                ->orderBy('route_id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('routes')
                            ->where('route_id', $row->route_id)
                            ->update(['sort_order' => $row->route_id]);
                    }
                }, 'route_id');
        }
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            if (Schema::hasColumn('routes', 'sort_order')) {
                $table->dropIndex(['sort_order']);
                $table->dropColumn('sort_order');
            }
        });
    }
};
