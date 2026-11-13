<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildEstimate AI — Civil Structural & Cost Estimation Suite</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN with custom config -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        slate: {
                            850: '#151f32',
                            900: '#0f172a',
                            950: '#080d1a',
                        },
                        amber: {
                            450: '#f59e0b',
                            500: '#d97706',
                        },
                        indigo: {
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Marked for Markdown rendering in Chat -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .dark-scroll::-webkit-scrollbar-thumb { background: #334155; }
        
        /* Prose styling for AI chat */
        .prose table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 0.75rem 0; border: 1px solid #e2e8f0; border-radius: 0.5rem; overflow: hidden; }
        .prose th, .prose td { padding: 0.5rem 0.75rem; font-size: 0.8125rem; border-bottom: 1px solid #f1f5f9; }
        .prose th { background-color: #f8fafc; font-weight: 600; color: #1e293b; text-align: left; }
        .prose tr:last-child td { border-bottom: none; }
        .prose strong { color: #0f172a; font-weight: 600; }
        .prose ul, .prose ol { margin-left: 1.25rem; margin-top: 0.35rem; margin-bottom: 0.35rem; }
        .prose li { margin-top: 0.15rem; margin-bottom: 0.15rem; }
        .prose p { margin-top: 0.4rem; margin-bottom: 0.4rem; }
    </style>
</head>
<body class="h-full bg-[#f8fafc] text-slate-800 antialiased flex flex-col font-sans selection:bg-amber-500 selection:text-white" x-data="buildEstimateApp()" x-cloak>

    <!-- Top Navigation Bar -->
    <header class="bg-slate-950 text-white border-b border-slate-800/80 sticky top-0 z-50 backdrop-blur-md bg-opacity-95">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            
            <!-- Brand & Tagline -->
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-400 flex items-center justify-center shadow-lg shadow-amber-500/20 text-slate-950 font-black text-xl tracking-tighter">
                    BE
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-base tracking-tight text-white">BuildEstimate</span>
                        <span class="text-xs bg-amber-400/10 text-amber-400 border border-amber-400/20 px-2 py-0.5 rounded-full font-medium">Enterprise Suite</span>
                        <span class="text-[11px] text-slate-400 hidden md:inline">|</span>
                        <span class="text-[11px] text-slate-400 font-medium hidden md:inline">Assam PWD SOR (2025–26)</span>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-none mt-0.5">IS 456 • IS 13920 (Zone V) • IS 3370 • MoRTH Deep Foundation</p>
                </div>
            </div>

            <!-- Top Right Utilities -->
            <div class="flex items-center space-x-3">
                <div class="hidden sm:flex items-center space-x-2 bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>System Status: <b class="text-white">Active</b></span>
                </div>

                <!-- Print / Export Button -->
                <button @click="printEstimate()" class="inline-flex items-center space-x-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Print Report</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Workspace Area -->
    <div class="flex-1 max-w-[1700px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        
        <!-- Left Panel: AI Chat Assistant (50% Width) -->
        <div class="flex flex-col space-y-4 w-full">
            
            <!-- AI Chat Assistant Container -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex flex-col h-full min-h-[680px]">
                <div class="border-b border-slate-100 pb-3 mb-3 flex justify-between items-center">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-sm shadow-sm">
                            👷‍♂️
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                <span>Er. BuildEstimate</span>
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-semibold px-2 py-0.5 rounded-full border border-emerald-200">Online</span>
                            </h2>
                            <p class="text-[11px] text-slate-500">Senior Civil Structural Engineer • Assam PWD & IS Expert</p>
                        </div>
                    </div>
                    <button @click="clearChat()" class="text-xs text-slate-400 hover:text-red-600 transition flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        <span>Clear</span>
                    </button>
                </div>

                <!-- Chat Messages Scroll Area -->
                <div class="flex-1 overflow-y-auto space-y-3.5 pr-2 dark-scroll" id="chat-box">
                    <template x-for="(msg, idx) in chatMessages" :key="idx">
                        <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start items-start space-x-2'">
                            <!-- Avatar for assistant -->
                            <template x-if="msg.role === 'assistant'">
                                <div class="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs flex-shrink-0 mt-1 shadow-sm">
                                    👷‍♂️
                                </div>
                            </template>
                            <div :class="msg.role === 'user' ? 'bg-slate-900 text-white max-w-[85%] rounded-2xl rounded-tr-none px-4 py-2.5 text-xs sm:text-sm font-medium shadow-sm' : 'bg-slate-50 text-slate-800 max-w-[90%] rounded-2xl rounded-tl-none px-4 py-3 border border-slate-200/80 text-xs sm:text-sm prose prose-sm leading-relaxed'">
                                <div x-html="renderMarkdown(msg.content)"></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="chatLoading" class="text-slate-500 text-xs italic flex items-center space-x-2.5 bg-slate-50 border border-slate-200/60 p-3 rounded-2xl w-fit">
                        <div class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></div>
                        <span>Er. BuildEstimate is working out the structural quantities and site advice...</span>
                    </div>
                </div>

                <!-- Quick Query Starters (Practical Real-World Civil Structures) -->
                <div class="py-2.5 flex flex-wrap gap-1.5 border-t border-slate-100 mt-2">
                    <button @click="sendPrompt('Estimate complete materials for a G+1 duplex villa of 2400 sq.ft built-up area')" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-lg transition font-medium">
                        🏡 G+1 Duplex Villa (2400 sqft)
                    </button>
                    <button @click="sendPrompt('Calculate materials for a commercial shop building with ground floor footprint 1800 sq.ft across 2 floors')" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-lg transition font-medium">
                        🏪 Commercial Complex (G+1, 1800 sqft)
                    </button>
                    <button @click="sendPrompt('Estimate a 3000 sq.ft industrial storage warehouse with boundary plinth and heavy flooring slab')" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-lg transition font-medium">
                        🏭 Industrial Warehouse (3000 sqft)
                    </button>
                    <button @click="sendPrompt('Estimate an underground RCC water storage reservoir 8m length, 4m width, and 2.5m depth per IS 3370')" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-lg transition font-medium">
                        💧 RCC Water Reservoir (8x4x2.5m)
                    </button>
                    <button @click="sendPrompt('Estimate 1200mm bored cast-in-situ RCC bridge pier piles for peak flood discharge Q=4200 cumecs')" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-lg transition font-medium">
                        🌉 Highway Bridge Pier Piles (4200 cumecs)
                    </button>
                    <button @click="sendPrompt('Calculate sariya rod schedule for 8500 kg total steel requirement for column footings and plinth beams')" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-lg transition font-medium">
                        🔩 Footing & Plinth Rebar (8.5 Tonnes)
                    </button>
                </div>

                <!-- Input Field -->
                <form @submit.prevent="submitChat()" class="flex space-x-2 pt-1">
                    <input type="text" x-model="chatInput" placeholder="Ask e.g. G+1 duplex villa, warehouse, commercial complex, water tank..." 
                           class="flex-1 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900 focus:outline-none transition" :disabled="chatLoading">
                    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition disabled:opacity-50 flex items-center gap-1.5 shadow-sm" :disabled="chatLoading || !chatInput.trim()">
                        <span>Send</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Panel: Estimation Summary & Bill of Quantities (50% Width) -->
        <div class="flex flex-col space-y-4 w-full" id="printable-area">
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 flex flex-col h-full min-h-[680px]">
                
                <!-- Report Header Bar -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full" :class="calcLoading ? 'bg-amber-400 animate-ping' : (lastResult ? 'bg-emerald-500' : 'bg-slate-300')"></div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-base" x-text="lastResult ? (lastResult.project_type || 'Estimation Summary') : 'Civil Estimation Report'"></h3>
                            <p class="text-xs text-slate-500" x-text="lastResult && lastResult.scope ? lastResult.scope : 'Engineered Bill of Quantities (BOQ)'"></p>
                        </div>
                    </div>

                    <template x-if="lastResult">
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">Governing Specification</span>
                            <span class="text-xs font-semibold text-slate-700">Assam PWD SOR (2025–26)</span>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="!lastResult && !calcLoading" class="flex-1 flex flex-col items-center justify-center text-center py-16 text-slate-400">
                    <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-3xl mb-3 shadow-inner">
                        🏗️
                    </div>
                    <h4 class="font-semibold text-slate-700 text-sm mb-1">No Estimate Generated Yet</h4>
                    <p class="text-xs text-slate-400 max-w-sm">Use the forms on the left or chat with BuildEstimate AI to calculate site quantities, rod counts, and costs.</p>
                </div>

                <!-- Loading State -->
                <div x-show="calcLoading" class="flex-1 flex flex-col items-center justify-center text-center py-16 text-slate-400">
                    <div class="w-8 h-8 border-3 border-amber-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                    <h4 class="font-semibold text-slate-700 text-sm">Processing Engineering Formulas</h4>
                    <p class="text-xs text-slate-400">Computing weights, rod breakdown, and schedule of rates...</p>
                </div>

                <!-- Active Estimation Output -->
                <div x-show="lastResult && !calcLoading" class="space-y-6 flex-1 text-slate-800">
                    
                    <!-- KPI Headline Cards -->
                    <template x-if="lastResult.cost_summary">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 border border-emerald-200/80 rounded-2xl p-4">
                                <span class="text-xs font-semibold text-emerald-800 block">Total Estimated Cost</span>
                                <span class="text-2xl font-black text-emerald-700 tracking-tight" x-text="formatINR(lastResult.cost_summary.total_estimated_materials_cost_inr)"></span>
                                <span class="text-[10px] text-emerald-600/90 block mt-0.5">Assam PWD Material Cost</span>
                            </div>
                            <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4">
                                <span class="text-xs font-semibold text-slate-600 block">Cost per Sq.Ft</span>
                                <span class="text-2xl font-black text-slate-900 tracking-tight" x-text="'₹' + lastResult.cost_summary.cost_per_sqft_inr"></span>
                                <span class="text-[10px] text-slate-500 block mt-0.5">Built-up Area Basis</span>
                            </div>
                            <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4">
                                <span class="text-xs font-semibold text-amber-800 block">Total 12m Sariya Rods</span>
                                <span class="text-2xl font-black text-amber-700 tracking-tight" x-text="lastResult.indian_market_materials ? lastResult.indian_market_materials.sariya_tmt_steel.total_12m_commercial_rods : (lastResult.total_standard_12m_rods || 'N/A')"></span>
                                <span class="text-[10px] text-amber-700/80 block mt-0.5">Grade Fe 500D (IS 1786)</span>
                            </div>
                        </div>
                    </template>

                    <!-- Rebar Sariya Schedule Table -->
                    <template x-if="lastResult.rebar_breakdown || lastResult.bar_schedule">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🔩</span> Sariya Schedule (Commercial 12-Metre Rods)
                                </h4>
                                <span class="text-[11px] text-slate-500">
                                    Binding Wire: <b class="text-slate-700" x-text="(lastResult.rebar_breakdown ? lastResult.rebar_breakdown.binding_wire_kg : lastResult.binding_wire_kg) + ' kg'"></b>
                                </span>
                            </div>

                            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-50/80 border-b border-slate-200">
                                        <tr>
                                            <th class="py-2.5 px-3 font-semibold text-slate-700">Bar Diameter</th>
                                            <th class="py-2.5 px-3 font-semibold text-slate-700">Weight (kg)</th>
                                            <th class="py-2.5 px-3 font-bold text-amber-700">12m Rods</th>
                                            <th class="py-2.5 px-3 font-semibold text-slate-700">Bundles</th>
                                            <th class="py-2.5 px-3 font-semibold text-slate-700">Structural Role</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="item in (lastResult.rebar_breakdown ? lastResult.rebar_breakdown.bar_schedule : lastResult.bar_schedule)" :key="item.diameter_mm">
                                            <tr class="hover:bg-slate-50/50 transition">
                                                <td class="py-2.5 px-3 font-bold text-slate-900 flex items-center gap-1.5">
                                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                                    <span x-text="item.diameter_mm + ' mm'"></span>
                                                </td>
                                                <td class="py-2.5 px-3 text-slate-700" x-text="item.total_weight_kg + ' kg'"></td>
                                                <td class="py-2.5 px-3 font-bold text-amber-700 text-sm" x-text="item.standard_12m_rods_count"></td>
                                                <td class="py-2.5 px-3 text-slate-600" x-text="item.approx_bundles + ' bundles'"></td>
                                                <td class="py-2.5 px-3 text-slate-500 text-[11px]" x-text="item.typical_use"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- Comprehensive Lifecycle Materials Inventory -->
                    <template x-if="lastResult.lifecycle_materials_inventory">
                        <div class="space-y-3 pt-2" x-data="{ activePhase: 'phase_1_structural' }">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📑</span> Complete Bill of Materials (BOM Lifecycle)
                                </h4>
                                <!-- Phase selector pills -->
                                <div class="flex space-x-1 text-[11px]">
                                    <button @click="activePhase = 'phase_1_structural'" :class="activePhase === 'phase_1_structural' ? 'bg-slate-900 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-2.5 py-1 rounded-lg transition">Structural</button>
                                    <button @click="activePhase = 'phase_2_masonry_wet'" :class="activePhase === 'phase_2_masonry_wet' ? 'bg-slate-900 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-2.5 py-1 rounded-lg transition">Masonry</button>
                                    <button @click="activePhase = 'phase_3_finishes_waterproofing'" :class="activePhase === 'phase_3_finishes_waterproofing' ? 'bg-slate-900 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-2.5 py-1 rounded-lg transition">Finishes</button>
                                    <button @click="activePhase = 'phase_4_fixtures_mep'" :class="activePhase === 'phase_4_fixtures_mep' ? 'bg-slate-900 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-2.5 py-1 rounded-lg transition">Openings & MEP</button>
                                </div>
                            </div>

                            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-50/80 border-b border-slate-200">
                                        <tr>
                                            <th class="py-2.5 px-3 font-semibold text-slate-700">Material Specification</th>
                                            <th class="py-2.5 px-3 font-bold text-amber-700">Calculated Quantity</th>
                                            <th class="py-2.5 px-3 font-semibold text-slate-700">Market Packaging / Form</th>
                                            <th class="py-2.5 px-3 font-semibold text-slate-700">Standard / IS Code</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="item in lastResult.lifecycle_materials_inventory[activePhase].items" :key="item.material">
                                            <tr class="hover:bg-slate-50/50 transition">
                                                <td class="py-2 px-3 font-medium text-slate-900" x-text="item.material"></td>
                                                <td class="py-2 px-3 font-bold text-amber-700 text-sm" x-text="item.quantity"></td>
                                                <td class="py-2 px-3 text-slate-600" x-text="item.packaging"></td>
                                                <td class="py-2 px-3 text-slate-400 font-mono text-[11px]" x-text="item.code"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- Site Materials (Indian Units: Bags, Brass, CFT) -->
                    <template x-if="lastResult.indian_market_materials">
                        <div class="space-y-2.5">
                            <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                <span>📦</span> Site Materials & Packaging
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                                    <span class="text-slate-500 font-medium block">Cement (50kg)</span>
                                    <span class="text-lg font-bold text-slate-900 mt-0.5 block" x-text="lastResult.indian_market_materials.cement_bags_50kg.total_bags"></span>
                                    <span class="text-[10px] text-slate-400">Bags (OPC / PPC)</span>
                                </div>
                                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                                    <span class="text-slate-500 font-medium block">Sand (Reti / M-Sand)</span>
                                    <span class="text-lg font-bold text-slate-900 mt-0.5 block" x-text="lastResult.indian_market_materials.sand_reti_msand.volume_brass + ' Brass'"></span>
                                    <span class="text-[10px] text-slate-400" x-text="lastResult.indian_market_materials.sand_reti_msand.volume_cft + ' CFT'"></span>
                                </div>
                                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                                    <span class="text-slate-500 font-medium block">Aggregate (Rori)</span>
                                    <span class="text-lg font-bold text-slate-900 mt-0.5 block" x-text="lastResult.indian_market_materials.coarse_aggregate_rori_gitti.volume_brass + ' Brass'"></span>
                                    <span class="text-[10px] text-slate-400" x-text="lastResult.indian_market_materials.coarse_aggregate_rori_gitti.volume_cft + ' CFT'"></span>
                                </div>
                                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                                    <span class="text-slate-500 font-medium block">1st Class Bricks</span>
                                    <span class="text-lg font-bold text-slate-900 mt-0.5 block" x-text="lastResult.indian_market_materials.first_class_bricks.total_bricks"></span>
                                    <span class="text-[10px] text-slate-400">IS 2212 Clay/Flyash</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Architectural Area Configuration (When Available) -->
                    <template x-if="lastResult.architectural_configuration">
                        <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl text-xs space-y-2">
                            <span class="font-bold text-slate-900 block text-xs uppercase tracking-wide">📐 Architectural Area Breakdown</span>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-slate-600">
                                <div>Carpet Area / Floor: <b class="text-slate-900" x-text="lastResult.architectural_configuration.carpet_area_per_floor_sqft + ' sq.ft'"></b></div>
                                <div>Wall & Circulation (+22%): <b class="text-slate-900" x-text="lastResult.architectural_configuration.walls_and_circulation_sqft + ' sq.ft'"></b></div>
                                <div>Total Built-Up Area: <b class="text-slate-900" x-text="lastResult.architectural_configuration.total_built_up_area_all_floors_sqft + ' sq.ft'"></b></div>
                            </div>
                        </div>
                    </template>

                    <!-- Bridge Pile Specific Details -->
                    <template x-if="lastResult.scour_hydraulics">
                        <div class="space-y-3">
                            <div class="p-4 bg-amber-50/70 border border-amber-200/80 rounded-xl text-xs space-y-2">
                                <span class="font-bold text-amber-950 block uppercase tracking-wide">🌉 Lacey Scour & Deep Foundation Results</span>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-amber-900">
                                    <div>Mean Scour Depth (R): <b x-text="lastResult.scour_hydraulics.mean_scour_depth_R_metres + ' m'"></b></div>
                                    <div>Max Pier Scour (2*R): <b x-text="lastResult.scour_hydraulics.max_scour_depth_under_pier_Dpier_metres + ' m'"></b></div>
                                    <div>Total Linear Boring: <b x-text="lastResult.pile_geometry.total_drilled_linear_metres + ' m'"></b></div>
                                </div>
                            </div>
                            <template x-if="lastResult.cost_breakdown">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 text-xs">
                                        <span class="text-slate-500 block">Boring & Piling</span>
                                        <span class="text-base font-bold text-slate-800" x-text="formatINR(lastResult.cost_breakdown.boring_piling_inr)"></span>
                                    </div>
                                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 text-xs">
                                        <span class="text-slate-500 block">Submerged Concrete</span>
                                        <span class="text-base font-bold text-slate-800" x-text="formatINR(lastResult.cost_breakdown.concrete_m3_inr)"></span>
                                    </div>
                                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-xs">
                                        <span class="text-emerald-700 block font-semibold">Total Foundation Cost</span>
                                        <span class="text-base font-bold text-emerald-800" x-text="formatINR(lastResult.cost_breakdown.total_foundation_cost_inr)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Swimming Pool & Water Retaining Specific Details -->
                    <template x-if="lastResult.dimensions && lastResult.concrete_and_waterproofing">
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                                    <span class="text-slate-500 block">Water Capacity</span>
                                    <span class="text-base font-bold text-slate-900" x-text="(lastResult.dimensions.capacity_litres ? lastResult.dimensions.capacity_litres.toLocaleString() : 0) + ' L'"></span>
                                </div>
                                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                                    <span class="text-slate-500 block">Wetted Area</span>
                                    <span class="text-base font-bold text-slate-900" x-text="lastResult.dimensions.wetted_surface_area_m2 + ' m²'"></span>
                                </div>
                                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                                    <span class="text-slate-500 block">Waterstop Joint</span>
                                    <span class="text-base font-bold text-amber-700" x-text="lastResult.dimensions.construction_joint_length_m + ' m'"></span>
                                </div>
                                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                                    <span class="text-emerald-700 block font-semibold">Total Est. Cost</span>
                                    <span class="text-base font-bold text-emerald-800" x-text="formatINR(lastResult.cost_breakdown ? lastResult.cost_breakdown.total_cost_inr : 0)"></span>
                                </div>
                            </div>
                            <div class="p-3 bg-sky-50/70 border border-sky-200/80 rounded-xl text-xs space-y-1 text-sky-950">
                                <div><b>Waterstop:</b> <span x-text="lastResult.concrete_and_waterproofing.waterstop_spec"></span></div>
                                <div><b>Waterproofing:</b> <span x-text="lastResult.concrete_and_waterproofing.dual_layer_waterproofing_spec"></span></div>
                            </div>
                        </div>
                    </template>

                </div>

            </div>

        </div>

    </div>

    <!-- Application Footer -->
    <footer class="bg-white border-t border-slate-200 py-3 text-center text-xs text-slate-500">
        BuildEstimate AI • Calibrated with Assam PWD Schedule of Rates (SOR 2025–2026) & Indian Standards
    </footer>

    <script>
        function buildEstimateApp() {
            return {
                activeTab: 'chat',
                calcLoading: false,
                lastResult: null,

                // Forms
                formRoom: {
                    rooms: 3,
                    toilets: 3,
                    balconies: 1,
                    floors: 1,
                    include_living_dining: true,
                    include_kitchen: true,
                    seismic_zone_v: true
                },
                formResidential: {
                    built_up_area_sqft: 1200,
                    floors: 1,
                    seismic_zone_v: true
                },
                formRebar: {
                    total_steel_kg: 5400,
                    application: 'residential'
                },
                formBridge: {
                    peak_discharge_Q: 2500,
                    lacey_silt_factor_f: 1.0,
                    pile_diameter_mm: 1000,
                    num_piles: 4,
                    superstructure_span_m: 35,
                    water_depth_above_bed_m: 4.0
                },
                formPool: {
                    length_m: 10,
                    width_m: 5,
                    depth_m: 1.5,
                    wall_thickness_m: 0.25,
                    raft_thickness_m: 0.30
                },

                // Chat state
                chatInput: '',
                chatLoading: false,
                chatMessages: [
                    {
                        role: 'assistant',
                        content: "👋 **Hello and welcome! I am Er. BuildEstimate**, your Senior Civil Structural Engineer.\n\nI can help you estimate your complete building project — whether it's a **3 BHK house**, a custom floor plan, deep foundation piles, or swimming pools.\n\nSimply tell me what you'd like to build (e.g. *\"3 BHK house in Guwahati\"* or *\"1500 sqft residential duplex\"*), and I will calculate the quantities and guide you through the materials on the right!"
                    }
                ],

                renderMarkdown(content) {
                    if (window.marked) {
                        return marked.parse(content);
                    }
                    return content.replace(/\n/g, '<br>');
                },

                formatINR(val) {
                    if (!val) return '₹0';
                    const num = Number(val);
                    if (num >= 10000000) {
                        return '₹' + (num / 10000000).toFixed(2) + ' Cr';
                    }
                    if (num >= 100000) {
                        return '₹' + (num / 100000).toFixed(2) + ' Lakh';
                    }
                    return '₹' + num.toLocaleString('en-IN');
                },

                printEstimate() {
                    window.print();
                },

                async calculateDirect(endpoint, payload) {
                    this.calcLoading = true;
                    try {
                        const res = await fetch(endpoint, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        this.lastResult = data;
                    } catch (e) {
                        alert('Calculation Error: ' + e.message);
                    } finally {
                        this.calcLoading = false;
                    }
                },

                sendPrompt(text) {
                    this.chatInput = text;
                    this.submitChat();
                },

                clearChat() {
                    this.chatMessages = [
                        {
                            role: 'assistant',
                            content: 'Chat cleared. How can I assist with your civil structural estimation?'
                        }
                    ];
                },

                async submitChat() {
                    const text = this.chatInput.trim();
                    if (!text || this.chatLoading) return;

                    this.chatMessages.push({ role: 'user', content: text });
                    this.chatInput = '';
                    this.chatLoading = true;

                    this.$nextTick(() => {
                        const box = document.getElementById('chat-box');
                        if (box) box.scrollTop = box.scrollHeight;
                    });

                    try {
                        const payload = {
                            messages: this.chatMessages.map(m => ({ role: m.role, content: m.content })),
                            active_estimate: this.lastResult || null
                        };

                        const res = await fetch('/api/v1/chat', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();
                        this.chatMessages.push({
                            role: 'assistant',
                            content: data.response || 'No response returned.'
                        });

                        if (data.tool_results && Object.keys(data.tool_results).length > 0) {
                            const firstTool = Object.values(data.tool_results)[0];
                            this.lastResult = firstTool;
                        }
                    } catch (e) {
                        this.chatMessages.push({
                            role: 'assistant',
                            content: '⚠️ **Error:** Unable to connect to estimation service. ' + e.message
                        });
                    } finally {
                        this.chatLoading = false;
                        this.$nextTick(() => {
                            const box = document.getElementById('chat-box');
                            if (box) box.scrollTop = box.scrollHeight;
                        });
                    }
                }
            }
        }
    </script>
</body>
</html>
