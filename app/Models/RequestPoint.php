<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestPoint extends Model
{
    protected $table = 'request_points';
    
    protected $fillable = [
        'request_id', 'point_id', 'status', 'comment', 'voted_at', 'token'
    ];
    
    protected $casts = [
        'voted_at' => 'datetime',
    ];
    
    public function request()
    {
        return $this->belongsTo(ApprovalRequest::class, 'request_id');
    }
    
    public function point()
    {
        return $this->belongsTo(Point::class);
    }
}