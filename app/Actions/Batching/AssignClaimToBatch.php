<?php
namespace App\Actions\Batching;

use App\Actions\Optimization\OptimizeBatching;
use App\Models\Claim;
use App\Models\Batch;
use App\Models\Insurer;
use App\Models\Provider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Mail\BatchNotificationMail;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\Log;

class AssignClaimToBatch
{
    // This class is responsible for putting a claim into the right batch
    private OptimizeBatching $optimizationAction;

    public function __construct(
        OptimizeBatching $optimizationAction
    ) {
        // We need the optimizer to help us find the best batch
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
        // Figure out the date to use for batching (usually submission date)
        $date = Carbon::parse($claim->submission_date);
        $insurer = $claim->insurer;

        // If the claim doesn't have an insurer, log an error and stop
        if (!$insurer) {
            Log::error("Claim {$claim->id} is missing an insurer.");
            return;
        }

        // If this claim is already in a batch, do nothing
        if ($claim->batches()->exists()) {
            return;
        }

        // Try to find the best batch for this claim using the optimizer
        $optimizationResult = $this->optimizationAction->handle($insurer, $date);
        
        // If the optimizer couldn't find a batch, use a simple fallback method
        if (empty($optimizationResult['batches'])) {
            $this->executeSimpleBatching($claim, $insurer, $claim->provider, $date->copy()->subDay()->toDateString());
            return;
        }
        
        // For each batch created, send a notification email to the insurer
        foreach ($optimizationResult['batches'] as $batch) {
            if ($batch && $insurer->email) {
                Mail::to($insurer->email)->send(new BatchNotificationMail($batch));
            }
        }
        
        // Log what happened for debugging and tracking
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
        // Find or create a batch for this provider, insurer, and date
        $batch = $provider->batches()->firstOrCreate(
            [
                'insurer_id' => $insurer->id,
                'batch_date' => $batchDate,
            ],
            ['total_cost' => 0]
        );

        // Attach the claim to the batch (don't duplicate if already attached)
        $claim->batches()->syncWithoutDetaching([$batch->id]);

        // Send a notification email to the insurer
        if ($insurer->email) {
            Mail::to($insurer->email)->send(new BatchNotificationMail($batch));
        }
    }


}
