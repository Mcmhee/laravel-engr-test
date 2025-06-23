<?php

namespace Tests\Feature;

use App\Models\Insurer;
use App\Models\Provider;
use App\Models\Claim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OptimizationApiTest extends TestCase
{
    use RefreshDatabase;

    private Insurer $insurer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->artisan('db:seed', ['--class' => 'InsurerSeeder']);
        $this->insurer = Insurer::first();
    }

    public function test_get_optimization_recommendations()
    {
        $response = $this->getJson("/api/insurers/{$this->insurer->id}/optimization-recommendations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message'
            ]);
    }

    public function test_get_optimization_recommendations_with_claims()
    {
        // Create some unbatched claims
        $provider = Provider::create(['name' => 'Test Provider']);
        
        for ($i = 1; $i <= 3; $i++) {
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

        $response = $this->getJson("/api/insurers/{$this->insurer->id}/optimization-recommendations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_unbatched_claims',
                'estimated_total_cost',
                'average_cost_per_claim',
                'capacity_utilization',
                'suggestions'
            ]);

        $data = $response->json();
        $this->assertEquals(3, $data['total_unbatched_claims']);
        $this->assertGreaterThan(0, $data['estimated_total_cost']);
    }

    public function test_optimize_batching_endpoint()
    {
        $response = $this->postJson("/api/insurers/{$this->insurer->id}/optimize-batching");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'result' => [
                    'batches',
                    'total_cost',
                    'optimization_notes'
                ]
            ]);
    }

    public function test_optimize_batching_with_date_parameter()
    {
        $date = Carbon::today()->addDay()->toDateString();
        
        $response = $this->postJson("/api/insurers/{$this->insurer->id}/optimize-batching", [
            'date' => $date
        ]);

        $response->assertStatus(200);
    }

    public function test_get_claim_cost_breakdown()
    {
        // Create a claim first
        $provider = Provider::create(['name' => 'Cost Breakdown Provider']);
        $claim = Claim::create([
            'provider_id' => $provider->id,
            'insurer_id' => $this->insurer->id,
            'encounter_date' => Carbon::today()->addDay(),
            'submission_date' => Carbon::today()->addDay(),
            'specialty' => 'cardiology',
            'priority_level' => 2,
            'total_amount' => 5000.00
        ]);

        $response = $this->getJson("/api/claims/{$claim->id}/cost-breakdown");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'claim_id',
                'provider',
                'specialty',
                'priority_level',
                'total_amount',
                'cost_breakdown' => [
                    'base_cost',
                    'time_multiplier',
                    'specialty_multiplier',
                    'priority_multiplier',
                    'value_multiplier',
                    'total_cost',
                    'breakdown'
                ]
            ]);

        $data = $response->json();
        $this->assertEquals($claim->id, $data['claim_id']);
        $this->assertEquals('cardiology', $data['specialty']);
        $this->assertEquals(2, $data['priority_level']);
        $this->assertEquals(5000.00, $data['total_amount']);
        $this->assertGreaterThan(0, $data['cost_breakdown']['total_cost']);
    }

    public function test_get_cost_analysis()
    {
        $response = $this->getJson("/api/insurers/{$this->insurer->id}/cost-analysis");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'insurer' => [
                    'id',
                    'name',
                    'code'
                ],
                'analysis' => [
                    'total_claims',
                    'total_processing_cost',
                    'average_cost_per_claim',
                    'cost_by_specialty',
                    'cost_by_priority',
                    'cost_by_month',
                    'cost_by_provider',
                    'optimization_opportunities'
                ]
            ]);
    }

    public function test_get_cost_analysis_with_claims()
    {
        // Create claims with different characteristics
        $provider = Provider::create(['name' => 'Analysis Provider']);
        
        $claims = [
            ['specialty' => 'cardiology', 'priority' => 1, 'amount' => 2000],
            ['specialty' => 'orthopedics', 'priority' => 3, 'amount' => 1500],
            ['specialty' => 'neurology', 'priority' => 2, 'amount' => 3000],
            ['specialty' => 'cardiology', 'priority' => 4, 'amount' => 800],
        ];

        foreach ($claims as $claimData) {
            Claim::create([
                'provider_id' => $provider->id,
                'insurer_id' => $this->insurer->id,
                'encounter_date' => Carbon::today()->addDay(),
                'submission_date' => Carbon::today()->addDay(),
                'specialty' => $claimData['specialty'],
                'priority_level' => $claimData['priority'],
                'total_amount' => $claimData['amount']
            ]);
        }

        $response = $this->getJson("/api/insurers/{$this->insurer->id}/cost-analysis");

        $response->assertStatus(200);

        $data = $response->json();
        $analysis = $data['analysis'];

        $this->assertEquals(4, $analysis['total_claims']);
        $this->assertGreaterThan(0, $analysis['total_processing_cost']);
        $this->assertGreaterThan(0, $analysis['average_cost_per_claim']);

        // Check specialty breakdown
        $this->assertArrayHasKey('cardiology', $analysis['cost_by_specialty']);
        $this->assertEquals(2, $analysis['cost_by_specialty']['cardiology']['count']);

        // Check priority breakdown
        $this->assertArrayHasKey(1, $analysis['cost_by_priority']);
        $this->assertEquals(1, $analysis['cost_by_priority'][1]['count']);

        // Check provider breakdown
        $this->assertArrayHasKey('Analysis Provider', $analysis['cost_by_provider']);
        $this->assertEquals(4, $analysis['cost_by_provider']['Analysis Provider']['count']);
    }

    public function test_cost_analysis_with_high_cost_specialty()
    {
        $provider = Provider::create(['name' => 'High Cost Provider']);
        
        // Create baseline claims with a different, more efficient specialty
        for ($i = 1; $i <= 5; $i++) {
            Claim::create([
                'provider_id' => $provider->id,
                'insurer_id' => $this->insurer->id,
                'encounter_date' => Carbon::today()->addDay(),
                'submission_date' => Carbon::today()->addDay(),
                'specialty' => 'cardiology', // 90% efficient (low cost)
                'priority_level' => 3,
                'total_amount' => 1000.00
            ]);
        }

        // Create claims with a specialty that has low efficiency (high cost)
        for ($i = 1; $i <= 5; $i++) {
            Claim::create([
                'provider_id' => $provider->id,
                'insurer_id' => $this->insurer->id,
                'encounter_date' => Carbon::today()->addDay(),
                'submission_date' => Carbon::today()->addDay(),
                'specialty' => 'oncology', // 60% efficient (high cost)
                'priority_level' => 3,
                'total_amount' => 1000.00
            ]);
        }

        $response = $this->getJson("/api/insurers/{$this->insurer->id}/cost-analysis");

        $response->assertStatus(200);

        $data = $response->json();
        $opportunities = $data['analysis']['optimization_opportunities'];

        // Should identify oncology as a high-cost specialty
        $oncologyOpportunity = collect($opportunities)->firstWhere('type', 'high_cost_specialty');
        $this->assertNotNull($oncologyOpportunity);
        $this->assertEquals('oncology', $oncologyOpportunity['specialty']);
    }

    public function test_cost_analysis_with_high_priority_volume()
    {
        $provider = Provider::create(['name' => 'High Priority Provider']);
        
        // Create many high-priority claims
        for ($i = 1; $i <= 15; $i++) {
            Claim::create([
                'provider_id' => $provider->id,
                'insurer_id' => $this->insurer->id,
                'encounter_date' => Carbon::today()->addDay(),
                'submission_date' => Carbon::today()->addDay(),
                'specialty' => 'cardiology',
                'priority_level' => 1, // High priority
                'total_amount' => 1000.00
            ]);
        }

        $response = $this->getJson("/api/insurers/{$this->insurer->id}/cost-analysis");

        $response->assertStatus(200);

        $data = $response->json();
        $opportunities = $data['analysis']['optimization_opportunities'];

        // Should identify high priority volume
        $priorityOpportunity = collect($opportunities)->firstWhere('type', 'high_priority_volume');
        $this->assertNotNull($priorityOpportunity);
        $this->assertEquals(1, $priorityOpportunity['priority']);
        $this->assertEquals(15, $priorityOpportunity['count']);
    }

    public function test_invalid_insurer_id()
    {
        $response = $this->getJson("/api/insurers/999/optimization-recommendations");
        $response->assertStatus(404);

        $response = $this->getJson("/api/insurers/999/cost-analysis");
        $response->assertStatus(404);

        $response = $this->postJson("/api/insurers/999/optimize-batching");
        $response->assertStatus(404);
    }

    public function test_invalid_claim_id()
    {
        $response = $this->getJson("/api/claims/999/cost-breakdown");
        $response->assertStatus(404);
    }
} 