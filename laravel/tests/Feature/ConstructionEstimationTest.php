<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConstructionEstimationTest extends TestCase
{
    /**
     * Test Health Check Endpoint.
     */
    public function test_api_health_check(): void
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'backend', 'groq_configured', 'groq_model'])
            ->assertJsonFragment(['status' => 'healthy']);
    }

    /**
     * Test Rates Endpoint for All Categories and Technical Standards.
     */
    public function test_rates_and_standards_endpoints(): void
    {
        $categories = ['residential', 'bridge', 'liquid_retaining', 'boundary_wall', 'commercial', 'warehouse_peb', 'road_and_drainage', 'standards'];

        foreach ($categories as $cat) {
            $response = $this->getJson("/api/v1/rates?category={$cat}");
            $response->assertStatus(200);

            if ($cat === 'standards') {
                $response->assertJsonStructure(['technical_specifications', 'rebar_standards']);
            } elseif ($cat === 'liquid_retaining') {
                $response->assertJsonStructure(['category', 'rates']);
            } else {
                $response->assertJsonStructure(['category', 'rates', 'jurisdiction']);
            }
        }
    }

    /**
     * Test Residential Estimation by Area (Phase 1).
     */
    public function test_residential_estimation_by_area(): void
    {
        $response = $this->postJson('/api/v1/estimate/residential', [
            'built_up_area_sqft' => 1200,
            'floors' => 1,
            'seismic_zone_v' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'inputs',
                'indian_market_materials',
                'lifecycle_materials_inventory' => [
                    'phase_1_structural',
                    'phase_2_masonry_wet',
                    'phase_3_finishes_waterproofing',
                    'phase_4_fixtures_mep',
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

        $data = $response->json();
        $this->assertGreaterThan(0, $data['cost_summary']['total_estimated_materials_cost_inr']);
        $this->assertGreaterThan(0, $data['rebar_breakdown']['total_standard_12m_rods']);
        $this->assertArrayHasKey('phase_1_structural', $data['lifecycle_materials_inventory']);
    }

    /**
     * Test Rebar Schedule Calculation (IS 1786).
     */
    public function test_rebar_schedule_service(): void
    {
        $response = $this->postJson('/api/v1/estimate/rebar-schedule', [
            'total_steel_kg' => 4500,
            'application' => 'residential'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_steel_kg',
                'total_standard_12m_rods',
                'bar_schedule'
            ]);

        $data = $response->json();
        $this->assertEquals(4500, $data['total_steel_kg']);
        $this->assertGreaterThan(0, $data['total_standard_12m_rods']);
    }

    /**
     * Test Bridge Pier Lacey Scour & Pile Foundation (MoRTH / IRC:78).
     */
    public function test_bridge_pile_scour_estimation(): void
    {
        $response = $this->postJson('/api/v1/estimate/bridge-pile', [
            'peak_discharge_Q' => 2000,
            'lacey_silt_factor_f' => 1.2,
            'pile_diameter_mm' => 1200,
            'num_piles' => 4,
            'superstructure_span_m' => 30,
            'water_depth_above_bed_m' => 4.5
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'scour_hydraulics' => [
                    'mean_scour_depth_R_metres',
                    'max_scour_depth_under_pier_Dpier_metres',
                    'min_embedment_depth_metres',
                ],
                'pile_geometry',
                'reinforcement_details',
                'cost_breakdown'
            ]);
    }

    /**
     * Test Water Retaining Tank & Swimming Pool (IS 3370).
     */
    public function test_swimming_pool_and_tank_estimation(): void
    {
        $response = $this->postJson('/api/v1/estimate/swimming-pool', [
            'length_m' => 12.0,
            'width_m' => 6.0,
            'depth_m' => 1.8,
            'wall_thickness_m' => 0.25,
            'raft_thickness_m' => 0.30
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'governing_standard',
                'dimensions',
                'concrete_and_waterproofing' => [
                    'concrete_volume_m3',
                    'crystalline_admixture_kg',
                    'waterstop_spec',
                ],
                'reinforcement',
                'cost_breakdown'
            ]);
    }

    /**
     * Test Boundary Wall & Security Fencing (Phase 3).
     */
    public function test_boundary_wall_estimation(): void
    {
        $response = $this->postJson('/api/v1/estimate/boundary-wall', [
            'length_m' => 150.0,
            'height_m' => 2.4,
            'wall_thickness_mm' => 230,
            'pillar_spacing_m' => 3.0,
            'fencing_type' => 'concertina_razor_coil'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'inputs',
                'structural_elements' => [
                    'earthwork_excavation_m3',
                    'pcc_bedding_1_4_8_m3',
                    'rcc_m25_concrete_m3',
                    'brickwork_volume_m3',
                    'total_bricks_count',
                ],
                'indian_market_materials' => [
                    'cement_bags_50kg',
                    'sand_reti_msand',
                    'coarse_aggregate_rori_gitti',
                    'first_class_bricks',
                ],
                'cost_breakdown' => [
                    'total_cost_inr',
                    'cost_per_linear_metre_inr',
                ]
            ]);

        $data = $response->json();
        $this->assertGreaterThan(0, $data['cost_breakdown']['total_cost_inr']);
    }

    /**
     * Test Commercial Multi-Storey Building with NBC Fire Reserve (Phase 3).
     */
    public function test_commercial_building_estimation(): void
    {
        $response = $this->postJson('/api/v1/estimate/commercial', [
            'built_up_area_sqft' => 12000,
            'floors' => 4,
            'has_basement' => true,
            'occupancy_type' => 'office'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'inputs',
                'structural_elements',
                'fire_safety_provisions' => [
                    'nbc_standard',
                    'static_fire_reserve_litres',
                ],
                'indian_market_materials',
                'rebar_breakdown',
                'cost_breakdown'
            ]);

        $data = $response->json();
        $this->assertEquals(150000, $data['fire_safety_provisions']['static_fire_reserve_litres']);
        $this->assertGreaterThan(0, $data['cost_breakdown']['total_estimated_materials_cost_inr']);
    }

    /**
     * Test Pre-Engineered Building (PEB Warehouse) Estimation (Phase 4).
     */
    public function test_warehouse_peb_estimation(): void
    {
        $response = $this->postJson('/api/v1/estimate/warehouse-peb', [
            'length_m' => 40.0,
            'width_m' => 20.0,
            'eave_height_m' => 7.0,
            'floor_load_grade' => 'heavy_duty',
            'bay_spacing_m' => 6.0,
            'dado_wall_height_m' => 2.1
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'scope',
                'inputs',
                'peb_structural_steel' => [
                    'primary_rigid_frames_tonnes',
                    'secondary_purlins_girts_tonnes',
                    'total_structural_steel_tonnes',
                    'total_structural_steel_kg',
                ],
                'cladding_and_envelope' => [
                    'roof_sheeting_area_m2',
                    'wall_cladding_area_m2',
                    'total_galvalume_cladding_m2',
                ],
                'civil_and_flooring' => [
                    'substructure_concrete_m25_m3',
                    'substructure_rebar_tonnes',
                    'vdf_floor_thickness_mm',
                    'vdf_floor_concrete_m3',
                    'total_cement_bags_50kg',
                ],
                'cost_breakdown'
            ]);

        $data = $response->json();
        $this->assertGreaterThan(0, $data['cost_breakdown']['total_estimated_cost_inr']);
        $this->assertGreaterThan(0, $data['peb_structural_steel']['total_structural_steel_kg']);
    }

    /**
     * Test Highway Roadway & CPHEEO Storm Drainage Estimation (Phase 4).
     */
    public function test_road_and_drainage_estimation(): void
    {
        // Flexible Pavement Test
        $responseFlex = $this->postJson('/api/v1/estimate/road-and-drainage', [
            'length_m' => 500.0,
            'carriageway_width_m' => 7.0,
            'pavement_type' => 'flexible',
            'drain_type' => 'rcc_covered_both_sides'
        ]);

        $responseFlex->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'inputs',
                'subgrade_earthwork',
                'pavement_layers' => [
                    'gsb_grade1_volume_m3',
                    'wmm_base_volume_m3',
                    'dbm_bituminous_volume_m3',
                    'bc_wearing_course_volume_m3',
                ],
                'stormwater_drainage',
                'cost_breakdown'
            ]);

        // Rigid Concrete Pavement Test
        $responseRigid = $this->postJson('/api/v1/estimate/road-and-drainage', [
            'length_m' => 500.0,
            'carriageway_width_m' => 7.0,
            'pavement_type' => 'rigid',
            'drain_type' => 'rcc_covered_both_sides'
        ]);

        $responseRigid->assertStatus(200)
            ->assertJsonStructure([
                'project_type',
                'inputs',
                'subgrade_earthwork',
                'pavement_layers' => [
                    'dlc_m10_subbase_m3',
                    'pqc_m40_slab_m3',
                    'dowel_and_tie_steel_tonnes',
                ],
                'stormwater_drainage',
                'cost_breakdown'
            ]);
    }

    /**
     * Test Chatbot Guardrail Scope Enforcement.
     */
    public function test_chat_scope_guardrail(): void
    {
        $response = $this->postJson('/api/v1/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'how to make pasta?']
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['model_used' => 'rule-based-guardrail']);
    }
}
