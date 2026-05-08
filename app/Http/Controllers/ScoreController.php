<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Score;

class ScoreController extends Controller
{
    // ================================
    // GET /api/routes/{id}/reviews
    // ================================
    public function index($id)
    {
        $reviews = Score::with('user')
            ->where('route_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Нормализуем данные
        $reviews->getCollection()->transform(function ($item) {
            return [
                'id'        => $item->id,
                'user_id'   => $item->user_id,
                'route_id'  => $item->route_id,
                'rating'    => $item->star_rate,
                'comment'   => $item->text,
                'created_at'=> $item->created_at,
                'user'      => [
                    'id'   => $item->user->id,
                    'name' => $item->user->name,
                ]
            ];
        });

        return response()->json($reviews);
    }

    // ================================
    // POST /api/routes/{id}/reviews
    // ================================
    public function store(Request $request, $id)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:500',
        ]);

        $user = auth()->user();

       
        if (Score::where('route_id', $id)->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Вы уже оставили отзыв'
            ], 400);
        }

        $review = Score::create([
            'user_id'   => $user->id,
            'route_id'  => $id,
            'star_rate' => $request->rating,
            'text'      => $request->comment,
        ]);

        $review->load('user');

        return response()->json([
            'data' => [
                'id'        => $review->id,
                'user_id'   => $review->user_id,
                'route_id'  => $review->route_id,
                'rating'    => $review->star_rate,
                'comment'   => $review->text,
                'created_at'=> $review->created_at,
                'user'      => [
                    'id'   => $review->user->id,
                    'name' => $review->user->name,
                ]
            ]
        ]);
    }
    public function userReviews()
{
    $user = auth()->user();

    $reviews = Score::with('route')
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'data' => $reviews->map(function ($review) {
            return [
                'id'        => $review->id,
                'route_id'  => $review->route_id,
                'rating'    => $review->star_rate,
                'comment'   => $review->text,
                'created_at'=> $review->created_at,
                'route'     => [
                    'id'    => $review->route->id,
                    'title' => $review->route->title,
                    'slug'  => $review->route->slug,
                    'mapColor' => $review->route->mapColor,
                ]
            ];
        })
    ]);
}

}
