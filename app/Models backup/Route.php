<?php

namespace App\Models;
use App\Models\Point;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'route';

    protected $fillable = [

        'title',
        'mapColor',
        'description',
        'points',
        'duration',
        'distance',
        'participants',
        'audience',
        'slug'
    ];

    public function point()
    {
        return $this->hasMany(Point::class);
    }

    public function routeInfos()
    {
        return $this->hasMany(RouteInfo::class);
    }
}