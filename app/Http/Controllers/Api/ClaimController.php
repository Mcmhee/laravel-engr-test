<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Provider;
use App\Models\Insurer;
use App\Models\Claim;
use App\Actions\Batching\AssignClaimToBatch;

class ClaimController extends Controller
{
  

    //
    public function submit(Request $request)
    {
        $data = $request->validate([
            'provider_name' => 'required|string',
            'insurer_code' => 'required|string|exists:insurers,code',
            'encounter_date' => 'required|date',
            'submission_date' => 'required|date',
            'specialty' => 'required|string',
            'priority_level' => 'required|integer|min:1|max:5',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            // Provider (create if not exists)
            $provider = Provider::firstOrCreate(['name' => $data['provider_name']]);

            // Insurer
            $insurer = Insurer::where('code', $data['insurer_code'])->firstOrFail();

            // Calculate total
            $total = collect($data['items'])->sum(fn ($item) => $item['unit_price'] * $item['quantity']);

            // Create claim
            $claim = $provider->claims()->create([
                'insurer_id' => $insurer->id,
                'encounter_date' => $data['encounter_date'],
                'submission_date' => $data['submission_date'],
                'specialty' => $data['specialty'],
                'priority_level' => $data['priority_level'],
                'total_amount' => $total,
            ]);

            // Save claim items
            foreach ($data['items'] as $item) {
                $claim->items()->create([
                    'name' => $item['name'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['unit_price'] * $item['quantity'],
                ]);
            }

            //   Assign claim to batch
            app(AssignClaimToBatch::class)->execute($claim);

            DB::commit();

            return response()->json(['message' => 'Claim submitted successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }


}
