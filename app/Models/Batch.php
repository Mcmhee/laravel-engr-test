<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    // 
    protected $fillable = ['insurer_id', 'provider_id', 'batch_date', 'total_cost'];

    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function claims()
    {
        return $this->belongsToMany(Claim::class)->withTimestamps();
    }
}
