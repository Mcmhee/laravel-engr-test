<?php

namespace App\Actions\Analysis;

use App\Actions\Cost\CalculateClaimProcessingCost;
use App\Models\Claim;
use App\Models\Insurer;
use Illuminate\Support\Carbon;

class GetCostAnalysis
{
    // This class helps analyze the cost of processing claims for an insurer
    private CalculateClaimProcessingCost $calculateClaimProcessingCost;

    public function __construct(CalculateClaimProcessingCost $calculateClaimProcessingCost)
    {
        // We need a way to calculate the cost of a single claim
        $this->calculateClaimProcessingCost = $calculateClaimProcessingCost;
    }

    // Main function: Get a summary of costs for all claims of an insurer
    public function handle(Insurer $insurer): array
    {
        // Get all claims for this insurer, including related provider and items
        $claims = Claim::where('insurer_id', $insurer->id)
            ->with(['provider', 'items'])
            ->get();

        // If there are no claims, return an empty summary
        if ($claims->isEmpty()) {
            return $this->emptyAnalysisPayload();
        }

        // We'll keep track of total cost and breakdowns by specialty, priority, month, and provider
        $totalCost = 0;
        $costBySpecialty = [];
        $costByPriority = [];
        $costByMonth = [];
        $costByProvider = [];

        // Go through each claim and calculate its processing cost
        foreach ($claims as $claim) {
            $cost = $this->calculateClaimProcessingCost->handle($claim, $insurer);
            $totalCost += $cost;

            // Group costs by specialty, priority, month, and provider for analysis
            $this->groupCost($costBySpecialty, $claim->specialty, $cost);
            $this->groupCost($costByPriority, $claim->priority_level, $cost);
            $this->groupCost($costByMonth, Carbon::parse($claim->submission_date)->format('Y-m'), $cost);
            $this->groupCost($costByProvider, $claim->provider->name, $cost);
        }

        // Calculate the average cost per claim
        $avgCostPerClaim = $totalCost / $claims->count();

        // Format the breakdowns for easier reading
        $formattedSpecialty = $this->formatCostAnalysis($costBySpecialty, $totalCost);
        $formattedPriority = $this->formatCostAnalysis($costByPriority, $totalCost);

        // Return a detailed summary of all the cost analysis
        return [
            'total_claims' => $claims->count(),
            'total_processing_cost' => round($totalCost, 2),
            'average_cost_per_claim' => round($avgCostPerClaim, 2),
            'cost_by_specialty' => $formattedSpecialty,
            'cost_by_priority' => $formattedPriority,
            'cost_by_month' => $this->formatCostAnalysis($costByMonth, $totalCost),
            'cost_by_provider' => $this->formatCostAnalysis($costByProvider, $totalCost),
            'optimization_opportunities' => $this->identifyOptimizationOpportunities($formattedSpecialty, $formattedPriority, $avgCostPerClaim)
        ];
    }

    // Helper: Add cost to a group (e.g., by specialty or priority)
    private function groupCost(array &$group, string|int $key, float $cost): void
    {
        if (!isset($group[$key])) {
            $group[$key] = ['count' => 0, 'total_cost' => 0];
        }
        $group[$key]['count']++;
        $group[$key]['total_cost'] += $cost;
    }

    // Helper: Format the grouped cost data for easier reading
    private function formatCostAnalysis(array $data, float $totalCost): array
    {
        $formatted = [];
        if ($totalCost == 0) return $formatted;

        foreach ($data as $key => $values) {
            $percentage = ($values['total_cost'] / $totalCost) * 100;
            $average = $values['total_cost'] / $values['count'];
            
            $formatted[$key] = [
                'count' => $values['count'],
                'total_cost' => round($values['total_cost'], 2),
                'percentage' => round($percentage, 2),
                'average_cost' => round($average, 2)
            ];
        }

        return $formatted;
    }

    // Helper: Find areas where costs are unusually high and suggest improvements
    private function identifyOptimizationOpportunities(array $costBySpecialty, array $costByPriority, float $avgCostPerClaim): array
    {
        $opportunities = [];

        // If a specialty is much more expensive than average, flag it
        foreach ($costBySpecialty as $specialty => $data) {
            if ($data['average_cost'] > $avgCostPerClaim * 1.5) {
                $opportunities[] = [
                    'type' => 'high_cost_specialty',
                    'specialty' => $specialty,
                    'average_cost' => $data['average_cost'],
                    'recommendation' => "Consider optimizing processing for {$specialty} claims"
                ];
            }
        }

        // If there are a lot of high-priority claims, suggest reviewing them
        foreach ($costByPriority as $priority => $data) {
            if ($priority <= 2 && $data['count'] > 10) {
                $opportunities[] = [
                    'type' => 'high_priority_volume',
                    'priority' => $priority,
                    'count' => $data['count'],
                    'recommendation' => "High volume of priority {$priority} claims. Consider reviewing priority assignment."
                ];
            }
        }

        return $opportunities;
    }

    // Helper: Return an empty summary if there are no claims
    private function emptyAnalysisPayload(): array
    {
        return [
            'total_claims' => 0,
            'total_processing_cost' => 0,
            'average_cost_per_claim' => 0,
            'cost_by_specialty' => [],
            'cost_by_priority' => [],
            'cost_by_month' => [],
            'cost_by_provider' => [],
            'optimization_opportunities' => []
        ];
    }
} 