<?php

namespace App\Actions\Cost;

use App\Models\Claim;
use App\Models\Insurer;
use Illuminate\Support\Carbon;

class CalculateClaimProcessingCost
{
    // This class figures out how much it costs to process a single claim
    public function handle(Claim $claim, Insurer $insurer): float
    {
        $baseCost = 100.0; // Start with a base processing cost
        
        // Adjust the cost based on when in the month the claim was submitted
        $timeMultiplier = $this->calculateTimeOfMonthMultiplier($claim);
        
        // Adjust the cost based on how efficient the specialty is for this insurer
        $specialtyMultiplier = $this->calculateSpecialtyMultiplier($claim, $insurer);
        
        // Adjust the cost based on the claim's priority (urgent claims cost more)
        $priorityMultiplier = $this->calculatePriorityMultiplier($claim);
        
        // Adjust the cost based on how much money the claim is for
        $valueMultiplier = $this->calculateValueMultiplier($claim);
        
        // Multiply all the factors together to get the total cost
        $totalCost = $baseCost * $timeMultiplier * $specialtyMultiplier * $priorityMultiplier * $valueMultiplier;
        
        // Return the final cost, rounded to 2 decimal places
        return round($totalCost, 2);
    }

    // Helper: Make claims more expensive if submitted later in the month
    private function calculateTimeOfMonthMultiplier(Claim $claim): float
    {
        $date = Carbon::parse($claim->submission_date);
        $dayOfMonth = $date->day;
        
        $multiplier = 1.2 + (($dayOfMonth - 1) / 29) * 0.3;
        
        return round($multiplier, 3);
    }

    // Helper: Make claims cheaper if the insurer is efficient at this specialty
    private function calculateSpecialtyMultiplier(Claim $claim, Insurer $insurer): float
    {
        $specialtyPreferences = $insurer->specialty_preferences ?? [];
        $claimSpecialty = strtolower($claim->specialty);
        
        if (isset($specialtyPreferences[$claimSpecialty])) {
            $efficiency = $specialtyPreferences[$claimSpecialty];
            return 2.0 - $efficiency;
        }
        
        // If we don't know, use a default multiplier
        return 1.25;
    }

    // Helper: Higher priority claims cost more to process
    private function calculatePriorityMultiplier(Claim $claim): float
    {
        $priorityMultipliers = [
            1 => 2.0,   // Highest priority
            2 => 1.6,
            3 => 1.3,
            4 => 1.1,
            5 => 1.0,   // Lowest priority
        ];
        
        return $priorityMultipliers[$claim->priority_level] ?? 1.0;
    }

    // Helper: Bigger claims (more money) cost more to process
    private function calculateValueMultiplier(Claim $claim): float
    {
        $value = $claim->total_amount;
        
        if ($value <= 1000) {
            return 1.0;
        } elseif ($value <= 5000) {
            return 1.2;
        } elseif ($value <= 10000) {
            return 1.4;
        } elseif ($value <= 25000) {
            return 1.6;
        } else {
            return 2.0;
        }
    }
} 