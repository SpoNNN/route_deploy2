<?php

namespace App\Http\Controllers;

use App\Models\routeOrder;
use App\Models\Route;
use App\Models\Point;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RouteOrderController extends Controller
{

    public function getEventsByRoute($routeId)
    {
        try {
            $events = routeOrder::where('route_id', $routeId)
                ->where('date', '>=', now()->startOfDay())
                ->orderBy('date', 'asc')
                ->with(['route', 'route.point'])
                ->get()
                ->map(function ($event) {
                    $route = $event->route;
                    
                
                    $firstPoint = $route ? $route->point()
                        ->orderBy('pointName', 'asc')
                        ->first() : null;
                    
                    $location = $firstPoint && $firstPoint->address 
                        ? $firstPoint->address 
                        : 'встреча группы состоится на адресу: ул. 50ти Бытам, 32/2';
                    
                    return [
                        'id' => $event->id,
                        'date' => Carbon::parse($event->date)->format('Y-m-d'),
                        'title' => $route ? $route->title : 'Маршрут',
                        'startTime' => Carbon::parse($event->date)->format('H:i'),
                        'bookedSeats' => $event->ordered_users ?? 0,
                        'totalSeats' => $event->max_users,
                        'location' => $location,
                        'description' => $route ? $route->description : 'Кампус Евразийского МОЦ#6',
                        'routeId' => $event->route_id
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения событий: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'eventId' => 'required|exists:routeOrder,id',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'seats' => 'required|integer|min:1|max:10',
            'phone' => 'required|string|regex:/^[0-9+\-\s()]+$/',
            'email' => 'required|email|max:255',
            'userId' => 'required|integer', 
        ], [
            'eventId.required' => 'Не указано событие',
            'eventId.exists' => 'Событие не найдено',
            'firstName.required' => 'Введите имя',
            'lastName.required' => 'Введите фамилию',
            'seats.required' => 'Укажите количество мест',
            'seats.min' => 'Минимум 1 место',
            'seats.max' => 'Максимум 10 мест',
            'phone.required' => 'Введите телефон',
            'phone.regex' => 'Некорректный формат телефона',
            'email.required' => 'Введите email',
            'email.email' => 'Некорректный email',
            'userId.required' => 'Не указан пользователь',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $event = routeOrder::findOrFail($request->eventId);
            
           
            $currentBooked = $event->ordered_users ?? 0;
            $availableSeats = $event->max_users - $currentBooked;
            
            if ($request->seats > $availableSeats) {
                return response()->json([
                    'success' => false,
                    'message' => "Недостаточно свободных мест. Доступно: {$availableSeats}"
                ], 400);
            }

       
            $booking = Booking::create([
                'user_email' => $request->email,
                'user_number' => $request->phone,
                'uesr_name' => $request->firstName, 
                'user_lastname' => $request->lastName,
                'order_id' => $event->id,
                'users' => $request->seats,
                'user_id' => $request->userId, 
            ]);

          
            $event->ordered_users = $currentBooked + $request->seats;
            $event->save();

            DB::commit();

            // Здесь можно отправить email уведомление
            // Mail::to($request->email)->send(new BookingConfirmation($booking));

            return response()->json([
                'success' => true,
                'message' => 'Бронирование успешно создано',
                'data' => [
                    'bookingId' => $booking->id,
                    'eventId' => $event->id,
                    'bookedSeats' => $event->ordered_users,
                    'totalSeats' => $event->max_users
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания бронирования: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getEvent($eventId)
    {
        try {
            $event = routeOrder::with(['route', 'route.point'])->findOrFail($eventId);
            $route = $event->route;

         
            $firstPoint = $route ? $route->point()
                ->orderBy('pointName', 'asc')
                ->first() : null;
            
            $location = $firstPoint && $firstPoint->address 
                ? $firstPoint->address 
                : 'встреча группы состоится на адресу: ул. 50ти Бытам, 32/2';

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $event->id,
                    'date' => Carbon::parse($event->date)->format('Y-m-d'),
                    'title' => $route ? $route->title : 'Маршрут',
                    'startTime' => Carbon::parse($event->date)->format('H:i'),
                    'bookedSeats' => $event->ordered_users ?? 0,
                    'totalSeats' => $event->max_users,
                    'location' => $location,
                    'description' => $route ? $route->description : 'Кампус Евразийского МОЦ#6',
                    'routeId' => $event->route_id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Событие не найдено'
            ], 404);
        }
    }


    public function getEventBookings($eventId)
    {
        try {
            $event = routeOrder::findOrFail($eventId);
            
            $bookings = Booking::where('order_id', $eventId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'name' => $booking->uesr_name,
                        'lastName' => $booking->user_lastname,
                        'email' => $booking->user_email,
                        'phone' => $booking->user_number,
                        'seats' => $booking->users,
                        'createdAt' => Carbon::parse($booking->created_at)->format('d.m.Y H:i'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'totalSeats' => $event->max_users,
                        'bookedSeats' => $event->ordered_users,
                        'availableSeats' => $event->max_users - ($event->ordered_users ?? 0),
                    ],
                    'bookings' => $bookings
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения бронирований: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelBooking($bookingId)
    {
        try {
            $booking = Booking::findOrFail($bookingId);
            $event = routeOrder::findOrFail($booking->order_id);

            DB::beginTransaction();

           
            $event->ordered_users = max(0, $event->ordered_users - $booking->users);
            $event->save();

        
            $booking->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Бронирование отменено',
                'data' => [
                    'eventId' => $event->id,
                    'bookedSeats' => $event->ordered_users,
                    'totalSeats' => $event->max_users
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка отмены бронирования: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getUserBookings($userId)
    {
        try {
            $bookings = Booking::where('user_id', $userId)
                ->with(['routeOrder.route.point'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($booking) {
                    $routeOrder = $booking->routeOrder;
                    $route = $routeOrder->route;
                 
                    $firstPoint = $route ? $route->point()
                        ->orderBy('pointName', 'asc')
                        ->first() : null;
                    
                    $location = $firstPoint && $firstPoint->address 
                        ? $firstPoint->address 
                        : 'встреча группы состоится на адресу: ул. 50ти Бытам, 32/2';

                    return [
                        'id' => $booking->id,
                        'bookingDate' => Carbon::parse($booking->created_at)->format('d.m.Y H:i'),
                        'seats' => $booking->users,
                        'route' => [
                            'id' => $route->id,
                            'title' => $route->title,
                            'description' => $route->description,
                            'slug' => $route->slug,
                            'mapColor' => $route->mapColor,
                        ],
                        'event' => [
                            'id' => $routeOrder->id,
                            'date' => Carbon::parse($routeOrder->date)->format('d.m.Y'),
                            'time' => Carbon::parse($routeOrder->date)->format('H:i'),
                            'location' => $location,
                            'bookedSeats' => $routeOrder->ordered_users,
                            'totalSeats' => $routeOrder->max_users,
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $bookings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения бронирований: ' . $e->getMessage()
            ], 500);
        }
    }
}