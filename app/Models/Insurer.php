<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'email',
        'specialty_preferences', 'date_preference',
        'min_batch_size', 'max_batch_size', 'daily_capacity'
    ];

    protected $casts = [
        'specialty_preferences' => 'array'
    ];

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
} 