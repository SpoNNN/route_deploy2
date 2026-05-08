<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteInfo extends Model
{
    protected $table = 'RouteInfo';
    
    protected $fillable = [
        'id',
        'key',
        'label',
        'value',
        'route_id'
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}