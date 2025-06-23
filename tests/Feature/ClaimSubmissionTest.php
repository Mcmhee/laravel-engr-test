<?php

namespace Tests\Feature;

use App\Models\Insurer;
use App\Models\Provider;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\Batch;
use App\Services\ProcessingCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Insurer $insurer;
    private ProcessingCostService $costService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed insurers
        $this->artisan('db:seed', ['--class' => 'InsurerSeeder']);
        $this->insurer = Insurer::first();
        $this->costService = app(ProcessingCostService::class);
    }

    public function test_claim_submission_with_valid_data()
    {
        $claimData = [
            'provider_name' => 'Test Hospital',
            'insurer_code' => $this->insurer->code,
            'encounter_date' => '2024-01-15',
            'submission_date' => '2024-01-16',
            'specialty' => 'Cardiology',
            'priority_level' => 3,
            'items' => [
                [
                    'name' => 'Consultation Fee',
                    'unit_price' => 150.00,
                    'quantity' => 1
                ],
                [
                    'name' => 'ECG Test',
                    'unit_price' => 75.00,
                    'quantity' => 2
                ]
            ]
        ];

        $response = $this->postJson('/api/claims', $claimData);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Claim submitted successfully']);

        // Verify claim was created
        $this->assertDatabaseHas('claims', [
            'provider_id' => Provider::where('name', 'Test Hospital')->first()->id,
            'insurer_id' => $this->insurer->id,
            'specialty' => 'Cardiology',
            'priority_level' => 3,
            'total_amount' => 300.00 // 150 + (75 * 2)
        ]);

        // Verify claim items were created
        $claim = Claim::where('specialty', 'Cardiology')->first();
        $this->assertDatabaseHas('claim_items', [
            'claim_id' => $claim->id,
            'name' => 'Consultation Fee',
            'unit_price' => 150.00,
            'quantity' => 1,
            'subtotal' => 150.00
        ]);

        // Verify batch was created
        $this->assertDatabaseHas('batches', [
            'insurer_id' => $this->insurer->id,
            'provider_id' => $claim->provider_id,
            'batch_date' => '2024-01-14' // encounter_date - 1 day
        ]);
    }

    public function test_claim_submission_validation_errors()
    {
        $invalidData = [
            'provider_name' => '', // Required
            'insurer_code' => 'INVALID-CODE', // Doesn't exist
            'encounter_date' => 'invalid-date', // Invalid date
            'submission_date' => '2024-01-16',
            'specialty' => 'Cardiology',
            'priority_level' => 6, // Invalid priority (max 5)
            'items' => [] // Empty items array
        ];

        $response = $this->postJson('/api/claims', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'provider_name',
                'insurer_code',
                'encounter_date',
                'priority_level',
                'items'
            ]);
    }

    public function test_claim_submission_with_multiple_items()
    {
        $claimData = [
            'provider_name' => 'Multi-Service Clinic',
            'insurer_code' => $this->insurer->code,
            'encounter_date' => '2024-01-20',
            'submission_date' => '2024-01-21',
            'specialty' => 'General Medicine',
            'priority_level' => 2,
            'items' => [
                [
                    'name' => 'Initial Consultation',
                    'unit_price' => 200.00,
                    'quantity' => 1
                ],
                [
                    'name' => 'Blood Test',
                    'unit_price' => 50.00,
                    'quantity' => 3
                ],
                [
                    'name' => 'X-Ray',
                    'unit_price' => 300.00,
                    'quantity' => 1
                ]
            ]
        ];

        $response = $this->postJson('/api/claims', $claimData);

        $response->assertStatus(200);

        $claim = Claim::where('provider_id', Provider::where('name', 'Multi-Service Clinic')->first()->id)->first();
        
        // Verify total amount calculation
        $expectedTotal = 200 + (50 * 3) + 300; // 650
        $this->assertEquals($expectedTotal, $claim->total_amount);

        // Verify all items were created
        $this->assertEquals(3, $claim->items()->count());
    }

    public function test_processing_cost_calculation()
    {
        // Create a claim with known parameters
        $provider = Provider::create(['name' => 'Cost Test Hospital']);
        
        $claim = Claim::create([
            'provider_id' => $provider->id,
            'insurer_id' => $this->insurer->id,
            'encounter_date' => '2024-01-15',
            'submission_date' => '2024-01-16', // Day 16 of month
            'specialty' => 'cardiology', // 80% efficient for this insurer
            'priority_level' => 2, // 1.6x multiplier
            'total_amount' => 5000.00 // 1.2x multiplier
        ]);

        $cost = $this->costService->calculateClaimProcessingCost($claim, $this->insurer);
        
        // Expected calculation:
        // Base cost: 100
        // Time multiplier (day 16): 1.2 + ((16-1)/29) * 0.3 = 1.355
        // Specialty multiplier (cardiology 80% efficient): 2.0 - 0.8 = 1.2
        // Priority multiplier (level 2): 1.6
        // Value multiplier (5000): 1.2
        // Total: 100 * 1.355 * 1.2 * 1.6 * 1.2 = 312.19
        
        $this->assertGreaterThan(300, $cost);
        $this->assertLessThan(320, $cost);
    }

    public function test_batch_creation_with_date_preference()
    {
        // Test with encounter date preference
        $encounterInsurer = Insurer::where('date_preference', 'encounter')->first();
        
        $claimData = [
            'provider_name' => 'Encounter Test Hospital',
            'insurer_code' => $encounterInsurer->code,
            'encounter_date' => '2024-01-25',
            'submission_date' => '2024-01-26',
            'specialty' => 'Orthopedics',
            'priority_level' => 4,
            'items' => [
                [
                    'name' => 'Consultation',
                    'unit_price' => 100.00,
                    'quantity' => 1
                ]
            ]
        ];

        $this->postJson('/api/claims', $claimData);

        $claim = Claim::where('specialty', 'Orthopedics')->first();
        $batch = Batch::where('insurer_id', $encounterInsurer->id)->first();

        // Batch date should be encounter_date - 1 day
        $this->assertEquals('2024-01-24', $batch->batch_date);
    }

    public function test_duplicate_claim_batching_prevention()
    {
        $claimData = [
            'provider_name' => 'Duplicate Test Hospital',
            'insurer_code' => $this->insurer->code,
            'encounter_date' => '2024-01-30',
            'submission_date' => '2024-01-31',
            'specialty' => 'Neurology',
            'priority_level' => 1,
            'items' => [
                [
                    'name' => 'Test Item',
                    'unit_price' => 100.00,
                    'quantity' => 1
                ]
            ]
        ];

        // Submit claim twice
        $this->postJson('/api/claims', $claimData);
        $this->postJson('/api/claims', $claimData);

        $claim = Claim::where('specialty', 'Neurology')->first();
        
        // Claim should only be in one batch
        $this->assertEquals(1, $claim->batches()->count());
    }

    public function test_claim_items_subtotal_calculation()
    {
        $claimData = [
            'provider_name' => 'Subtotal Test Hospital',
            'insurer_code' => $this->insurer->code,
            'encounter_date' => '2024-02-01',
            'submission_date' => '2024-02-02',
            'specialty' => 'Pediatrics',
            'priority_level' => 5,
            'items' => [
                [
                    'name' => 'Medication A',
                    'unit_price' => 25.50,
                    'quantity' => 4
                ],
                [
                    'name' => 'Medication B',
                    'unit_price' => 12.75,
                    'quantity' => 2
                ]
            ]
        ];

        $this->postJson('/api/claims', $claimData);

        $claim = Claim::where('specialty', 'Pediatrics')->first();
        $items = $claim->items;

        // Verify subtotal calculations
        $this->assertEquals(102.00, $items->where('name', 'Medication A')->first()->subtotal); // 25.50 * 4
        $this->assertEquals(25.50, $items->where('name', 'Medication B')->first()->subtotal); // 12.75 * 2
    }
} 