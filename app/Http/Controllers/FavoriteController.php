<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Получаем все избранные записи с данными маршрута
        $favorites = Favorite::with('route')
            ->where('user_id', $user->id)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }
    
   
    public function store(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:route,id'
        ]);
        
        $user = Auth::user();
        
      
        $existing = Favorite::where('user_id', $user->id)
            ->where('route_id', $request->route_id)
            ->first();
            
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Маршрут уже в избранном'
            ], 400);
        }
        
        $favorite = Favorite::create([
            'user_id' => $user->id,
            'route_id' => $request->route_id
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $favorite
        ], 201);
    }
    

    public function destroy($routeId)
    {
        $user = Auth::user();
        
        $favorite = Favorite::where('user_id', $user->id)
            ->where('route_id', $routeId)
            ->first();
            
        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Маршрут не найден в избранном'
            ], 404);
        }
        
        $favorite->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Маршрут удален из избранного'
        ]);
    }
}