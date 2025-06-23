<?php

namespace App\Actions\Cost;

use App\Models\Claim;
use App\Models\Insurer;
use Illuminate\Support\Carbon;

class CalculateClaimProcessingCost
{
    public function handle(Claim $claim, Insurer $insurer): float
    {
        $baseCost = 100.0; // Base processing cost
        
        // Apply time of month multiplier
        $timeMultiplier = $this->calculateTimeOfMonthMultiplier($claim);
        
        // Apply specialty efficiency multiplier
        $specialtyMultiplier = $this->calculateSpecialtyMultiplier($claim, $insurer);
        
        // Apply priority level multiplier
        $priorityMultiplier = $this->calculatePriorityMultiplier($claim);
        
        // Apply monetary value multiplier
        $valueMultiplier = $this->calculateValueMultiplier($claim);
        
        $totalCost = $baseCost * $timeMultiplier * $specialtyMultiplier * $priorityMultiplier * $valueMultiplier;
        
        return round($totalCost, 2);
    }

    private function calculateTimeOfMonthMultiplier(Claim $claim): float
    {
        $date = Carbon::parse($claim->submission_date);
        $dayOfMonth = $date->day;
        
        $multiplier = 1.2 + (($dayOfMonth - 1) / 29) * 0.3;
        
        return round($multiplier, 3);
    }

    private function calculateSpecialtyMultiplier(Claim $claim, Insurer $insurer): float
    {
        $specialtyPreferences = $insurer->specialty_preferences ?? [];
        $claimSpecialty = strtolower($claim->specialty);
        
        if (isset($specialtyPreferences[$claimSpecialty])) {
            $efficiency = $specialtyPreferences[$claimSpecialty];
            return 2.0 - $efficiency;
        }
        
        return 1.25;
    }

    private function calculatePriorityMultiplier(Claim $claim): float
    {
        $priorityMultipliers = [
            1 => 2.0,
            2 => 1.6,
            3 => 1.3,
            4 => 1.1,
            5 => 1.0,
        ];
        
        return $priorityMultipliers[$claim->priority_level] ?? 1.0;
    }

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