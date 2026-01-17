<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_actions', function (Blueprint $table) {
            if (!Schema::hasColumn('route_actions', 'payment_amount')) {
                $table->unsignedInteger('payment_amount')
                    ->default(0)
                    ->after('boxes_done');
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_actions', function (Blueprint $table) {
            if (Schema::hasColumn('route_actions', 'payment_amount')) {
                $table->dropColumn('payment_amount');
            }
        });
    }
};
