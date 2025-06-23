<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimItem extends Model
{
    use HasFactory;

    // 

    protected $fillable = ['claim_id', 'name', 'unit_price', 'quantity', 'subtotal'];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}
