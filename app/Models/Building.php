<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordCreator;

class Building extends Model
{
    use HasFactory, RecordCreator;

    protected $fillable = [
        'name',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'image_path',
        'manager_id',
        'total_units',
        'total_floors',
        'created_by',
    ];

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function activeUnits()
    {
        return $this->hasMany(Unit::class)->where('status', 'OCCUPIED');
    }

    public function availableUnits()
    {
        return $this->hasMany(Unit::class)->where('status', 'AVAILABLE');
    }

    public function tenants()
    {
        return $this->hasManyThrough(Tenant::class, Unit::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function rents()
    {
        return $this->hasManyThrough(Rent::class, Unit::class);
    }

    public function getAddressAttribute()
    {
        return $this->address_line1 . 
               ($this->address_line2 ? ', ' . $this->address_line2 : '') . 
               ', ' . $this->city . ', ' . $this->state;
    }
}
