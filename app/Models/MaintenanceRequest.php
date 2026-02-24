<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordCreator;

class MaintenanceRequest extends Model
{
    use HasFactory, RecordCreator;

    protected $fillable = [
        'unit_id',
        'tenant_id',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'resolved_at',
        'created_by',
    ];

    protected $dates = [
        'resolved_at',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
