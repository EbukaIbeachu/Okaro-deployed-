<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'manager_id',
        'title',
        'content',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function dismissedBy()
    {
        return $this->belongsToMany(User::class, 'announcement_dismissals', 'announcement_id', 'user_id')->withTimestamps();
    }
}
