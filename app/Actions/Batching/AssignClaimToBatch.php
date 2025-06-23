<?php
namespace App\Actions\Batching;

use App\Models\Claim;
use App\Models\Batch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Mail\BatchNotificationMail;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\Log;

class AssignClaimToBatch
{

    /**
     * Execute the action to assign a claim to a batch.
     *
     * The batch must:
     * - Match the insurer's batching preference
     * - Belong to the same provider
     * - Be dated correctly (see batching rule)
     * - Be reused if it already exists (no duplicates)
     *
     * @param Claim $claim
     * @return void
     */
    public function execute(Claim $claim): void
    {
        if ($claim->batches()->exists()) {
            // Claim is already in a batch, decide on what to do.
            // For now, we'll just return.
            return;
        }

        $insurer = $claim->insurer;
        $provider = $claim->provider;

        $date = $insurer->date_preference === 'encounter'
            ? Carbon::parse($claim->encounter_date)
            : Carbon::parse($claim->submission_date);

        // set batch date to the day before the claim's date
        $batchDate = $date->copy()->subDay()->toDateString();

        try {
            DB::transaction(function () use ($claim, $insurer, $provider, $batchDate) {
                // Find or create batch
                $batch = Batch::firstOrCreate(
                    [
                        'insurer_id' => $insurer->id,
                        'provider_id' => $provider->id,
                        'batch_date' => $batchDate,
                    ],
                    ['total_cost' => 0]
                );

                // Link claim to batch
                $batch->claims()->attach($claim->id);

                // Update batch cost atomically
                $batch->increment('total_cost', $claim->total_amount);

                if ($insurer->email) {
                    Mail::to($insurer->email)->send(new BatchNotificationMail($batch));
                }
            });
        } catch (Exception $e) {
            Log::error('Failed to assign claim to batch: ' . $e->getMessage(), [
                'claim_id' => $claim->id,
                'insurer_id' => $insurer->id ?? null,
                'provider_id' => $provider->id ?? null,
            ]);
    
        }
    }
}
