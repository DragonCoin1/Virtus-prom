<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('promoters:clear', function () {
    $this->info('Removing related route actions...');
    DB::table('route_actions')->delete();

    $this->info('Removing promoters...');
    $deleted = DB::table('promoters')->delete();

    $this->info("Done. Deleted promoters: {$deleted}.");
})->purpose('Delete all promoters and related route actions');
