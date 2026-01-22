<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\RoutesController;
use App\Http\Controllers\PromotersController;
use App\Http\Controllers\RouteActionsController;
use App\Http\Controllers\AdTemplatesController;
use App\Http\Controllers\InterviewsController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UsersController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        return redirect()->route('module.route_actions');
    })->name('home');

    // PROMOTERS
    Route::get('/promoters', [PromotersController::class, 'index'])
        ->middleware('module:promoters,view')
        ->name('module.promoters');

    Route::get('/promoters/create', [PromotersController::class, 'create'])
        ->middleware('module:promoters,edit')
        ->name('promoters.create');

    Route::post('/promoters', [PromotersController::class, 'store'])
        ->middleware('module:promoters,edit')
        ->name('promoters.store');

    Route::get('/promoters/import', [PromotersController::class, 'importForm'])
        ->middleware('module:promoters,edit')
        ->name('promoters.import.form');

    Route::post('/promoters/import', [PromotersController::class, 'import'])
        ->middleware('module:promoters,edit')
        ->name('promoters.import');

    Route::get('/promoters/{promoter}/edit', [PromotersController::class, 'edit'])
        ->middleware('module:promoters,edit')
        ->name('promoters.edit');

    Route::put('/promoters/{promoter}', [PromotersController::class, 'update'])
        ->middleware('module:promoters,edit')
        ->name('promoters.update');

    Route::delete('/promoters/{promoter}', [PromotersController::class, 'destroy'])
        ->middleware('module:promoters,delete')
        ->name('promoters.destroy');

    // ROUTES
    Route::get('/routes', function () {
        return redirect()->route('module.cards');
    })->middleware('module:routes,view');

    Route::get('/routes/create', [RoutesController::class, 'create'])
        ->middleware('module:routes,edit')
        ->name('routes.create');

    Route::post('/routes', [RoutesController::class, 'store'])
        ->middleware('module:routes,edit')
        ->name('routes.store');

    Route::get('/routes/{route}/edit', [RoutesController::class, 'edit'])
        ->middleware('module:routes,edit')
        ->name('routes.edit');

    Route::put('/routes/{route}', [RoutesController::class, 'update'])
        ->middleware('module:routes,edit')
        ->name('routes.update');

    Route::get('/routes/import', [RoutesController::class, 'importForm'])
        ->middleware('module:routes,edit')
        ->name('routes.import.form');

    Route::post('/routes/import', [RoutesController::class, 'import'])
        ->middleware('module:routes,edit')
        ->name('routes.import');

    // ROUTE ACTIONS (РАЗНОСКА)
    Route::get('/route-actions', [RouteActionsController::class, 'index'])
        ->middleware('module:route_actions,view')
        ->name('module.route_actions');

    Route::get('/route-actions/create', [RouteActionsController::class, 'create'])
        ->middleware('module:route_actions,edit')
        ->name('route_actions.create');

    Route::post('/route-actions', [RouteActionsController::class, 'store'])
        ->middleware('module:route_actions,edit')
        ->name('route_actions.store');

    Route::get('/route-actions/{routeAction}/edit', [RouteActionsController::class, 'edit'])
        ->middleware('module:route_actions,edit')
        ->name('route_actions.edit');

    Route::put('/route-actions/{routeAction}', [RouteActionsController::class, 'update'])
        ->middleware('module:route_actions,edit')
        ->name('route_actions.update');

    Route::delete('/route-actions/{routeAction}', [RouteActionsController::class, 'destroy'])
        ->middleware('module:route_actions,edit')
        ->name('route_actions.destroy');

    // AD TEMPLATES (кнопкой из "Карты")
    Route::get('/ad-templates', [AdTemplatesController::class, 'index'])
        ->middleware('module:ad_templates,view')
        ->name('ad_templates.index');

    Route::get('/ad-templates/create', [AdTemplatesController::class, 'create'])
        ->middleware('module:ad_templates,edit')
        ->name('ad_templates.create');

    Route::post('/ad-templates', [AdTemplatesController::class, 'store'])
        ->middleware('module:ad_templates,edit')
        ->name('ad_templates.store');

    Route::get('/ad-templates/{adTemplate}/edit', [AdTemplatesController::class, 'edit'])
        ->middleware('module:ad_templates,edit')
        ->name('ad_templates.edit');

    Route::put('/ad-templates/{adTemplate}', [AdTemplatesController::class, 'update'])
        ->middleware('module:ad_templates,edit')
        ->name('ad_templates.update');

    Route::post('/ad-templates/{adTemplate}/toggle', [AdTemplatesController::class, 'toggle'])
        ->middleware('module:ad_templates,edit')
        ->name('ad_templates.toggle');

    // CARDS
    Route::get('/cards', [ModuleController::class, 'cards'])
        ->middleware('module:cards,view')
        ->name('module.cards');

    // INTERVIEWS
    Route::get('/interviews', [InterviewsController::class, 'index'])
        ->middleware('module:interviews,view')
        ->name('interviews.index');

    Route::get('/interviews/create', [InterviewsController::class, 'create'])
        ->middleware('module:interviews,edit')
        ->name('interviews.create');

    Route::post('/interviews', [InterviewsController::class, 'store'])
        ->middleware('module:interviews,edit')
        ->name('interviews.store');

    Route::get('/interviews/{interview}/edit', [InterviewsController::class, 'edit'])
        ->middleware('module:interviews,edit')
        ->name('interviews.edit');

    Route::put('/interviews/{interview}', [InterviewsController::class, 'update'])
        ->middleware('module:interviews,edit')
        ->name('interviews.update');

    Route::delete('/interviews/{interview}', [InterviewsController::class, 'destroy'])
        ->middleware('module:interviews,edit')
        ->name('interviews.destroy');

    // SALARY
    Route::get('/salary', [SalaryController::class, 'index'])
        ->middleware('module:salary,view')
        ->name('salary.index');

    Route::get('/salary/adjustments/create', [SalaryController::class, 'createAdjustment'])
        ->middleware('module:salary,edit')
        ->name('salary.adjustments.create');

    Route::get('/salary/adjustments/{salaryAdjustment}/edit', [SalaryController::class, 'editAdjustment'])
        ->middleware('module:salary,edit')
        ->name('salary.adjustments.edit');

    Route::post('/salary/adjustments', [SalaryController::class, 'storeAdjustment'])
        ->middleware('module:salary,edit')
        ->name('salary.adjustments.store');

    Route::put('/salary/adjustments/{salaryAdjustment}', [SalaryController::class, 'updateAdjustment'])
        ->middleware('module:salary,edit')
        ->name('salary.adjustments.update');

    Route::delete('/salary/adjustments/{salaryAdjustment}', [SalaryController::class, 'destroyAdjustment'])
        ->middleware('module:salary,edit')
        ->name('salary.adjustments.destroy');

    // REPORTS (статистика)
    Route::get('/reports', [ReportsController::class, 'index'])
        ->middleware('module:reports,view')
        ->name('reports.index');

    // USERS (RBAC management)
    Route::get('/users', [UsersController::class, 'index'])
        ->middleware('user.manage')
        ->name('users.index');

    Route::get('/users/create', [UsersController::class, 'create'])
        ->middleware('user.manage')
        ->name('users.create');

    Route::post('/users', [UsersController::class, 'store'])
        ->middleware('user.manage')
        ->name('users.store');

    Route::get('/users/{user}/edit', [UsersController::class, 'edit'])
        ->middleware('user.manage')
        ->name('users.edit');

    Route::put('/users/{user}', [UsersController::class, 'update'])
        ->middleware('user.manage')
        ->name('users.update');

    Route::delete('/users/{user}', [UsersController::class, 'destroy'])
        ->middleware('user.manage')
        ->name('users.destroy');
});
