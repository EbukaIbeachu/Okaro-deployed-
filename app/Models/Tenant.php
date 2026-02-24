<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordCreator;

class Tenant extends Model
{
    use HasFactory, RecordCreator;

    protected $fillable = [
        'user_id',
        'unit_id',
        'full_name',
        'phone',
        'email',
        'profile_image',
        'room_number',
        'move_in_date',
        'move_out_date',
        'active',
        'created_by',
    ];

    protected $casts = [
        'move_in_date' => 'date',
        'move_out_date' => 'date',
        'active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function rents()
    {
        return $this->hasMany(Rent::class);
    }

    public function currentRent()
    {
        return $this->hasOne(Rent::class)->where('status', 'ACTIVE')->latest();
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Rent::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}