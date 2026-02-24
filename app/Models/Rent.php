<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordCreator;

class Rent extends Model
{
    use HasFactory, RecordCreator;

    protected $fillable = [
        'tenant_id',
        'unit_id',
        'annual_amount',
        'due_day',
        'start_date',
        'end_date',
        'status',
        'signed_agreement_path',
        'created_by',
    ];

    protected $casts = [
        'annual_amount' => 'decimal:2',
        'due_day' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getBalanceAttribute()
    {
        $start = $this->start_date;
        $end = $this->end_date ? $this->end_date : now();
        
        // Ensure we don't calculate past today if the lease is still active
        $calculationEnd = $end->isFuture() ? now() : $end;
        
        if ($start->isFuture()) {
            return -$this->total_paid;
        }

        // Calculate years passed since start (Annual Rent logic)
        // If today is in the first year, 1 annual payment is due.
        // If today is in the second year, 2 annual payments are due.
        
        $yearsOwed = $start->diffInYears($calculationEnd) + 1;
        
        $expectedTotal = $yearsOwed * $this->annual_amount;
        
        return $expectedTotal - $this->total_paid;
    }

    public function getNextDueDateAttribute()
    {
        if ($this->annual_amount <= 0) return null;
        
        // Calculate how many years represent the total paid amount
        // We use floor because partial payment means the current year is not fully paid
        $yearsPaid = floor($this->total_paid / $this->annual_amount);
        
        // The next due date is the start date plus the number of fully paid years
        return $this->start_date->copy()->addYears($yearsPaid);
    }
}