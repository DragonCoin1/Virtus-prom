<?php

namespace App\Services;

use App\Models\Route;

class RouteService
{
    public function create(array $data): Route
    {
        return Route::create($data);
    }

    public function update(Route $route, array $data): Route
    {
        $route->update($data);
        return $route;
    }

    public function toggleActive(Route $route): Route
    {
        $route->is_active = !$route->is_active;
        $route->save();

        return $route;
    }
}
