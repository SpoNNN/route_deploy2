<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Route;
class FilterController extends Controller
{
    private function applyFilters($query, Request $request)
    {

        if ($request->has('min_distance') && $request->min_distance) {
            $query->where('distance', '>=', $request->min_distance);
        }


        if ($request->has('max_distance') && $request->max_distance) {
            $query->where('distance', '<=', $request->max_distance);
        }


        if ($request->has('max_participants') && $request->max_participants) {
            $query->where('participants', '<=', $request->max_participants);
        }


        if ($request->has('target_audience') && $request->target_audience) {
            $query->where('audience', $request->target_audience);
        }


        if ($request->has('min_duration') && $request->min_duration) {
            $query->where('duration', '>=', $request->min_duration);
        }


        if ($request->has('max_duration') && $request->max_duration) {
            $query->where('duration', '<=', $request->max_duration);
        }

        return $query;
    }
    public function index(Request $request): JsonResponse
    {
        $query = Route::query();

        $this->applyFilters($query, $request);

        $routes = $query->get();

        return response()->json([
            'success' => true,
            'routes' => $routes,
            'total' => $routes->count()
        ]);
    }
}
