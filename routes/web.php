<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\RouteOrderController;

use Illuminate\Http\Request;
use App\Http\Controllers\ApprovalController;
Route::get('/test-api', function () {
    return ['message' => 'API works'];
});

Route::get('/', function () {
    return view('vue');
});

Route::prefix('api')->group(function () {
    Route::get('/routes/{routeId}/events', [RouteOrderController::class, 'getEventsByRoute']);

    Route::get('/user/{userId}/bookings', [RouteOrderController::class, 'getUserBookings']);

    Route::post('/bookings', [RouteOrderController::class, 'createBooking']);

    // Получить все бронирования для события (для админки)
    Route::get('/events/{eventId}/bookings', [RouteOrderController::class, 'getEventBookings']);

    // Отменить бронирование (для админки)
    Route::delete('/bookings/{bookingId}', [RouteOrderController::class, 'cancelBooking']);

    Route::get('/events/{eventId}', [RouteOrderController::class, 'getEvent']);

    Route::post('/bookings', [RouteOrderController::class, 'createBooking']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/routes/{id}/reviews', [ScoreController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/routes/{id}/reviews', [ScoreController::class, 'store']);
    });
    Route::get('/routes', [RouteController::class, 'getRoutes']);
    Route::get('/routes/{id}', [RouteController::class, 'getRoute']);
    Route::post('/routes/filter', [FilterController::class, 'index']);


    Route::get('/score/{route_id}', [ScoreController::class, 'index']);


    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/routes/{routeId}/reviews', [ScoreController::class, 'store']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/routes/{id}/points', [PointController::class, 'addPoint']);
        Route::delete('/routes/{routeId}/points/{pointIndex}', [PointController::class, 'deletePoint']);
        Route::get('/user/reviews', [ScoreController::class, 'userReviews']);
        Route::put('/routes/{routeId}/points/{pointIndex}', [PointController::class, 'updatePoint']);
        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoriteController::class, 'index']);
            Route::post('/', [FavoriteController::class, 'store']);
            Route::delete('/{routeId}', [FavoriteController::class, 'destroy']);
        });


        Route::post('/score', [ScoreController::class, 'store']);
        Route::delete('/score/{id}', [ScoreController::class, 'destroy']);
    });
});
Route::get('/routes/{routeId}/reviews', [ScoreController::class, 'index']);

Route::get('/profile', function () {
    return view('vue');
});



// Голосование (публичные маршруты)
Route::get('/vote/{token}', function (Request $request, $token) {
    $status = $request->query('status');

    if (!in_array($status, ['confirmed', 'rejected'])) {
        return view('emails.vote_invalid');
    }

    if ($status === 'rejected') {
        return view('emails.vote_comment', compact('token'));
    }

    return app(ApprovalController::class)->vote(
        Request::create('', 'POST', ['status' => 'confirmed']),
        $token
    );
});
Route::post('/vote/{token}', [ApprovalController::class, 'vote']);

// Тестовый маршрут
Route::get('/test-date', function () {
    return [
        'server_now' => now()->toDateTimeString(),
        'server_date' => now()->toDateString(),
        'timezone' => config('app.timezone'),
    ];
});

