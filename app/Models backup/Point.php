<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    protected $table = 'point';
    
    protected $fillable = [
        'id',
        'lon',
        'lat',
        'address',
        'url',
        'route_id',
        'name',
        'pointName',
        'description',
        'images'
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}