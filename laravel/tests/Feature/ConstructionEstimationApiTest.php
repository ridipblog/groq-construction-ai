<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConstructionEstimationApiTest extends TestCase
{
    public function test_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'backend', 'groq_configured', 'groq_model']);
    }

    public function test_room_based_estimation_endpoint(): void
    {
        $response = $this->postJson('/api/v1/estimate/by-rooms', [
            'rooms' => 3,
            'toilets' => 3,
            'balconies' => 1,
            'floors' => 1,
            'seismic_zone_v' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'inputs',
                'indian_market_materials' => [
                    'sariya_tmt_steel',
                    'cement_bags_50kg',
                    'sand_reti_msand',
                    'coarse_aggregate_rori_gitti',
                    'first_class_bricks'
                ],
                'material_quantities',
                'rebar_breakdown' => [
                    'total_steel_kg',
                    'total_standard_12m_rods',
                    'bar_schedule'
                ],
                'cost_summary',
                'architectural_configuration'
            ]);
    }

    public function test_rebar_schedule_endpoint(): void
    {
        $response = $this->postJson('/api/v1/estimate/rebar-schedule', [
            'total_steel_kg' => 5400,
            'application' => 'residential'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_steel_kg',
                'total_standard_12m_rods',
                'bar_schedule'
            ]);
    }

    public function test_bridge_pile_scour_endpoint(): void
    {
        $response = $this->postJson('/api/v1/estimate/bridge-pile', [
            'peak_discharge_Q' => 2500,
            'lacey_silt_factor_f' => 1.0,
            'pile_diameter_mm' => 1000,
            'num_piles' => 4,
            'superstructure_span_m' => 35,
            'water_depth_above_bed_m' => 4.0
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'scour_hydraulics',
                'pile_geometry',
                'reinforcement_details',
                'cost_breakdown'
            ]);
    }

    public function test_swimming_pool_endpoint(): void
    {
        $response = $this->postJson('/api/v1/estimate/swimming-pool', [
            'length_m' => 10.0,
            'width_m' => 5.0,
            'depth_m' => 1.5
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'governing_standard',
                'dimensions',
                'concrete_and_waterproofing',
                'cost_breakdown'
            ]);
    }

    public function test_rates_endpoint(): void
    {
        $response = $this->getJson('/api/v1/rates?category=residential');
        $response->assertStatus(200)
            ->assertJsonStructure(['category', 'rates', 'jurisdiction']);
    }

    public function test_chat_scope_guardrail(): void
    {
        $response = $this->postJson('/api/v1/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'hello there']
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['model_used' => 'rule-based-guardrail']);
    }
}

