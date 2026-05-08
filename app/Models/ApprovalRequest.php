<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';
    
    protected $fillable = [
        'route_id', 'proposed_date', 'start_time', 'deadline', 'status', 'created_by'
    ];
    
    protected $casts = [
        'proposed_date' => 'date',
        'deadline' => 'datetime',
    ];
    
    public function route()
    {
        return $this->belongsTo(Route::class);
    }
    
    public function requestPoints()
    {
        return $this->hasMany(RequestPoint::class, 'request_id');
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}