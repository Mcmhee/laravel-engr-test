<?php

namespace App\Http\Controllers\Api;

use App\Actions\Analysis\GetCostAnalysis;
use App\Actions\Cost\CalculateClaimProcessingCost;
use App\Actions\Optimization\GetOptimizationRecommendations;
use App\Actions\Optimization\OptimizeBatching;
use App\Http\Controllers\Controller;
use App\Models\Insurer;
use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OptimizationController extends Controller
{
    public function getRecommendations($id, GetOptimizationRecommendations $getOptimizationRecommendations)
    {
        $insurer = Insurer::findOrFail($id);
        $recommendations = $getOptimizationRecommendations->handle($insurer);

        return response()->json($recommendations);
    }

    public function optimizeBatching(Request $request, $id, OptimizeBatching $optimizeBatching)
    {
        $insurer = Insurer::findOrFail($id);
        
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $request->input('date') 
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $result = $optimizeBatching->handle($insurer, $date);

        return response()->json([
            'message' => 'Optimization completed successfully',
            'result' => $result
        ]);
    }

    public function getClaimCostBreakdown($id, CalculateClaimProcessingCost $calculateClaimProcessingCost)
    {
        $claim = Claim::with(['insurer', 'provider', 'items'])->findOrFail($id);
        
        // Note: The original service had a dedicated 'getClaimCostBreakdown' method.
        // For this refactoring, we'll build the response here. A dedicated action could also be created.
        $totalCost = $calculateClaimProcessingCost->handle($claim, $claim->insurer);

        return response()->json([
            'claim_id' => $claim->id,
            'provider' => $claim->provider->name,
            'specialty' => $claim->specialty,
            'priority_level' => $claim->priority_level,
            'total_amount' => $claim->total_amount,
            'total_processing_cost' => $totalCost
        ]);
    }

    public function getCostAnalysis($id, GetCostAnalysis $getCostAnalysis)
    {
        $insurer = Insurer::findOrFail($id);
        $analysis = $getCostAnalysis->handle($insurer);

        return response()->json([
            'insurer' => [
                'id' => $insurer->id,
                'name' => $insurer->name,
                'code' => $insurer->code
            ],
            'analysis' => $analysis
        ]);
    }
} 