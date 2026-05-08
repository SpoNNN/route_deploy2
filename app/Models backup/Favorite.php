<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{    protected $table = 'favorite';
    protected $fillable = ['user_id', 'route_id'];
    
    /**
     * Получить маршрут
     */
    public function route()
    {
        return $this->belongsTo(Route::class);
    }
    
    /**
     * Получить пользователя
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}