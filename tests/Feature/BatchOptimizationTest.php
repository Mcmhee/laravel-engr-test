<?php

namespace Tests\Feature;

use App\Models\Insurer;
use App\Models\Provider;
use App\Models\Claim;
use App\Models\Batch;
use App\Services\BatchOptimizationService;
use App\Services\ProcessingCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BatchOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private Insurer $insurer;
    private BatchOptimizationService $optimizationService;
    private ProcessingCostService $costService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->artisan('db:seed', ['--class' => 'InsurerSeeder']);
        $this->insurer = Insurer::first();
        $this->optimizationService = app(BatchOptimizationService::class);
        $this->costService = app(ProcessingCostService::class);
    }

    public function test_optimization_with_no_unbatched_claims()
    {
        $result = $this->optimizationService->optimizeBatching($this->insurer, Carbon::today()->addDay());

        $this->assertEquals([], $result['batches']);
        $this->assertEquals(0, $result['total_cost']);
        $this->assertEquals('No claims to batch', $result['optimization_notes']);
    }

    public function test_optimization_with_single_claim()
    {
        // Create a provider and claim
        $provider = Provider::create(['name' => 'Test Provider']);
        $claim = Claim::create([
            'provider_id' => $provider->id,
            'insurer_id' => $this->insurer->id,
            'encounter_date' => Carbon::today()->addDay(),
            'submission_date' => Carbon::today()->addDay(),
            'specialty' => 'cardiology',
            'priority_level' => 3,
            'total_amount' => 1000.00
        ]);

        $result = $this->optimizationService->optimizeBatching($this->insurer, Carbon::today()->addDay());

        $this->assertCount(1, $result['batches']);
        $this->assertGreaterThan(0, $result['total_cost']);
        
        $batch = $result['batches'][0];
        $this->assertNotNull($batch);
        $this->assertEquals($this->insurer->id, $batch->insurer_id);
        $this->assertEquals($provider->id, $batch->provider_id);
    }

    public function test_optimization_respects_min_batch_size()
    {
        $provider = Provider::create(['name' => 'Min Batch Provider']);
        
        // Create only 2 claims when minimum is 3
        for ($i = 1; $i <= 2; $i++) {
            Claim::create([
                'provider_id' => $provider->id,
                'insurer_id' => $this->insurer->id,
                'encounter_date' => Carbon::today()->addDay(),
                'submission_date' => Carbon::today()->addDay(),
                'specialty' => 'cardiology',
                'priority_level' => 3,
                'total_amount' => 1000.00
            ]);
        }

        $result = $this->optimizationService->optimizeBatching($this->insurer, Carbon::today()->addDay());

        // Should not create batch due to minimum size constraint
        $this->assertEmpty($result['batches']);
        $this->assertEquals(0, $result['total_cost']);
    }

    public function test_optimization_respects_max_batch_size()
    {
        $provider = Provider::create(['name' => 'Max Batch Provider']);
        
        // Create 20 claims when maximum is 15
        for ($i = 1; $i <= 20; $i++) {
            Claim::create([
                'provider_id' => $provider->id,
                'insurer_id' => $this->insurer->id,
                'encounter_date' => Carbon::today()->addDay(),
                'submission_date' => Carbon::today()->addDay(),
                'specialty' => 'cardiology',
                'priority_level' => 3,
                'total_amount' => 1000.00
            ]);
        }

        $result = $this->optimizationService->optimizeBatching($this->insurer, Carbon::today()->addDay());

        $this->assertCount(1, $result['batches']);
        $batch = $result['batches'][0];
        
        // Should only have 15 claims (max batch size)
        $this->assertEquals(15, $batch->claims()->count());
    }

    public function test_optimization_respects_daily_capacity()
    {
        $provider = Provider::create(['name' => 'Capacity Provider']);
        
        // Create claims exceeding daily capacity
        for ($i = 1; $i <= 60; $i++) { // Daily capacity is 50
            Claim::create([
                'provider_id' => $provider->id,
                'insurer_id' => $this->insurer->id,
                'encounter_date' => Carbon::today()->addDay(),
                'submission_date' => Carbon::today()->addDay(),
                'specialty' => 'cardiology',
                'priority_level' => 3,
                'total_amount' => 1000.00
            ]);
        }

        $result = $this->optimizationService->optimizeBatching($this->insurer, Carbon::today()->addDay());

        $this->assertCount(1, $result['batches']);
        $batch = $result['batches'][0];
        
        // Should only have 50 claims (daily capacity)
        $this->assertEquals(50, $batch->claims()->count());
    }

    public function test_optimization_prioritizes_by_priority_and_cost()
    {
        $provider = Provider::create(['name' => 'Priority Provider']);
        
        // Create claims with different priorities and costs
        $claims = [
            ['priority' => 5, 'amount' => 1000], // Low priority, low cost
            ['priority' => 1, 'amount' => 2000], // High priority, high cost
            ['priority' => 3, 'amount' => 1500], // Medium priority, medium cost
            ['priority' => 2, 'amount' => 3000], // High priority, very high cost
        ];

        foreach ($claims as $claimData) {
            Claim::create([
                'provider_id' => $provider->id,
                'insurer_id' => $this->insurer->id,
                'encounter_date' => Carbon::today()->addDay(),
                'submission_date' => Carbon::today()->addDay(),
                'specialty' => 'cardiology',
                'priority_level' => $claimData['priority'],
                'total_amount' => $claimData['amount']
            ]);
        }

        $result = $this->optimizationService->optimizeBatching($this->insurer, Carbon::today()->addDay());

        $this->assertCount(1, $result['batches']);
        $batch = $result['batches'][0];
        
        // Should include all claims since we have 4 and min is 3, max is 15
        $this->assertEquals(4, $batch->claims()->count());
    }

    public function test_optimization_with_different_specialties()
    {
        $provider = Provider::create(['name' => 'Specialty Provider']);
        
        // Create claims with different specialties
        $specialties = ['cardiology', 'orthopedics', 'neurology', 'oncology'];
        
        foreach ($specialties as $specialty) {
            Claim::create([
                'provider_id' => $provider->id,
                'insurer_id' => $this->insurer->id,
                'encounter_date' => Carbon::today()->addDay(),
                'submission_date' => Carbon::today()->addDay(),
                'specialty' => $specialty,
                'priority_level' => 3,
                'total_amount' => 1000.00
            ]);
        }

        $result = $this->optimizationService->optimizeBatching($this->insurer, Carbon::today()->addDay());

        $this->assertCount(1, $result['batches']);
        $this->assertGreaterThan(0, $result['total_cost']);
    }

    public function test_optimization_recommendations()
    {
        $provider = Provider::create(['name' => 'Recommendation Provider']);
        
        // Create some unbatched claims
        for ($i = 1; $i <= 5; $i++) {
            Claim::create([
                'provider_id' => $provider->id,
                'insurer_id' => $this->insurer->id,
                'encounter_date' => Carbon::today()->addDay(),
                'submission_date' => Carbon::today()->addDay(),
                'specialty' => 'cardiology',
                'priority_level' => 3,
                'total_amount' => 1000.00
            ]);
        }

        $recommendations = $this->optimizationService->getOptimizationRecommendations($this->insurer);

        $this->assertEquals(5, $recommendations['total_unbatched_claims']);
        $this->assertGreaterThan(0, $recommendations['estimated_total_cost']);
        $this->assertGreaterThan(0, $recommendations['average_cost_per_claim']);
        $this->assertArrayHasKey('capacity_utilization', $recommendations);
    }

    public function test_optimization_with_date_preferences()
    {
        // Test with encounter date preference
        $encounterInsurer = Insurer::where('date_preference', 'encounter')->first();
        $provider = Provider::create(['name' => 'Date Preference Provider']);
        
        Claim::create([
            'provider_id' => $provider->id,
            'insurer_id' => $encounterInsurer->id,
            'encounter_date' => Carbon::today()->addDay(),
            'submission_date' => Carbon::today()->addDays(2),
            'specialty' => 'cardiology',
            'priority_level' => 3,
            'total_amount' => 1000.00
        ]);

        $result = $this->optimizationService->optimizeBatching($encounterInsurer, Carbon::today()->addDay());

        $this->assertCount(1, $result['batches']);
        $batch = $result['batches'][0];
        
        // Batch date should be encounter_date - 1 day
        $this->assertEquals(Carbon::today()->toDateString(), $batch->batch_date);
    }

    public function test_cost_calculation_integration()
    {
        $provider = Provider::create(['name' => 'Cost Calc Provider']);
        $claim = Claim::create([
            'provider_id' => $provider->id,
            'insurer_id' => $this->insurer->id,
            'encounter_date' => Carbon::today()->addDay(),
            'submission_date' => Carbon::today()->addDay(),
            'specialty' => 'oncology',
            'priority_level' => 1,
            'total_amount' => 50000
        ]);

        $result = $this->optimizationService->optimizeBatching($this->insurer, Carbon::today()->addDay());

        $this->assertCount(1, $result['batches']);
        $this->assertGreaterThan(0, $result['total_cost']);
        
        // Verify the cost calculation is reasonable
        $expectedCost = $this->costService->calculateClaimProcessingCost($claim, $this->insurer);
        $this->assertEquals($expectedCost, $result['total_cost']);
    }
} 