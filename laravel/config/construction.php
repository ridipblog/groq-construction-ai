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

