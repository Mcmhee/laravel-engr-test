<?php

namespace App\Actions\Cost;

use App\Models\Claim;
use App\Models\Insurer;
use Illuminate\Support\Collection;

class CalculateBatchProcessingCost
{
    // This class helps calculate the total cost for a group (batch) of claims
    private CalculateClaimProcessingCost $calculateClaimProcessingCost;

    public function __construct(CalculateClaimProcessingCost $calculateClaimProcessingCost)
    {
        // We need a way to calculate the cost of a single claim
        $this->calculateClaimProcessingCost = $calculateClaimProcessingCost;
    }

    // Main function: Add up the cost for all claims in a batch
    public function handle(array|Collection $claims, Insurer $insurer): float
    {
        $totalCost = 0;
        
        // Go through each claim and add its cost to the total
        foreach ($claims as $claimData) {
            // Make sure we have a Claim object
            $claim = $claimData instanceof Claim ? $claimData : new Claim($claimData);
            $totalCost += $this->calculateClaimProcessingCost->handle($claim, $insurer);
        }
        
        // Return the total cost, rounded to 2 decimal places
        return round($totalCost, 2);
    }
} 