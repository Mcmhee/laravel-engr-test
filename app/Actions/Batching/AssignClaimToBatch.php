<?php
namespace App\Actions\Batching;

use App\Actions\Optimization\OptimizeBatching;
use App\Models\Claim;
use App\Models\Batch;
use App\Models\Insurer;
use App\Models\Provider;
use App\Services\BatchOptimizationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Mail\BatchNotificationMail;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\Log;

class AssignClaimToBatch
{
    private BatchOptimizationService $optimizationService;
    private OptimizeBatching $optimizationAction;

    public function __construct(
        BatchOptimizationService $optimizationService,
        OptimizeBatching $optimizationAction
    ) {
        $this->optimizationService = $optimizationService;
        $this->optimizationAction = $optimizationAction;
    }

    /**
     * Execute the action to assign a claim to a batch.
     *
     * The batch must:
     * - Match the insurer's batching preference
     * - Belong to the same provider
     * - Be dated correctly (see batching rule)
     * - Be reused if it already exists (no duplicates)
     * - Optimize for minimum processing costs
     *
     * @param Claim $claim
     * @return void
     */
    public function handle(Claim $claim): void
    {
        $date = Carbon::parse($claim->submission_date);
        $insurer = $claim->insurer;

        if (!$insurer) {
            Log::error("Claim {$claim->id} is missing an insurer.");
            return;
        }

        if ($claim->batches()->exists()) {
            return;
        }

        $optimizationResult = $this->optimizationAction->handle($insurer, $date);
        
        if (empty($optimizationResult['batches'])) {
            $this->executeSimpleBatching($claim, $insurer, $claim->provider, $date->copy()->subDay()->toDateString());
            return;
        }
        
        foreach ($optimizationResult['batches'] as $batch) {
            if ($batch && $insurer->email) {
                Mail::to($insurer->email)->send(new BatchNotificationMail($batch));
            }
        }
        
        Log::info('Optimized batching completed', [
            'insurer_id' => $insurer->id,
            'batches_created' => count($optimizationResult['batches']),
            'total_cost' => $optimizationResult['total_cost'],
            'notes' => $optimizationResult['optimization_notes']
        ]);
    }

    /**
     * Execute simple batching (original logic)
     *
     * @param Claim $claim
     * @param Insurer $insurer
     * @param Provider $provider
     * @param string $batchDate
     * @return void
     */
    private function executeSimpleBatching(Claim $claim, Insurer $insurer, Provider $provider, string $batchDate): void
    {
        $batch = $provider->batches()->firstOrCreate(
            [
                'insurer_id' => $insurer->id,
                'batch_date' => $batchDate,
            ],
            ['total_cost' => 0]
        );

        $claim->batches()->syncWithoutDetaching([$batch->id]);

        if ($insurer->email) {
            Mail::to($insurer->email)->send(new BatchNotificationMail($batch));
        }
    }

    /**
     * Determine if optimization should be used for this insurer
     *
     * @param Insurer $insurer
     * @return bool
     */
    private function shouldUseOptimization(Insurer $insurer): bool
    {
        // Use optimization if insurer has specific constraints set
        return $insurer->min_batch_size > 1 || 
               $insurer->max_batch_size < 100 || 
               $insurer->daily_capacity < 1000 ||
               !empty($insurer->specialty_preferences);
    }
}
