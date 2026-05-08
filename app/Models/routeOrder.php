<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class routeOrder extends Model
{
    use HasFactory;
    
    protected $table = 'routeOrder';
    
    protected $fillable = [
        'route_id',
        'max_users',
        'ordered_users',
        'date',
    ];

    protected $casts = [
        'date' => 'datetime',
        'max_users' => 'integer',
        'ordered_users' => 'integer',
    ];


    public function route()
    {
        return $this->belongsTo(Route::class);
    }


    public function hasAvailableSeats($requestedSeats = 1)
    {
        $available = $this->max_users - ($this->booked_users ?? 0);
        return $available >= $requestedSeats;
    }


    public function getAvailableSeats()
    {
        return $this->max_users - ($this->booked_users ?? 0);
    }
}