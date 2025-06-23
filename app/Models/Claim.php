<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $table = 'claims';

    // 
    protected $fillable = [
        'provider_id', 'insurer_id',
        'encounter_date', 'submission_date',
        'specialty', 'priority_level', 'total_amount'
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }

    public function items()
    {
        return $this->hasMany(ClaimItem::class);
    }

    public function batches()
    {
        return $this->belongsToMany(Batch::class)->withTimestamps();
    }

} 