<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\routeOrder;
use App\Models\Route;
use App\Models\Point;
use App\Models\Booking;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
     public function createEvent(Request $request)
{
    $validator = Validator::make($request->all(), [
        'route_id' => 'required|exists:route,id',
        'max_users' => 'required|integer|min:1',
        'date' => 'required|date|after:now',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $event = routeOrder::create([
        'route_id' => $request->route_id,
        'max_users' => $request->max_users,
        'ordered_users' => 0,
        'date' => Carbon::parse($request->date),
    ]);

    return response()->json([
        'success' => true,
        'data' => $event
    ]);
}
public function getEvents()
{
    $events = routeOrder::with('route')
        ->orderBy('date', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $events
    ]);
}
}
