<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait RecordCreator
{
    public static function bootRecordCreator()
    {
        static::creating(function ($model) {
            if (Auth::check() && !$model->created_by) {
                $model->created_by = Auth::id();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
