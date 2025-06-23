<?php

namespace App\Actions\Optimization;

use App\Actions\Cost\CalculateBatchProcessingCost;
use App\Models\Claim;
use App\Models\Insurer;
use App\Models\Batch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetOptimizationRecommendations
{
    private CalculateBatchProcessingCost $calculateBatchProcessingCost;

    public function __construct(CalculateBatchProcessingCost $calculateBatchProcessingCost)
    {
        $this->calculateBatchProcessingCost = $calculateBatchProcessingCost;
    }

    public function handle(Insurer $insurer): array
    {
        $unbatchedClaims = Claim::where('insurer_id', $insurer->id)
            ->whereDoesntHave('batches')
            ->with(['provider', 'items'])
            ->get();
        
        if ($unbatchedClaims->isEmpty()) {
            return [
                'total_unbatched_claims' => 0,
                'estimated_total_cost' => 0,
                'average_cost_per_claim' => 0,
                'capacity_utilization' => $this->calculateCapacityUtilization($insurer),
                'analysis' => [
                    'optimization_opportunities' => [],
                ]
            ];
        }
        
        $totalCost = $this->calculateBatchProcessingCost->handle($unbatchedClaims->all(), $insurer);
        $avgCostPerClaim = $totalCost / $unbatchedClaims->count();
        
        $opportunities = [];

        // 1. High-cost specialties
        $specialtyCosts = $unbatchedClaims->groupBy('specialty')->map(function ($claims) use ($insurer) {
            $cost = $this->calculateBatchProcessingCost->handle($claims->all(), $insurer);
            return $cost / $claims->count();
        });

        $avgSpecialtyCost = $specialtyCosts->avg();
        foreach ($specialtyCosts as $specialty => $cost) {
            if ($cost > $avgSpecialtyCost * 1.2) { // 20% above average
                $opportunities[] = [
                    'type' => 'high_cost_specialty',
                    'specialty' => $specialty,
                    'average_cost' => round($cost, 2),
                    'suggestion' => "Review processing for {$specialty} as its average cost per claim is significantly above average."
                ];
            }
        }

        // 2. High-priority volume
        $highPriorityClaimsCount = $unbatchedClaims->where('priority_level', '<=', 2)->count();
        if ($unbatchedClaims->count() > 0 && ($highPriorityClaimsCount / $unbatchedClaims->count()) > 0.3) {
            $opportunities[] = [
                'type' => 'high_priority_volume',
                'count' => $highPriorityClaimsCount,
                'percentage' => round(($highPriorityClaimsCount / $unbatchedClaims->count()) * 100, 2),
                'suggestion' => 'High volume of priority claims. Consider if priorities are assigned correctly.'
            ];
        }
        
        return [
            'total_unbatched_claims' => $unbatchedClaims->count(),
            'estimated_total_cost' => $totalCost,
            'average_cost_per_claim' => round($avgCostPerClaim, 2),
            'capacity_utilization' => $this->calculateCapacityUtilization($insurer),
            'analysis' => [
                'optimization_opportunities' => $opportunities,
            ]
        ];
    }
    
    private function calculateCapacityUtilization(Insurer $insurer): float
    {
        $today = Carbon::today();
        $processedToday = Batch::where('insurer_id', $insurer->id)
            ->where('batch_date', $today->toDateString())
            ->withCount('claims')
            ->get()
            ->sum('claims_count');
        
        if ($insurer->daily_capacity === 0) {
            return 0.0;
        }

        return round(($processedToday / $insurer->daily_capacity) * 100, 2);
    }
} 