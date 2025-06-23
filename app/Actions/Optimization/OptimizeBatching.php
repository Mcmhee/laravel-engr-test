<?php

namespace App\Actions\Optimization;

use App\Actions\Cost\CalculateBatchProcessingCost;
use App\Models\Claim;
use App\Models\Insurer;
use App\Models\Batch;
use App\Models\Provider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class OptimizeBatching
{
    private CalculateBatchProcessingCost $calculateBatchProcessingCost;

    public function __construct(CalculateBatchProcessingCost $calculateBatchProcessingCost)
    {
        $this->calculateBatchProcessingCost = $calculateBatchProcessingCost;
    }

    public function handle(Insurer $insurer, Carbon $date): array
    {
        $unbatchedClaims = $this->getUnbatchedClaims($insurer, $date);
        
        if ($unbatchedClaims->isEmpty()) {
            return ['batches' => [], 'total_cost' => 0, 'optimization_notes' => 'No claims to batch'];
        }
        
        $groupedClaims = $this->groupClaimsByProviderAndDate($unbatchedClaims, $insurer);
        
        $optimizedBatches = [];
        $totalCost = 0;
        $optimizationNotes = [];
        
        $forceBatchCreation = count($groupedClaims) === 1 && 
                              collect(current($groupedClaims)['claims'])->count() < $insurer->min_batch_size;

        foreach ($groupedClaims as $groupKey => $claims) {
            $batchResult = $this->optimizeBatchForGroup($claims, $insurer, $groupKey, $forceBatchCreation);
            if (!empty($batchResult['batches'])) {
                $optimizedBatches = array_merge($optimizedBatches, $batchResult['batches']);
            }
            $totalCost += $batchResult['cost'];
            $optimizationNotes[] = $batchResult['notes'];
        }
        
        return [
            'batches' => $optimizedBatches,
            'total_cost' => $totalCost,
            'optimization_notes' => $optimizationNotes
        ];
    }

    private function getUnbatchedClaims(Insurer $insurer, Carbon $date): Collection
    {
        return Claim::where('insurer_id', $insurer->id)
            ->whereDoesntHave('batches')
            ->where(function ($query) use ($insurer, $date) {
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

    private function groupClaimsByProviderAndDate(Collection $claims, Insurer $insurer): array
    {
        $grouped = [];
        
        foreach ($claims as $claim) {
            $date = $insurer->date_preference === 'encounter' 
                ? Carbon::parse($claim->encounter_date)
                : Carbon::parse($claim->submission_date);
            
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

    private function optimizeBatchForGroup(array $claims, Insurer $insurer, string $groupKey, bool $force = false): array
    {
        $claimsCollection = collect($claims['claims']);
        $providerId = $claimsCollection->first()->provider_id;
        $batchDate = $claims['batch_date'] ?? Carbon::now()->subDay()->toDateString();
        
        $dailyProcessedClaims = $this->getDailyProcessedClaimsCount($insurer, $batchDate);
        $availableCapacity = $insurer->daily_capacity - $dailyProcessedClaims;
        
        if ($availableCapacity <= 0) {
            return ['batches' => [], 'cost' => 0, 'notes' => "Daily capacity exceeded for {$batchDate}"];
        }
        
        $claimsToProcess = $claimsCollection->take($availableCapacity);
        $createdBatches = [];
        $totalCost = 0;
        
        while ($claimsToProcess->isNotEmpty()) {
            $optimizedClaims = $this->optimizeBatchSize($claimsToProcess, $insurer, $force);
            
            if ($optimizedClaims->isEmpty()) {
                break; 
            }
            
            $batch = $this->createOrUpdateBatch($insurer, $providerId, $batchDate, $optimizedClaims);
            $createdBatches[] = $batch;
            $totalCost += $this->calculateBatchProcessingCost->handle($optimizedClaims, $insurer);
            
            $claimsToProcess = $claimsToProcess->diff($optimizedClaims);
            
            if ($force) {
                break;
            }
        }

        if (empty($createdBatches)) {
            return ['batches' => [], 'cost' => 0, 'notes' => "No claims meet batch size constraints"];
        }
        
        return [
            'batches' => $createdBatches,
            'cost' => $totalCost,
            'notes' => "Created " . count($createdBatches) . " batches with a total of {$claimsCollection->count()} claims, cost: \${$totalCost}"
        ];
    }
    
    private function optimizeBatchSize(Collection $claims, Insurer $insurer, bool $force = false): Collection
    {
        $minSize = $insurer->min_batch_size;
        $maxSize = $insurer->max_batch_size;
        $totalClaims = $claims->count();
        
        if ($force) {
            return $claims->take($maxSize);
        }

        if ($totalClaims < $minSize) {
            return collect();
        }
        
        if ($totalClaims <= $maxSize) {
            return $claims;
        }
        
        return $this->selectOptimalClaims($claims, $insurer, $maxSize);
    }
    
    private function selectOptimalClaims(Collection $claims, Insurer $insurer, int $maxSize): Collection
    {
        $claimsWithCosts = $claims->map(function ($claim) use ($insurer) {
            $cost = app(CalculateClaimProcessingCost::class)->handle($claim, $insurer);
            return [
                'claim' => $claim,
                'cost' => $cost,
                'cost_per_amount' => $cost / $claim->total_amount,
                'priority_score' => (6 - $claim->priority_level) * 10
            ];
        });
        
        $sortedClaims = $claimsWithCosts->sortByDesc('priority_score')
            ->sortBy('cost_per_amount');
        
        return $sortedClaims->take($maxSize)->pluck('claim');
    }
    
    private function getDailyProcessedClaimsCount(Insurer $insurer, string $batchDate): int
    {
        return Batch::where('insurer_id', $insurer->id)
            ->where('batch_date', $batchDate)
            ->withCount('claims')
            ->get()
            ->sum('claims_count');
    }
    
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