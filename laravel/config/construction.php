<?php

return [
    'groq' => [
        'api_key' => env('GROQ_API_KEY', ''),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'verify_ssl' => env('GROQ_VERIFY_SSL', false),
    ],

    'jurisdiction' => [
        'bridge_works' => 'Highway, River Bridge, and Flyover construction projects under Assam State Roads Project (PWRD Guwahati, Dibrugarh, Silchar, Tezpur circles).',
        'residential_building' => 'Low-rise (G, G+1, G+2) RCC framed residential houses and quarters across Kamrup Metropolitan (Guwahati), Dibrugarh, Cachar (Silchar), and Jorhat districts.',
        'seismic_zone' => 'Seismic Zone V (Highest vulnerability - strict ductile detailing mandatory per IS 13920 & IRC:112).'
    ],

    'rates' => [
        'residential' => [
            'Item 101' => [
                'name' => 'Structural Concrete M25 Grade (IS 456:2000)',
                'description' => 'Providing and laying reinforced cement concrete in isolated column footings, plinth beams, columns, lintels, roof beams, and 130mm thick two-way roof slabs',
                'rate' => 7200.0,
                'unit' => 'm3'
            ],
            'Item 102' => [
                'name' => 'Reinforcement Steel Fe500D (IS 1786)',
                'description' => 'High Yield Strength Deformed TMT bars conforming to Fe500D including decoiling, cutting, bending, binding, and placement',
                'rate' => 82000.0,
                'unit' => 'metric tonne',
                'rate_per_kg' => 82.0
            ],
            'Item 103' => [
                'name' => 'First Class Brickwork Masonry (IS 2212)',
                'description' => '9-inch exterior walls and 4.5-inch interior partition walls in cement mortar 1:6',
                'rate' => 12500.0,
                'unit' => '1,000 bricks',
                'rate_per_brick' => 12.50
            ],
            'Item 104' => [
                'name' => 'Cement Plastering Works (IS 1661)',
                'description' => '12mm thick internal plaster in 1:6 mortar and 15mm external sand-faced waterproof sponge plaster in 1:4 mortar',
                'rate' => 320.0,
                'unit' => 'm2'
            ],
            'Item 105' => [
                'name' => 'Vitrified Tile Flooring (IS 15622)',
                'description' => '600x600mm double-charged vitrified floor tiles laid over 20mm thick cement mortar bedding',
                'rate' => 1150.0,
                'unit' => 'm2'
            ]
        ],
        'bridge' => [
            'Item 1.01' => [
                'name' => 'Structural Concrete in Superstructure (M35 Grade)',
                'description' => 'Providing, batching, mixing in computerized batching plant, transit mixer carriage, pumping, vibrator compaction, and steam/water curing for 14 days in bridge deck slab, longitudinal T-beams, and cross girders',
                'rate' => 8650.0,
                'unit' => 'm3'
            ],
            'Item 1.02' => [
                'name' => 'High-Performance Concrete (M40 / M45 Grade)',
                'description' => 'For long-span river bridges (>30m span) and precast segmental girders',
                'rate' => 9450.0,
                'unit' => 'm3'
            ],
            'Item 2.01' => [
                'name' => 'Reinforcement Steel (Fe 500D TMT Bars)',
                'description' => 'Conforming to IS 1786 Grade Fe500D (high ductility), including decoiling, straightening, cutting, bending, binding wire, and hoisting in position at all heights',
                'rate' => 86500.0,
                'unit' => 'metric tonne',
                'rate_per_kg' => 86.50
            ],
            'Item 3.01' => [
                'name' => 'Bored Cast-in-Situ RCC Piles (1000mm Diameter)',
                'description' => 'Boring in all types of riverbed alluvial soils, sand, clay, silt, and soft rock strata including casing pipe, bentonite slurry circulation, and cleaning',
                'rate' => 4550.0,
                'unit' => 'linear metre (m)'
            ],
            'Item 4.01' => [
                'name' => 'Centering and Shuttering (MoRTH Section 1500)',
                'description' => 'Steel formwork with staging across riverbeds including striking and scaffolding',
                'rate' => 820.0,
                'unit' => 'm2'
            ]
        ],
        'liquid_retaining' => [
            'concrete_m30' => [
                'name' => 'Structural Concrete M30 Grade (IS 3370:2021)',
                'description' => 'Design mix impermeability concrete with water-cement ratio <= 0.45 and crystalline waterproofing admixture',
                'rate' => 8000.0,
                'unit' => 'm3'
            ],
            'steel_fe500d' => [
                'name' => 'Reinforcement Steel Fe500D (IS 1786)',
                'description' => 'High ductility corrosion-resistant TMT bars for crack width restriction <= 0.1mm',
                'rate' => 82000.0,
                'unit' => 'metric tonne',
                'rate_per_kg' => 82.0
            ],
            'waterstop' => [
                'name' => 'Swellable Hydrophilic Waterstop (20mm x 10mm)',
                'description' => 'Bentonite-rubber swellable waterstop bar installed continuously along cold construction joint between base raft and perimeter retaining walls',
                'rate' => 450.0,
                'unit' => 'linear metre'
            ],
            'dual_waterproofing' => [
                'name' => 'Dual-Layer Waterproofing System',
                'description' => '2-coat flexible polymer-modified cementitious slurry reinforced with alkali-resistant 145 gsm fiberglass mesh (DFT 1.5mm - 2.0mm)',
                'rate' => 650.0,
                'unit' => 'm2'
            ],
            'pool_tiles' => [
                'name' => 'Pool Finishes & Tiling',
                'description' => 'Fully vitrified glass mosaic or chemical-resistant anti-slip ceramic pool tiles laid with polymer-modified adhesive and 100% solid waterproof epoxy grout',
                'rate' => 1450.0,
                'unit' => 'm2'
            ]
        ],
        'boundary_wall' => [
            'earthwork_excavation' => [
                'name' => 'Earthwork Excavation in Foundation Trenches',
                'description' => 'Excavation in all kinds of soil for foundation trenches including lift up to 1.5m and disposal',
                'rate' => 280.0,
                'unit' => 'm3'
            ],
            'pcc_1_4_8' => [
                'name' => 'Plain Cement Concrete PCC 1:4:8 in Foundation Bed',
                'description' => 'PCC 1:4:8 (1 cement : 4 coarse sand : 8 graded stone aggregate 40mm nominal size)',
                'rate' => 5200.0,
                'unit' => 'm3'
            ],
            'brickwork' => [
                'name' => 'First Class Brickwork in Cement Mortar 1:6 (IS 2212)',
                'description' => 'Brickwork in foundation, plinth and superstructure walls in CM 1:6',
                'rate' => 12500.0,
                'unit' => '1,000 bricks',
                'rate_per_brick' => 12.50
            ],
            'rcc_m25' => [
                'name' => 'RCC M25 Grade in Columns, Plinth & Coping (IS 456)',
                'description' => 'Reinforced cement concrete M25 in isolated post footings, vertical tie columns, plinth beam band, and weather coping',
                'rate' => 7200.0,
                'unit' => 'm3'
            ],
            'steel_fe500d' => [
                'name' => 'Reinforcement Steel Fe500D TMT Bars (IS 1786)',
                'description' => 'High Yield Strength Deformed Fe500D bars including cutting, bending, and placing',
                'rate' => 82000.0,
                'unit' => 'metric tonne',
                'rate_per_kg' => 82.0
            ],
            'cement_plaster_1_4' => [
                'name' => 'Cement Plaster 15mm Sand-Faced External (CM 1:4)',
                'description' => '15mm external cement plaster on both wall faces and posts',
                'rate' => 320.0,
                'unit' => 'm2'
            ],
            'concertina_razor_coil' => [
                'name' => 'Concertina Razor Coil & Barbed Wire Security Fencing',
                'description' => 'Supplying and fixing 450mm dia concertina razor coil with Y-shaped MS angle posts and 3-strand G.I. barbed wire',
                'rate' => 580.0,
                'unit' => 'linear metre (m)'
            ],
            'barbed_wire_3strand' => [
                'name' => 'Standard 3-Strand G.I. Barbed Wire Fencing',
                'description' => '12x14 gauge hot-dip galvanized barbed wire on angle iron extensions',
                'rate' => 220.0,
                'unit' => 'linear metre (m)'
            ]
        ],
        'commercial' => [
            'concrete_m30' => [
                'name' => 'Structural Design Mix Concrete M30 Grade (IS 456:2000)',
                'description' => 'High-strength structural design mix concrete for heavy commercial columns, shear walls, flat slabs, and basement retaining walls',
                'rate' => 8100.0,
                'unit' => 'm3'
            ],
            'steel_fe500d' => [
                'name' => 'Reinforcement Steel Fe500D Commercial Grade (IS 1786)',
                'description' => 'Fe500D high ductility TMT steel for heavy commercial column cages, beams, and raft slabs',
                'rate' => 82500.0,
                'unit' => 'metric tonne',
                'rate_per_kg' => 82.50
            ],
            'basement_retaining_wall' => [
                'name' => 'RCC Basement Retaining Wall & Raft Slabs',
                'description' => 'Impermeable RCC retaining wall with crystalline admixture and waterproofing membrane',
                'rate' => 8600.0,
                'unit' => 'm3'
            ],
            'fire_reserve_water_tank' => [
                'name' => 'NBC 2016 Part 4 Fire-Water Underground Static Storage Tank',
                'description' => 'RCC underground fire static tank including sump, access manhole, and suction header connections',
                'rate' => 8400.0,
                'unit' => 'm3'
            ],
            'commercial_flooring_mep' => [
                'name' => 'Heavy Footfall Vitrified Flooring & Core MEP Allowance',
                'description' => 'Heavy-duty 800x800mm full-body vitrified tiles with dedicated electrical ducting, HVAC sleeves, and fire riser rough-ins',
                'rate' => 1650.0,
                'unit' => 'm2'
            ]
        ],
        'warehouse_peb' => [
            'primary_structural_steel' => [
                'name' => 'Primary Built-Up & Hot-Rolled Structural Steel (IS 800 / IS 808)',
                'description' => 'Built-up I-sections for rigid frames, portal rafters, and columns conforming to IS 2062 Grade E250/E350 including fabrication, primer, and erection',
                'rate' => 98000.0,
                'unit' => 'metric tonne',
                'rate_per_kg' => 98.0
            ],
            'secondary_cold_formed_steel' => [
                'name' => 'Secondary Cold-Formed Z/C Purlins & Girts (IS 801 / IS 811)',
                'description' => 'Galvanized cold-formed Z & C purlins, eave struts, sag rods, and tie angles (275 gsm zinc coating)',
                'rate' => 105000.0,
                'unit' => 'metric tonne',
                'rate_per_kg' => 105.0
            ],
            'galvalume_roof_wall_cladding' => [
                'name' => 'Pre-Painted Galvalume Sheeting (IS 14246 / AS 1397)',
                'description' => '0.50mm TCT high-tensile AZ150 galvalume sheeting with self-drilling EPDM fasteners',
                'rate' => 620.0,
                'unit' => 'm2'
            ],
            'rcc_pedestals_m25' => [
                'name' => 'RCC Isolated Footings & Stub Pedestals M25 (IS 456)',
                'description' => 'M25 reinforced concrete pedestals with anchor bolt assemblies (Grade 8.8 bolts) for PEB column base plates',
                'rate' => 7400.0,
                'unit' => 'm3'
            ],
            'industrial_vdf_flooring' => [
                'name' => 'Industrial VDF (Vacuum Dewatered) / FM2 Concrete Floor Slab',
                'description' => '150mm thick M25 concrete floor with steel wire mesh / steel fibers, vacuum dewatering, power floating, and metallic floor hardener',
                'rate' => 850.0,
                'unit' => 'm2'
            ],
            'brick_dado_wall' => [
                'name' => 'Perimeter Brickwork Splash/Dado Wall (2.1m Height)',
                'description' => '230mm brick wall in cement mortar 1:6 up to 2.1m height with 15mm cement plaster on both faces',
                'rate' => 1350.0,
                'unit' => 'm2'
            ]
        ],
        'road_and_drainage' => [
            'subgrade_earthwork' => [
                'name' => 'Subgrade Preparation & Earthwork Compaction (MoRTH Section 300)',
                'description' => 'Compaction of 500mm subgrade in layers to 97% maximum dry density per IS 2720 (Part 8)',
                'rate' => 240.0,
                'unit' => 'm3'
            ],
            'gsb_crushed_stone' => [
                'name' => 'Granular Sub-Base (GSB) Well-Graded Crushed Stone (MoRTH Section 401)',
                'description' => '150mm/200mm thick GSB Grade-I mechanical mix compacted to 98% MDD',
                'rate' => 1850.0,
                'unit' => 'm3'
            ],
            'wmm_base_course' => [
                'name' => 'Wet Mix Macadam (WMM) Base Course (MoRTH Section 406)',
                'description' => '250mm thick WMM premixed with optimum moisture content and laid with mechanical paver',
                'rate' => 2450.0,
                'unit' => 'm3'
            ],
            'dbm_dense_bituminous' => [
                'name' => 'Dense Bituminous Macadam (DBM) 50-75mm (MoRTH Section 505)',
                'description' => 'VG-30/VG-40 paving bitumen with graded aggregate laid with sensor paver and vibratory rolling',
                'rate' => 8800.0,
                'unit' => 'm3'
            ],
            'bc_bituminous_concrete' => [
                'name' => 'Bituminous Concrete (BC) 30-40mm Wearing Course (MoRTH Section 507)',
                'description' => 'Premix bituminous concrete wearing course with 2% lime filler and VG-30 bitumen',
                'rate' => 9600.0,
                'unit' => 'm3'
            ],
            'pqc_rigid_pavement' => [
                'name' => 'Pavement Quality Concrete (PQC M40) Rigid Pavement (IRC:58 / MoRTH 602)',
                'description' => '250mm thick slip-form paver laid M40 grade concrete with dowel bars and tie bars at joints',
                'rate' => 8400.0,
                'unit' => 'm3'
            ],
            'dlc_dry_lean_concrete' => [
                'name' => 'Dry Lean Concrete (DLC M10) Sub-Base (IRC:58 / MoRTH 601)',
                'description' => '100mm/150mm thick dry lean concrete sub-base layer under PQC slab',
                'rate' => 4800.0,
                'unit' => 'm3'
            ],
            'rcc_storm_drain' => [
                'name' => 'RCC Roadside Storm Drain with Precast Perforated Covers (CPHEEO)',
                'description' => 'M25 reinforced concrete U-trough drain (600x750mm internal) with 100mm precast slotted cover slabs',
                'rate' => 3850.0,
                'unit' => 'linear metre (m)'
            ]
        ]
    ],

    'technical_specifications' => [
        'IS 456:2000' => [
            'code' => 'IS 456:2000',
            'title' => 'Plain and Reinforced Concrete - Code of Practice',
            'scope' => 'General structural safety, nominal/design mixes (M20-M40), clear cover guidelines (Table 16), development lengths (Clause 26.2.1), and formwork stripping times (Table 11).'
        ],
        'IS 1786' => [
            'code' => 'IS 1786:2008',
            'title' => 'High Strength Deformed Steel Bars and Wires for Concrete Reinforcement',
            'scope' => 'Fe500D / Fe550D thermo-mechanically treated (TMT) rebar with high elongation, unit weight formulas (W = d^2 / 162.28 kg/m), and standard 12m stock lengths.'
        ],
        'IS 3370' => [
            'code' => 'IS 3370 (Parts 1-4)',
            'title' => 'Code of Practice for Concrete Structures for the Storage of Liquids',
            'scope' => 'Water tightness design, M30 minimum concrete grade, maximum water-cement ratio 0.45, surface crack width limits (<= 0.1mm - 0.2mm), and hydrophilic swellable waterstops.'
        ],
        'IS 6313' => [
            'code' => 'IS 6313 (Part 2):2013',
            'title' => 'Code of Practice for Anti-Termite Measures in Buildings - Pre-construction Chemical Treatment',
            'scope' => 'Chemical barrier application across bottom and sides of foundation trenches, backfill earth, and plinth floor sub-grade at 5.0 L/m2.'
        ],
        'MoRTH Sec 1500/2200' => [
            'code' => 'MoRTH Section 1500 / 2200',
            'title' => 'Specifications for Road and Bridge Works (Ministry of Road Transport & Highways)',
            'scope' => 'Section 1500 for formwork, falsework, and staging; Section 2200 for sub-structure deep foundation bored cast-in-situ concrete piles with bentonite drilling.'
        ],
        'IRC:78-2014' => [
            'code' => 'IRC:78-2014',
            'title' => 'Standard Specifications and Code of Practice for Road Bridges - Section VII: Foundations and Substructure',
            'scope' => 'Lacey regime scour hydraulics, mean scour depth (R = 0.473(Q/f)^(1/3)), pier scour (2.0R), and minimum embedment depth (>= 1/3 D_pier) into firm bearing strata.'
        ],
        'IRC:112' => [
            'code' => 'IRC:112:2020',
            'title' => 'Code of Practice for Concrete Road Bridges',
            'scope' => 'Limit state design philosophy, high-performance concrete (M35-M45), crack control, ductile detailing, and shear design for bridge superstructure and substructure.'
        ],
        'IRC:83' => [
            'code' => 'IRC:83 (Parts I-IV)',
            'title' => 'Standard Specifications and Code of Practice for Road Bridges - Bearings',
            'scope' => 'Elastomeric bearings, POT and PTFE bearings, spherical bearings, and seismic restraint blocks on pier caps.'
        ],
        'NBC 2016' => [
            'code' => 'NBC 2016',
            'title' => 'National Building Code of India 2016',
            'scope' => 'Comprehensive architectural standards, habitable room minimum areas and heights, light and ventilation, staircase widths, and structural integration.'
        ]
    ],

    'rebar_standards' => [
        8 => ['weight_per_m' => 0.395, 'weight_per_12m_bar' => 4.74, 'typical_use' => 'Stirrups, ties, temperature reinforcement'],
        10 => ['weight_per_m' => 0.617, 'weight_per_12m_bar' => 7.40, 'typical_use' => 'Slab distribution bars, staircases'],
        12 => ['weight_per_m' => 0.888, 'weight_per_12m_bar' => 10.66, 'typical_use' => 'Slab main bars, lintels, light beams'],
        16 => ['weight_per_m' => 1.578, 'weight_per_12m_bar' => 18.94, 'typical_use' => 'Beams main bars, residential columns'],
        20 => ['weight_per_m' => 2.466, 'weight_per_12m_bar' => 29.59, 'typical_use' => 'Heavy beams, columns, pile cages'],
        25 => ['weight_per_m' => 3.853, 'weight_per_12m_bar' => 46.24, 'typical_use' => 'Bridge piers, deep foundation piles, transfer girders'],
        32 => ['weight_per_m' => 6.313, 'weight_per_12m_bar' => 75.76, 'typical_use' => 'Heavy bridge piles, pier caps, critical infrastructure']
    ]
];

