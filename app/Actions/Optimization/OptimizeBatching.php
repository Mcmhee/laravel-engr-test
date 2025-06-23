<?php

namespace App\Actions\Optimization;

use App\Actions\Cost\CalculateBatchProcessingCost;
use App\Actions\Cost\CalculateClaimProcessingCost;
use App\Models\Claim;
use App\Models\Insurer;
use App\Models\Batch;
use App\Models\Provider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class OptimizeBatching
{
    // This class figures out the best way to group (batch) claims for an insurer
    private CalculateBatchProcessingCost $calculateBatchProcessingCost;

    public function __construct(CalculateBatchProcessingCost $calculateBatchProcessingCost)
    {
        // We need a way to calculate the total cost of a batch of claims
        $this->calculateBatchProcessingCost = $calculateBatchProcessingCost;
    }

    // Main function: Find the best way to batch claims for a given insurer and date
    public function handle(Insurer $insurer, Carbon $date): array
    {
        // Get all claims for this insurer and date that haven't been batched yet
        $unbatchedClaims = $this->getUnbatchedClaims($insurer, $date);
        
        // If there are no claims to batch, return an empty result
        if ($unbatchedClaims->isEmpty()) {
            return ['batches' => [], 'total_cost' => 0, 'optimization_notes' => ['No claims to batch']];
        }
        
        // Group claims by provider and batch date (so each batch is for one provider and one day)
        $groupedClaims = $this->groupClaimsByProviderAndDate($unbatchedClaims, $insurer);
        
        $optimizedBatches = [];
        $totalCost = 0;
        $optimizationNotes = [];
        
        // If there's only one group and it's too small, force batch creation anyway
        $forceBatchCreation = count($groupedClaims) === 1 && 
                              collect(current($groupedClaims)['claims'])->count() < $insurer->min_batch_size;

        // Go through each group and try to create the best batch(es)
        foreach ($groupedClaims as $groupKey => $claims) {
            $batchResult = $this->optimizeBatchForGroup($claims, $insurer, $groupKey, $forceBatchCreation);
            if (!empty($batchResult['batches'])) {
                $optimizedBatches = array_merge($optimizedBatches, $batchResult['batches']);
            }
            $totalCost += $batchResult['cost'];
            $optimizationNotes[] = $batchResult['notes'];
        }
        
        // Return all the batches created, the total cost, and any notes
        return [
            'batches' => $optimizedBatches,
            'total_cost' => $totalCost,
            'optimization_notes' => $optimizationNotes
        ];
    }

    // Helper: Get all claims for this insurer and date that aren't in a batch yet
    private function getUnbatchedClaims(Insurer $insurer, Carbon $date): Collection
    {
        return Claim::where('insurer_id', $insurer->id)
            ->whereDoesntHave('batches')
            ->where(function ($query) use ($insurer, $date) {
                // Use the insurer's date preference (encounter or submission date)
                if ($insurer->date_preference === 'encounter') {
                    $query->whereDate('encounter_date', $date);
                } else {
                    $query->whereDate('submission_date', $date);
                }
            })
            ->with(['provider', 'items'])
            ->orderBy('priority_level')
            ->orderBy('total_amount', 'desc')
            ->get();
    }

    // Helper: Group claims by provider and batch date
    private function groupClaimsByProviderAndDate(Collection $claims, Insurer $insurer): array
    {
        $grouped = [];
        
        foreach ($claims as $claim) {
            // Decide which date to use for batching
            $date = $insurer->date_preference === 'encounter' 
                ? Carbon::parse($claim->encounter_date)
                : Carbon::parse($claim->submission_date);
            
            // The batch date is always one day before the claim's date
            $batchDate = $date->copy()->subDay()->toDateString();
            $groupKey = $claim->provider_id . '_' . $batchDate;
            
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'provider_id' => $claim->provider_id,
                    'batch_date' => $batchDate,
                    'claims' => collect()
                ];
            }
            
            $grouped[$groupKey]['claims']->push($claim);
        }
        
        return $grouped;
    }

    // Helper: Try to create the best batch(es) for a group of claims
    private function optimizeBatchForGroup(array $claims, Insurer $insurer, string $groupKey, bool $force = false): array
    {
        $claimsCollection = collect($claims['claims']);
        $providerId = $claimsCollection->first()->provider_id;
        $batchDate = $claims['batch_date'] ?? Carbon::now()->subDay()->toDateString();
        
        // Check how many claims have already been processed for this insurer and date
        $dailyProcessedClaims = $this->getDailyProcessedClaimsCount($insurer, $batchDate);
        $availableCapacity = $insurer->daily_capacity - $dailyProcessedClaims;
        
        // If we've hit the daily limit, don't create any more batches
        if ($availableCapacity <= 0) {
            return ['batches' => [], 'cost' => 0, 'notes' => "Daily capacity exceeded for {$batchDate}"];
        }
        
        // Only process as many claims as we have capacity for
        $claimsToProcess = $claimsCollection->take($availableCapacity);
        $createdBatches = [];
        $totalCost = 0;
        
        // Keep creating batches until there are no more claims to process
        while ($claimsToProcess->isNotEmpty()) {
            $optimizedClaims = $this->optimizeBatchSize($claimsToProcess, $insurer, $force);
            
            if ($optimizedClaims->isEmpty()) {
                break; 
            }
            
            // Actually create or update the batch in the database
            $batch = $this->createOrUpdateBatch($insurer, $providerId, $batchDate, $optimizedClaims);
            $createdBatches[] = $batch;
            $totalCost += $this->calculateBatchProcessingCost->handle($optimizedClaims, $insurer);
            
            // Remove the claims we just batched from the list
            $claimsToProcess = $claimsToProcess->diff($optimizedClaims);
            
            // If we're forcing batch creation, only do it once
            if ($force) {
                break;
            }
        }

        // If we couldn't create any batches, explain why
        if (empty($createdBatches)) {
            return ['batches' => [], 'cost' => 0, 'notes' => "No claims meet batch size constraints"];
        }
        
        // Return the batches, total cost, and a note about what happened
        return [
            'batches' => $createdBatches,
            'cost' => $totalCost,
            'notes' => "Created " . count($createdBatches) . " batches with a total of {$claimsCollection->count()} claims, cost: \${$totalCost}"
        ];
    }
    
    // Helper: Decide how many claims to put in each batch, based on insurer rules
    private function optimizeBatchSize(Collection $claims, Insurer $insurer, bool $force = false): Collection
    {
        $minSize = $insurer->min_batch_size;
        $maxSize = $insurer->max_batch_size;
        $totalClaims = $claims->count();
        
        // If we're forcing, just take as many as allowed
        if ($force) {
            return $claims->take($maxSize);
        }

        // If there aren't enough claims for a batch, skip
        if ($totalClaims < $minSize) {
            return collect();
        }
        
        // If the number of claims is within the allowed range, use them all
        if ($totalClaims <= $maxSize) {
            return $claims;
        }
        
        // Otherwise, pick the best claims to include in the batch
        return $this->selectOptimalClaims($claims, $insurer, $maxSize);
    }
    
    // Helper: Pick the best claims for a batch (by priority and cost)
    private function selectOptimalClaims(Collection $claims, Insurer $insurer, int $maxSize): Collection
    {
        $claimsWithCosts = $claims->map(function ($claim) use ($insurer) {
            $cost = app(CalculateClaimProcessingCost::class)->handle($claim, $insurer);
            return [
                'claim' => $claim,
                'cost' => $cost,
                'cost_per_amount' => $cost / $claim->total_amount,
                'priority_score' => (6 - $claim->priority_level) * 10 // Higher priority = higher score
            ];
        });
        
        // Sort by priority first, then by cost efficiency
        $sortedClaims = $claimsWithCosts->sortByDesc('priority_score')
            ->sortBy('cost_per_amount');
        
        return $sortedClaims->take($maxSize)->pluck('claim');
    }
    
    // Helper: Count how many claims have already been processed for this insurer and date
    private function getDailyProcessedClaimsCount(Insurer $insurer, string $batchDate): int
    {
        return Batch::where('insurer_id', $insurer->id)
            ->where('batch_date', $batchDate)
            ->withCount('claims')
            ->get()
            ->sum('claims_count');
    }
    
    // Helper: Create or update a batch in the database
    private function createOrUpdateBatch(Insurer $insurer, int $providerId, string $batchDate, Collection $claims): Batch
    {
        return DB::transaction(function () use ($insurer, $providerId, $batchDate, $claims) {
            $batch = Batch::firstOrCreate(
                [
                    'insurer_id' => $insurer->id,
                    'provider_id' => $providerId,
                    'batch_date' => $batchDate,
                ],
                ['total_cost' => 0]
            );
            
            $claimIds = $claims->pluck('id')->toArray();
            $batch->claims()->attach($claimIds);
            
            $totalAmount = $claims->sum('total_amount');
            $batch->increment('total_cost', $totalAmount);
            
            return $batch->fresh(['claims', 'provider']);
        });
    }
} 