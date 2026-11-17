<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportNirmaanKnowledge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nirmaan:export-rag';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Nirmaan / BuildEstimate AI system knowledge, rates, and standards to JSONL for RAG';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Nirmaan RAG Knowledge Export...');

        $ragDir = storage_path('app/rag');
        if (!File::exists($ragDir)) {
            File::makeDirectory($ragDir, 0755, true);
        }

        $records = [];

        // 1. Export Jurisdiction & Regional Context
        $jurisdiction = config('construction.jurisdiction', []);
        foreach ($jurisdiction as $key => $desc) {
            $records[] = [
                'id' => "jurisdiction_{$key}",
                'category' => 'jurisdiction',
                'jurisdiction' => 'Assam (State & National Integration)',
                'standard' => 'Assam PWD & Indian Standards',
                'title' => 'Jurisdiction Context: ' . ucwords(str_replace('_', ' ', $key)),
                'content' => "Jurisdiction Rule for " . ucwords(str_replace('_', ' ', $key)) . ": {$desc}"
            ];
        }

        // 2. Export Technical Specifications
        $techSpecs = config('construction.technical_specifications', []);
        foreach ($techSpecs as $key => $spec) {
            $code = $spec['code'] ?? $key;
            $title = $spec['title'] ?? $key;
            $scope = $spec['scope'] ?? '';
            $records[] = [
                'id' => 'spec_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $key)),
                'category' => 'technical_specification',
                'jurisdiction' => 'National Standard (BIS / IRC / MoRTH / NBC)',
                'standard' => $code,
                'title' => "{$code}: {$title}",
                'content' => "Code: {$code}. Title: {$title}. Scope & Technical Requirements: {$scope}"
            ];
        }

        // 3. Export Rebar Commercial Standards (IS 1786)
        $rebarStandards = config('construction.rebar_standards', []);
        foreach ($rebarStandards as $dia => $data) {
            $wPerM = $data['weight_per_m'] ?? 0;
            $wPer12m = $data['weight_per_12m_bar'] ?? 0;
            $use = $data['typical_use'] ?? '';
            $records[] = [
                'id' => "rebar_std_{$dia}mm",
                'category' => 'rebar_standard',
                'jurisdiction' => 'Pan-India Commercial Steel Logistics',
                'standard' => 'IS 1786 / IS 456',
                'title' => "{$dia}mm TMT Fe500D Steel Rod Specification",
                'content' => "Diameter: {$dia}mm. Linear Unit Weight: {$wPerM} kg/m. Weight per 12m commercial rod: {$wPer12m} kg. Typical Structural Role: {$use}. Standard market rod length: 12.0 metres TMT steel rods."
            ];
        }

        // 4. Export Official Schedule of Rates (SOR)
        $rates = config('construction.rates', []);
        foreach ($rates as $category => $items) {
            $jurisdictionName = match ($category) {
                'residential' => 'Assam PWD (Building) SOR 2025-2026',
                'bridge' => 'Assam PWRD (Roads & Bridges) SOR 2025-2026',
                'liquid_retaining' => 'Assam PWD & IS 3370 Liquid Retaining Specifications',
                'boundary_wall' => 'Assam PWD Boundary Wall & Fencing SOR 2025-2026',
                'commercial' => 'Assam PWD Commercial & NBC 2016 Framework',
                'warehouse_peb' => 'Assam Industrial Infrastructure & PEB Standards',
                'road_and_drainage' => 'Assam PWRD Road & CPHEEO Storm Drainage SOR',
                default => 'Assam PWD Schedule of Rates',
            };

            foreach ($items as $itemCode => $item) {
                $name = $item['name'] ?? $itemCode;
                $desc = $item['description'] ?? '';
                $rate = $item['rate'] ?? 0;
                $unit = $item['unit'] ?? '';
                $ratePerUnit = isset($item['rate_per_kg']) ? " (INR {$item['rate_per_kg']} per kg)" : (isset($item['rate_per_brick']) ? " (INR {$item['rate_per_brick']} per brick)" : '');

                $records[] = [
                    'id' => 'sor_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', "{$category}_{$itemCode}")),
                    'category' => "sor_{$category}",
                    'jurisdiction' => $jurisdictionName,
                    'standard' => 'Assam PWD SOR & IS Specifications',
                    'title' => "SOR Item: {$name} ({$itemCode})",
                    'content' => "Item: {$name} ({$itemCode}). Schedule Rate: INR {$rate} per {$unit}{$ratePerUnit}. Description & Application: {$desc}. Governing Jurisdiction: {$jurisdictionName}."
                ];
            }
        }

        // 5. Export Deterministic Service Calculation Rules & Formulas
        $records[] = [
            'id' => 'calc_rule_residential_4phase',
            'category' => 'calculation_engine',
            'jurisdiction' => 'Assam PWD Residential Framework',
            'standard' => 'IS 456 / IS 1786 / IS 2212 / IS 1661',
            'title' => 'Deterministic 4-Phase Residential Estimation Logic',
            'content' => 'Residential house estimation computes: Steel Fe500D at 4.5 kg/sq.ft (Zone V ductile ties) or 3.8 kg/sq.ft; M25 concrete at 0.040 cu.m/sq.ft; 1st class red bricks at 8.5 nos/sq.ft; plaster at 2.6 sq.m/sq.m; vitrified tiles at 0.75 sq.m/sq.m; shuttering formwork at 2.45 times concrete volume (IS 14687). Cement bags = (concrete_m3 * 8.4) + (plaster_m2 * 0.18) + (bricks * 0.024).'
        ];

        $records[] = [
            'id' => 'calc_rule_bridge_scour_piles',
            'category' => 'calculation_engine',
            'jurisdiction' => 'Assam PWRD Roads & Bridges',
            'standard' => 'IRC:78-2014 & MoRTH Section 2200',
            'title' => 'Bridge Pier Scour & Pile Foundation Hydraulics',
            'content' => 'Lacey Regime Scour Depth R = 0.473 * (Q / f)^(1/3) metres. Maximum scour under bridge pier D_pier = 2.0 * R. Minimum pile embedment = 1/3 * D_pier into firm strata with SPT N-value > 35. Total pile depth below bed = D_pier + embedment. Steel reinforcement is 0.6% longitudinal rebar with 1.25 multiplier for spiral ties and lap joints. Cover blocks: 75mm minimum for underwater bored piles.'
        ];

        $records[] = [
            'id' => 'calc_rule_water_retaining_tank',
            'category' => 'calculation_engine',
            'jurisdiction' => 'IS 3370 & SP 34 Liquid Retaining',
            'standard' => 'IS 3370 (Parts 1-4)',
            'title' => 'Water Tank & Swimming Pool Impermeability Rules',
            'content' => 'Swimming pools and underground RCC tanks use M30 design mix concrete (w/c <= 0.45) with 2% crystalline waterproofing admixture by cement weight. Reinforcement steel is 115 kg per cu.m of concrete. Cold construction joints between raft and walls are sealed with continuous hydrophilic swellable waterstop bar (20x10mm). Wetted surfaces receive 2-coat flexible polymer-modified cementitious slurry reinforced with 145 gsm fiberglass mesh.'
        ];

        $records[] = [
            'id' => 'calc_rule_boundary_wall_perimeter',
            'category' => 'calculation_engine',
            'jurisdiction' => 'Assam PWD Boundary Wall & Fencing',
            'standard' => 'IS 1905 / IS 2212 / IS 456',
            'title' => 'Boundary Wall & Security Fencing Estimation Engine',
            'content' => 'Boundary walls compute trench earthwork excavation (depth 0.9m), 100mm PCC 1:4:8 bedding, 230x230mm RCC tie columns at 3.0m c/c, continuous plinth beam band, 75mm weather coping with throating, 1st class brickwork masonry in CM 1:6 (500 bricks/m3), 15mm cement plaster on both outer/inner faces, and optional security fencing (450mm concertina razor coil or 3-strand barbed wire). Steel average is ~85 kg/m3 of RCC elements with Fe500D 12m rods.'
        ];

        $records[] = [
            'id' => 'calc_rule_commercial_building_complex',
            'category' => 'calculation_engine',
            'jurisdiction' => 'Assam PWD Commercial Framework',
            'standard' => 'IS 456 / IS 875 / NBC 2016 Part 4',
            'title' => 'Commercial Building Structural & Fire Reserve Rules',
            'content' => 'Multi-storey commercial framed buildings consume ~5.8 kg/sq.ft of Fe500D steel (superstructure) and 7.2 kg/sq.ft for basement rafts; M30 structural design mix concrete at 0.048 cu.m/sq.ft (superstructure) and 0.065 cu.m/sq.ft (basement). Mandates NBC 2016 Part 4 static underground fire-water storage reserves: 50,000L (<10k sq.ft), 100,000L (10k-20k sq.ft), and 150,000L (>20k sq.ft) with monolithic RCC construction.'
        ];

        $records[] = [
            'id' => 'calc_rule_warehouse_peb_shed',
            'category' => 'calculation_engine',
            'jurisdiction' => 'Assam Industrial Infrastructure Framework',
            'standard' => 'IS 800:2007 / IS 801 / IS 14246 / IS 875 Part 3',
            'title' => 'Pre-Engineered Building (PEB) Warehouse Structural Rules',
            'content' => 'Industrial PEB warehouses compute primary structural steel portal frames at 18-24 kg/sq.m (IS 800/808 built-up sections, +4 kg/sq.m with EOT cranes); secondary cold-formed Z/C purlins and girts at 9.5 kg/sq.m (IS 801/811); AZ150 0.50mm colour-coated profiled galvalume roof and wall cladding (IS 14246); RCC stub pedestals and plinth tie beams; 2.1m peripheral brick dado splash wall in CM 1:5; and 150mm heavy-duty M25 concrete floor slab with Vacuum Dewatered Flooring (VDF/FM2) power-trowelled surface.'
        ];

        $records[] = [
            'id' => 'calc_rule_road_and_drainage_pavement',
            'category' => 'calculation_engine',
            'jurisdiction' => 'Assam PWRD Roads & Highway Framework',
            'standard' => 'IRC:37-2018 / IRC:58-2015 / MoRTH Sec 300-500 / CPHEEO',
            'title' => 'Roadway Pavement & Storm Water Drainage Rules',
            'content' => 'Roads compute MoRTH subgrade earthwork compaction (300mm), followed by either flexible pavement (150mm Granular Sub-base GSB + 150mm Wet Mix Macadam WMM + 50mm Dense Bituminous Macadam DBM + 30mm Bituminous Concrete BC per IRC:37) or rigid concrete pavement (100mm Dry Lean Concrete DLC + 250mm M40 Pavement Quality Concrete PQC with Fe500 dowel bars and tie bars at construction/expansion joints per IRC:58). Longitudinal storm drainage computes CPHEEO compliant M20 RCC rectangular U-drains (150mm wall/bed) with 100mm precast perforated covers.'
        ];

        // 6. Ingest Markdown source documents from storage/app/rag/sources/
        $sourcesDir = storage_path('app/rag/sources');
        if (File::exists($sourcesDir)) {
            $files = File::allFiles($sourcesDir);
            foreach ($files as $file) {
                if ($file->getExtension() === 'md') {
                    $filename = $file->getFilenameWithoutExtension();
                    $relPath = str_replace('\\', '/', $file->getRelativePath());
                    $content = File::get($file->getRealPath());

                    // Split markdown files into sections by '## '
                    $sections = explode("\n## ", $content);
                    $docTitle = trim(str_replace('#', '', $sections[0]));

                    for ($i = 1; $i < count($sections); $i++) {
                        $secText = $sections[$i];
                        $secLines = explode("\n", $secText);
                        $secHeading = trim($secLines[0]);
                        $secBody = trim(implode("\n", array_slice($secLines, 1)));

                        $records[] = [
                            'id' => "doc_{$filename}_sec_{$i}",
                            'category' => str_starts_with($relPath, 'pricing_sor') ? 'sor_reference_document' : 'standards_reference_document',
                            'jurisdiction' => str_starts_with($relPath, 'pricing_sor') ? 'Assam PWD Schedule of Rates' : 'National Standard Codes',
                            'standard' => $docTitle,
                            'title' => "{$docTitle} - {$secHeading}",
                            'content' => "Document: {$docTitle}. Section: {$secHeading}.\n{$secBody}"
                        ];
                    }
                }
            }
        }

        // Write out records to JSONL
        $targetFile = storage_path('app/rag/nirmaan_system_knowledge.jsonl');
        $fp = fopen($targetFile, 'w');
        foreach ($records as $record) {
            fwrite($fp, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        }
        fclose($fp);

        $this->info("Successfully exported " . count($records) . " knowledge records to {$targetFile}");
        return Command::SUCCESS;
    }
}

