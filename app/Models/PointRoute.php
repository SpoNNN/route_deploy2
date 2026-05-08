<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointRoute extends Model
{
    protected $table = 'point_route';
    
    protected $fillable = ['route_id', 'point_id', 'order_index'];
    
    public function route()
    {
        return $this->belongsTo(Route::class);
    }
    
    public function point()
    {
        return $this->belongsTo(Point::class);
    }
}