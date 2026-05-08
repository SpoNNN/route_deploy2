<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'routeBooking';
    protected $fillable = [
        'user_email',
        'user_number',
        'uesr_name',
        'user_lastname',
        'order_id',
        'users', // кол-во людей на посещение
        'user_id'
    ];
      protected $casts = [
        'users' => 'integer',
        'order_id' => 'integer',
        'user_id' => 'integer',
    ];
      public function routeOrder()
    {
        return $this->belongsTo(routeOrder::class, 'order_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->uesr_name} {$this->user_lastname}";
    }
}
