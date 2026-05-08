<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Point;
use App\Models\RouteInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PointController extends Controller
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
        $route = Route::with(['point', 'routeInfos'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transformRoute($route)
        ]);
    }

    public function addPoint(Request $request, $id)
    {
        try {
            $route = Route::findOrFail($id);
            $imageUrls = [];

            if ($request->has('images')) {
                $images = json_decode($request->images, true);

                if (is_array($images)) {
                    foreach ($images as $index => $base64Image) {
                        // base64 магия
                        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                            $type = strtolower($type[1]);

                            $imageData = base64_decode($base64Image);

                            if ($imageData === false) {
                                continue;
                            }

                            $fileName = 'point_' . $route->id . '_' . time() . '_' . $index . '.' . $type;
                            $path = 'points/' . $fileName;
                            Storage::disk('public')->put($path, $imageData);
                            $imageUrls[] = '/storage/' . $path;
                        }
                    }
                }
            }
            $point = new Point();
            $point->route_id = $route->id;
            $point->name = $request->name;
            $point->description = $request->description;
            $point->address = $request->address;
            $point->lon = $request->lon;
            $point->lat = $request->lat;
            $point->url = $request->url;
            $point->pointName = $request->point_name ?? null;
            $point->images = json_encode($imageUrls);
            $point->save();

            return response()->json([
                'success' => true,
                'message' => 'Точка успешно создана',
                'data' => [
                    'lon' => (float) $point->lon,
                    'lat' => (float) $point->lat,
                    'name' => $point->name,
                    'address' => $point->address,
                    'url' => $point->url,
                    'point_name' => $point->pointName,
                    'description' => $point->description,
                    'images' => $imageUrls
                ]
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Маршрут не найден'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании точки: ' . $e->getMessage()
            ], 500);
        }
    }
    public function updatePoint(Request $request, $routeId, $pointIndex)
    {
        $route = Route::with('point')->findOrFail($routeId);
        $points = $route->point;

        if (!isset($points[$pointIndex])) {
            return response()->json(['success' => false, 'message' => 'Точка не найдена'], 404);
        }

        $point = $points[$pointIndex];
        $imageUrls = [];

        if ($request->has('images')) {
            $images = json_decode($request->images, true);
            if (is_array($images)) {
                foreach ($images as $index => $img) {
                    if (str_starts_with($img, '/storage/')) {
                        // уже сохранённая картинка — оставляем как есть
                        $imageUrls[] = $img;
                    } elseif (preg_match('/^data:image\/(\w+);base64,/', $img, $type)) {
                        $base64 = substr($img, strpos($img, ',') + 1);
                        $ext = strtolower($type[1]);
                        $data = base64_decode($base64);
                        $fileName = 'point_' . $route->id . '_' . time() . '_' . $index . '.' . $ext;
                        Storage::disk('public')->put('points/' . $fileName, $data);
                        $imageUrls[] = '/storage/points/' . $fileName;
                    }
                }
            }
        }

        $point->name = $request->name;
        $point->description = $request->description;
        $point->address = $request->address;
        $point->lon = $request->lon;
        $point->lat = $request->lat;
        $point->url = $request->url;
        $point->pointName = $request->point_name ?? null;
        $point->images = json_encode($imageUrls);
        $point->save();

        return response()->json(['success' => true, 'message' => 'Точка обновлена']);
    }
    public function deletePoint($routeId, $pointIndex)
    {
        try {
            $route = Route::with('point')->findOrFail($routeId);

            $points = $route->point;

            if (!isset($points[$pointIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Точка не найдена'
                ], 404);
            }

            $point = $points[$pointIndex];

            $images = json_decode($point->images, true) ?? [];
            foreach ($images as $imagePath) {
                $storagePath = str_replace('/storage/', '', $imagePath);
                Storage::disk('public')->delete($storagePath);
            }

            $point->delete();

            return response()->json([
                'success' => true,
                'message' => 'Точка успешно удалена'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Маршрут не найден'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении точки: ' . $e->getMessage()
            ], 500);
        }
    }

    private function transformRoute(Route $route)
    {
        return [
            'id' => $route->id,
            'title' => $route->title,
            'map_color' => $route->mapColor,
            'description' => $route->description,
            'duration' => $route->duration,
            'audience' => $route->audience,
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