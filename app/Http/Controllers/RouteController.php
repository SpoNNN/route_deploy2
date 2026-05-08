<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Point;
use App\Models\RouteInfo;
use Illuminate\Http\Request;


// Необходимо рефакторить, временное решение переноса
class RouteController extends Controller
{
    public function getRoutes()
    {
        $routes = Route::with(['point', 'RouteInfos'])->get();

        return response()->json([
            'success' => true,
            'data' => $routes->map(function ($route) {
                return $this->transformRoute($route);
            })
        ]);
    }

    public function getRoute($id)
    {

        $route = Route::with('routeInfos')
            ->findOrFail($id);


        return response()->json([
            'success' => true,
            'data' => $this->transformRoute($route)
        ]);
    }



    private function transformRoute(Route $route)
    {
        return [
            'id' => $route->id,
            'title' => $route->title,
            'map_color' => $route->mapColor,
            'description' => $route->description,
            'distance' => $route->distance,
            'duration' => $route->duration,
            'audience' => $route->audience,
            'participants' => $route->participants,
            'slug' => $route->slug,
            'info_items' => $route->routeInfos ? $route->routeInfos->mapWithKeys(function ($info) {
                return [
                    $info->key => [
                        'label' => $info->label,
                        'value' => $info->value
                    ]
                ];
            }) : [],
            'point' => $route->point ? $route->point->map(function ($point) {
                return [
                    'lon' => (float) $point->lon,
                    'lat' => (float) $point->lat,
                    'name' => $point->name,
                    'address' => $point->address,
                    'url' => $point->url,
                    'point_name' => $point->pointName,
                    'description' => $point->description,
                    'images' => json_decode($point->images) ?? []
                ];
            }) : []
        ];
    }
}