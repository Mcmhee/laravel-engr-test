<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsurerSeeder extends Seeder
{
    private $insurers = [
        [
            'name' => 'Insurer A',
            'code' => 'INS-A',
            'email' => 'insurer.a@example.com',
            'specialty_preferences' => [
                'cardiology' => 0.8,    // 80% efficient
                'orthopedics' => 0.9,   // 90% efficient
                'neurology' => 0.7,     // 70% efficient
                'oncology' => 0.6,      // 60% efficient
                'general medicine' => 0.95, // 95% efficient
                'pediatrics' => 0.85,   // 85% efficient
                'emergency medicine' => 0.75 // 75% efficient
            ],
            'date_preference' => 'encounter',
            'min_batch_size' => 3,
            'max_batch_size' => 15,
            'daily_capacity' => 50
        ],
        [
            'name' => 'Insurer B',
            'code' => 'INS-B',
            'email' => 'insurer.b@example.com',
            'specialty_preferences' => [
                'cardiology' => 0.9,    // 90% efficient
                'orthopedics' => 0.7,   // 70% efficient
                'neurology' => 0.8,     // 80% efficient
                'oncology' => 0.5,      // 50% efficient
                'general medicine' => 0.9, // 90% efficient
                'pediatrics' => 0.8,    // 80% efficient
                'emergency medicine' => 0.6 // 60% efficient
            ],
            'date_preference' => 'submission',
            'min_batch_size' => 5,
            'max_batch_size' => 25,
            'daily_capacity' => 100
        ],
        [
            'name' => 'Insurer C',
            'code' => 'INS-C',
            'email' => 'insurer.c@example.com',
            'specialty_preferences' => [
                'cardiology' => 0.6,    // 60% efficient
                'orthopedics' => 0.8,   // 80% efficient
                'neurology' => 0.9,     // 90% efficient
                'oncology' => 0.8,      // 80% efficient
                'general medicine' => 0.85, // 85% efficient
                'pediatrics' => 0.7,    // 70% efficient
                'emergency medicine' => 0.9 // 90% efficient
            ],
            'date_preference' => 'encounter',
            'min_batch_size' => 2,
            'max_batch_size' => 20,
            'daily_capacity' => 75
        ],
        [
            'name' => 'Insurer D',
            'code' => 'INS-D',
            'email' => 'insurer.d@example.com',
            'specialty_preferences' => [
                'cardiology' => 0.7,    // 70% efficient
                'orthopedics' => 0.6,   // 60% efficient
                'neurology' => 0.8,     // 80% efficient
                'oncology' => 0.9,      // 90% efficient
                'general medicine' => 0.8, // 80% efficient
                'pediatrics' => 0.9,    // 90% efficient
                'emergency medicine' => 0.7 // 70% efficient
            ],
            'date_preference' => 'submission',
            'min_batch_size' => 4,
            'max_batch_size' => 30,
            'daily_capacity' => 150
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->insurers as $insurer) {
            DB::table('insurers')->insert([
                'name' => $insurer['name'],
                'code' => $insurer['code'],
                'email' => $insurer['email'],
                'specialty_preferences' => json_encode($insurer['specialty_preferences']),
                'date_preference' => $insurer['date_preference'],
                'min_batch_size' => $insurer['min_batch_size'],
                'max_batch_size' => $insurer['max_batch_size'],
                'daily_capacity' => $insurer['daily_capacity'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
