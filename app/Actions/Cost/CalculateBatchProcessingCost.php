<?php

namespace App\Actions\Cost;

use App\Models\Claim;
use App\Models\Insurer;
use Illuminate\Support\Collection;

class CalculateBatchProcessingCost
{
    private CalculateClaimProcessingCost $calculateClaimProcessingCost;

    public function __construct(CalculateClaimProcessingCost $calculateClaimProcessingCost)
    {
        $this->calculateClaimProcessingCost = $calculateClaimProcessingCost;
    }

    public function handle(array|Collection $claims, Insurer $insurer): float
    {
        $totalCost = 0;
        
        foreach ($claims as $claimData) {
            $claim = $claimData instanceof Claim ? $claimData : new Claim($claimData);
            $totalCost += $this->calculateClaimProcessingCost->handle($claim, $insurer);
        }
        
        return round($totalCost, 2);
    }
} 