<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildEstimate AI - Construction & Civil Engineering API Studio</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0284c7',
                            600: '#0369a1',
                            700: '#075985',
                            900: '#0c4a6e',
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
        .prose table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; margin-bottom: 0.5rem; }
        .prose th, .prose td { border: 1px solid #e2e8f0; padding: 0.4rem 0.6rem; font-size: 0.85rem; }
        .prose th { background-color: #f8fafc; font-weight: 600; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col" x-data="dashboardApp()" x-cloak>

    <!-- Header -->
    <header class="bg-slate-900 text-white shadow-md border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">🏗️</span>
                <div>
                    <h1 class="text-lg font-bold tracking-tight">BuildEstimate AI <span class="text-xs bg-sky-500/20 text-sky-300 border border-sky-400/30 px-2 py-0.5 rounded-full ml-2">Laravel 11+ & IS Codes</span></h1>
                    <p class="text-xs text-slate-400">Assam PWD SOR (2025-2026) • IS 456 / IS 13920 / IS 3370 • MoRTH</p>
                </div>
            </div>

            <!-- Global API Key config -->
            <div class="flex items-center space-x-2 text-xs">
                <span class="text-slate-400 hidden sm:inline">Groq Key:</span>
                <input type="password" x-model="customApiKey" placeholder="Optional Custom Groq Key" 
                       class="bg-slate-800 border border-slate-700 text-slate-200 text-xs rounded px-2.5 py-1 focus:ring-1 focus:ring-sky-400 focus:outline-none w-44">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-950 text-emerald-400 border border-emerald-800">
                    REST API Ready
                </span>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex overflow-x-auto border-t border-slate-800 text-sm font-medium">
            <button @click="activeTab = 'chat'" :class="activeTab === 'chat' ? 'border-sky-400 text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'" class="py-2.5 px-4 border-b-2 whitespace-nowrap transition-colors flex items-center gap-1.5">
                💬 AI Agent Chat
            </button>
            <button @click="activeTab = 'room'" :class="activeTab === 'room' ? 'border-sky-400 text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'" class="py-2.5 px-4 border-b-2 whitespace-nowrap transition-colors flex items-center gap-1.5">
                🏠 Room-Based House
            </button>
            <button @click="activeTab = 'residential'" :class="activeTab === 'residential' ? 'border-sky-400 text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'" class="py-2.5 px-4 border-b-2 whitespace-nowrap transition-colors flex items-center gap-1.5">
                📐 Area Estimation (IS 456)
            </button>
            <button @click="activeTab = 'rebar'" :class="activeTab === 'rebar' ? 'border-sky-400 text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'" class="py-2.5 px-4 border-b-2 whitespace-nowrap transition-colors flex items-center gap-1.5">
                🔩 Rebar Rod Schedule
            </button>
            <button @click="activeTab = 'bridge'" :class="activeTab === 'bridge' ? 'border-sky-400 text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'" class="py-2.5 px-4 border-b-2 whitespace-nowrap transition-colors flex items-center gap-1.5">
                🌉 Bridge Piles & Scour
            </button>
            <button @click="activeTab = 'pool'" :class="activeTab === 'pool' ? 'border-sky-400 text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'" class="py-2.5 px-4 border-b-2 whitespace-nowrap transition-colors flex items-center gap-1.5">
                🏊 Swimming Pool (IS 3370)
            </button>
            <button @click="activeTab = 'rates'" :class="activeTab === 'rates' ? 'border-sky-400 text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'" class="py-2.5 px-4 border-b-2 whitespace-nowrap transition-colors flex items-center gap-1.5">
                📋 PWD Rates & Codes
            </button>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 flex-1 w-full grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Interactive Left / Form Column -->
        <div class="lg:col-span-6 flex flex-col space-y-4">
            
            <!-- TAB 1: AI Chat -->
            <div x-show="activeTab === 'chat'" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex flex-col h-[650px]">
                <div class="border-b pb-3 mb-3 flex justify-between items-center">
                    <div>
                        <h2 class="font-bold text-slate-800">Autonomous Civil Engineering Chatbot</h2>
                        <p class="text-xs text-slate-500">Autonomous function calling into Assam PWD SOR calculators</p>
                    </div>
                    <button @click="clearChat()" class="text-xs text-red-500 hover:underline">Clear History</button>
                </div>

                <!-- Chat history -->
                <div class="flex-1 overflow-y-auto space-y-3 pr-2 text-sm" id="chat-box">
                    <template x-for="(msg, idx) in chatMessages" :key="idx">
                        <div :class="msg.role === 'user' ? 'text-right' : 'text-left'">
                            <div :class="msg.role === 'user' ? 'bg-sky-600 text-white inline-block max-w-[85%] rounded-2xl rounded-tr-none px-4 py-2.5 shadow-sm text-left' : 'bg-slate-100 text-slate-800 inline-block max-w-[95%] rounded-2xl rounded-tl-none px-4 py-2.5 border border-slate-200 text-left prose prose-sm'">
                                <div x-html="renderMarkdown(msg.content)"></div>
                                <template x-if="msg.tokens">
                                    <div class="text-[10px] text-slate-400 mt-1">Tokens: <span x-text="msg.tokens"></span> | Model: <span x-text="msg.model"></span></div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div x-show="chatLoading" class="text-slate-400 text-xs italic flex items-center space-x-2">
                        <div class="w-2 h-2 rounded-full bg-sky-500 animate-ping"></div>
                        <span>BuildEstimate AI is computing quantities & running civil engineering formulas...</span>
                    </div>
                </div>

                <!-- Quick Prompts -->
                <div class="py-2 flex flex-wrap gap-1.5 border-t border-slate-100 mt-2">
                    <button @click="sendPrompt('Estimate a 3 BHK house with 3 bathrooms and 1 balcony in Guwahati')" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded">
                        🏠 3 BHK House
                    </button>
                    <button @click="sendPrompt('Calculate sariya rods for 1500 sqft G+1 house in Seismic Zone V')" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded">
                        🔩 1500 sqft G+1 Sariya
                    </button>
                    <button @click="sendPrompt('Estimate deep foundation pile for Q=3500 cumecs and Lacey silt factor 1.0')" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded">
                        🌉 Bridge Pile (3500 cumecs)
                    </button>
                    <button @click="sendPrompt('Swimming pool 12m length, 6m width, 1.8m depth per IS 3370')" class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded">
                        🏊 IS 3370 Pool (12x6x1.8m)
                    </button>
                </div>

                <!-- Input box -->
                <form @submit.prevent="submitChat()" class="flex space-x-2">
                    <input type="text" x-model="chatInput" placeholder="Ask e.g.: Estimate materials and 12m sariya rods for a 3 BHK house..." 
                           class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:outline-none" :disabled="chatLoading">
                    <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition disabled:opacity-50" :disabled="chatLoading || !chatInput.trim()">
                        Send
                    </button>
                </form>
            </div>

            <!-- TAB 2: Room-Based House Form -->
            <div x-show="activeTab === 'room'" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                <div class="border-b pb-2">
                    <h2 class="font-bold text-slate-800">Room-Based Residential Estimator</h2>
                    <p class="text-xs text-slate-500">Computes carpet area, +22% NBC circulation allowance, full steel rod counts & materials</p>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Bedrooms / Main Rooms</label>
                        <input type="number" x-model.number="formRoom.rooms" min="1" max="10" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Toilets / Bathrooms</label>
                        <input type="number" x-model.number="formRoom.toilets" min="0" max="10" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Balconies</label>
                        <input type="number" x-model.number="formRoom.balconies" min="0" max="10" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Floors (G=1, G+1=2)</label>
                        <input type="number" x-model.number="formRoom.floors" min="1" max="5" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                </div>

                <div class="space-y-2 text-sm pt-2">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" x-model="formRoom.include_living_dining" class="rounded text-sky-600">
                        <span>Include Standard Living / Dining Hall (220 sq.ft)</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" x-model="formRoom.include_kitchen" class="rounded text-sky-600">
                        <span>Include Modular Kitchen (90 sq.ft)</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" x-model="formRoom.seismic_zone_v" class="rounded text-sky-600">
                        <span class="font-medium text-amber-700">Seismic Zone V (IS 13920 Ductile Detailing)</span>
                    </label>
                </div>

                <button @click="calculateDirect('/api/v1/estimate/by-rooms', formRoom)" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-medium py-2 rounded-lg text-sm transition">
                    Run Room-Based Calculation
                </button>
            </div>

            <!-- TAB 3: Direct Area Residential Form -->
            <div x-show="activeTab === 'residential'" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                <div class="border-b pb-2">
                    <h2 class="font-bold text-slate-800">Built-Up Area Residential Estimator (IS 456)</h2>
                    <p class="text-xs text-slate-500">Standard PWD item rates (M25 concrete, Fe500D steel, 1st class bricks, plaster, tiles)</p>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Built-Up Area per Floor (sq.ft)</label>
                        <input type="number" x-model.number="formResidential.built_up_area_sqft" min="100" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Floors (Levels)</label>
                        <input type="number" x-model.number="formResidential.floors" min="1" max="5" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                </div>

                <label class="flex items-center space-x-2 text-sm pt-1">
                    <input type="checkbox" x-model="formResidential.seismic_zone_v" class="rounded text-sky-600">
                    <span class="font-medium text-amber-700">Seismic Zone V Confinement Links (4.5 kg/sqft vs 3.8 kg/sqft)</span>
                </label>

                <button @click="calculateDirect('/api/v1/estimate/residential', formResidential)" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-medium py-2 rounded-lg text-sm transition">
                    Run Area-Based Calculation
                </button>
            </div>

            <!-- TAB 4: Rebar Schedule Form -->
            <div x-show="activeTab === 'rebar'" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                <div class="border-b pb-2">
                    <h2 class="font-bold text-slate-800">12-Meter Commercial Rebar Rod Calculator</h2>
                    <p class="text-xs text-slate-500">Converts total tonnage into 8mm-25mm commercial 12m bars and bundles</p>
                </div>

                <div class="space-y-4 text-sm">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Total Steel Requirement (kg)</label>
                        <input type="number" x-model.number="formRebar.total_steel_kg" min="10" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Application Type</label>
                        <select x-model="formRebar.application" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                            <option value="residential">Residential House (₹82,000 / MT)</option>
                            <option value="bridge">Bridge Works (₹86,500 / MT)</option>
                        </select>
                    </div>
                </div>

                <button @click="calculateDirect('/api/v1/estimate/rebar-schedule', formRebar)" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-medium py-2 rounded-lg text-sm transition">
                    Generate Rod Schedule
                </button>
            </div>

            <!-- TAB 5: Bridge Pile Form -->
            <div x-show="activeTab === 'bridge'" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                <div class="border-b pb-2">
                    <h2 class="font-bold text-slate-800">Bridge Bored Piles & Lacey's Scour Depth</h2>
                    <p class="text-xs text-slate-500">IRC:78-2014 & MoRTH Section 2200 deep foundation estimation</p>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Peak Flood Q (cumecs)</label>
                        <input type="number" x-model.number="formBridge.peak_discharge_Q" min="10" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Lacey's Silt Factor (f)</label>
                        <input type="number" step="0.1" x-model.number="formBridge.lacey_silt_factor_f" min="0.1" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Pile Diameter (mm)</label>
                        <input type="number" x-model.number="formBridge.pile_diameter_mm" step="100" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Number of Piles</label>
                        <input type="number" x-model.number="formBridge.num_piles" min="1" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Superstructure Span (m)</label>
                        <input type="number" x-model.number="formBridge.superstructure_span_m" min="5" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Water Depth Above Bed (m)</label>
                        <input type="number" step="0.5" x-model.number="formBridge.water_depth_above_bed_m" min="0" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                </div>

                <button @click="calculateDirect('/api/v1/estimate/bridge-pile', formBridge)" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-medium py-2 rounded-lg text-sm transition">
                    Calculate Pile & Scour Depth
                </button>
            </div>

            <!-- TAB 6: Swimming Pool Form -->
            <div x-show="activeTab === 'pool'" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                <div class="border-b pb-2">
                    <h2 class="font-bold text-slate-800">IS 3370 Swimming Pool & Water Tank</h2>
                    <p class="text-xs text-slate-500">Crack width &lt;= 0.1mm, 20x10mm swellable waterstop & dual-layer waterproofing</p>
                </div>

                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Length (m)</label>
                        <input type="number" step="0.5" x-model.number="formPool.length_m" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Width (m)</label>
                        <input type="number" step="0.5" x-model.number="formPool.width_m" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Depth (m)</label>
                        <input type="number" step="0.1" x-model.number="formPool.depth_m" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                    </div>
                    <div class="col-span-3 grid grid-cols-2 gap-3 pt-2">
                        <div>
                            <label class="block font-medium text-slate-700 mb-1">Wall Thickness (m)</label>
                            <input type="number" step="0.05" x-model.number="formPool.wall_thickness_m" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700 mb-1">Base Raft Thickness (m)</label>
                            <input type="number" step="0.05" x-model.number="formPool.raft_thickness_m" class="w-full border rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500">
                        </div>
                    </div>
                </div>

                <button @click="calculateDirect('/api/v1/estimate/swimming-pool', formPool)" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-medium py-2 rounded-lg text-sm transition">
                    Estimate Pool Quantities
                </button>
            </div>

            <!-- TAB 7: Rates Lookup -->
            <div x-show="activeTab === 'rates'" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                <div class="border-b pb-2 flex justify-between items-center">
                    <div>
                        <h2 class="font-bold text-slate-800">Assam PWD Schedule of Rates (SOR 2025-2026)</h2>
                        <p class="text-xs text-slate-500">Official reference item rates used in calculations</p>
                    </div>
                    <select x-model="ratesCategory" @change="fetchRates()" class="border rounded-lg text-xs px-2.5 py-1">
                        <option value="all">All Rates</option>
                        <option value="residential">Residential Items</option>
                        <option value="bridge">Bridge Items</option>
                        <option value="liquid_retaining">Liquid Retaining Items</option>
                        <option value="standards">Rebar Weights & Standards</option>
                    </select>
                </div>

                <button @click="fetchRates()" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-2 rounded-lg text-sm transition">
                    Fetch Rates from /api/v1/rates
                </button>
            </div>

        </div>

        <!-- Output / Results / Dart API Inspector Column -->
        <div class="lg:col-span-6 flex flex-col space-y-4">
            
            <!-- Result Cards View -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-center justify-between border-b pb-3 mb-4">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full" :class="calcLoading ? 'bg-amber-400 animate-ping' : (lastResult ? 'bg-emerald-500' : 'bg-slate-300')"></span>
                        <h3 class="font-bold text-slate-800">Engineered Calculation Summary</h3>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="viewMode = 'visual'" :class="viewMode === 'visual' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-700'" class="px-2.5 py-1 rounded text-xs font-medium">Visual</button>
                        <button @click="viewMode = 'json'" :class="viewMode === 'json' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-700'" class="px-2.5 py-1 rounded text-xs font-medium">Dart JSON Payload</button>
                    </div>
                </div>

                <!-- Empty State -->
                <div x-show="!lastResult && !calcLoading" class="text-center py-12 text-slate-400">
                    <span class="text-4xl block mb-2">📊</span>
                    <p class="text-sm">Run a calculator or ask the AI Agent to view the engineered results.</p>
                </div>

                <!-- Loading State -->
                <div x-show="calcLoading" class="text-center py-12 text-slate-400">
                    <div class="w-6 h-6 border-2 border-sky-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                    <p class="text-sm">Executing Indian standard calculation...</p>
                </div>

                <!-- Visual Output Display -->
                <div x-show="lastResult && viewMode === 'visual'" class="space-y-4 text-sm">
                    
                    <!-- Quick KPI metrics -->
                    <template x-if="lastResult.cost_summary">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                <span class="text-xs text-slate-500 block">Total Est. Cost</span>
                                <span class="text-lg font-bold text-emerald-600" x-text="formatINR(lastResult.cost_summary.total_estimated_materials_cost_inr)"></span>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                <span class="text-xs text-slate-500 block">Cost / sq.ft</span>
                                <span class="text-lg font-bold text-slate-800" x-text="'₹' + lastResult.cost_summary.cost_per_sqft_inr"></span>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                <span class="text-xs text-slate-500 block">Total 12m Sariya Rods</span>
                                <span class="text-lg font-bold text-sky-600" x-text="lastResult.indian_market_materials ? lastResult.indian_market_materials.sariya_tmt_steel.total_12m_commercial_rods : (lastResult.total_standard_12m_rods || 'N/A')"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Rebar Bar Schedule Table -->
                    <template x-if="lastResult.rebar_breakdown || lastResult.bar_schedule">
                        <div>
                            <h4 class="font-bold text-slate-800 mb-2 flex items-center gap-1.5 text-xs uppercase tracking-wide">
                                <span>🔩</span> Sariya Schedule (Standard 12-Metre Rods)
                            </h4>
                            <div class="overflow-x-auto border rounded-lg">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-50 border-b">
                                        <tr>
                                            <th class="p-2 font-semibold">Diameter</th>
                                            <th class="p-2 font-semibold">Weight (kg)</th>
                                            <th class="p-2 font-semibold text-sky-700">12m Rods</th>
                                            <th class="p-2 font-semibold">Bundles</th>
                                            <th class="p-2 font-semibold">Structural Application</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="item in (lastResult.rebar_breakdown ? lastResult.rebar_breakdown.bar_schedule : lastResult.bar_schedule)" :key="item.diameter_mm">
                                            <tr class="border-b last:border-0 hover:bg-slate-50">
                                                <td class="p-2 font-bold" x-text="item.diameter_mm + ' mm'"></td>
                                                <td class="p-2" x-text="item.total_weight_kg + ' kg'"></td>
                                                <td class="p-2 font-bold text-sky-700" x-text="item.standard_12m_rods_count"></td>
                                                <td class="p-2" x-text="item.approx_bundles + ' bndl'"></td>
                                                <td class="p-2 text-slate-500 text-[11px]" x-text="item.typical_use"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- Indian Market Materials Breakdown -->
                    <template x-if="lastResult.indian_market_materials">
                        <div>
                            <h4 class="font-bold text-slate-800 mb-2 flex items-center gap-1.5 text-xs uppercase tracking-wide">
                                <span>📦</span> Site Materials (Bags, Brass, CFT)
                            </h4>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="p-2.5 bg-slate-50 border rounded-lg">
                                    <span class="font-semibold text-slate-700 block">Cement Bags (50kg)</span>
                                    <span class="text-base font-bold text-sky-700" x-text="lastResult.indian_market_materials.cement_bags_50kg.total_bags + ' Bags'"></span>
                                </div>
                                <div class="p-2.5 bg-slate-50 border rounded-lg">
                                    <span class="font-semibold text-slate-700 block">Sand (Reti / M-Sand)</span>
                                    <span class="text-base font-bold text-amber-700" x-text="lastResult.indian_market_materials.sand_reti_msand.volume_brass + ' Brass (' + lastResult.indian_market_materials.sand_reti_msand.volume_cft + ' CFT)'"></span>
                                </div>
                                <div class="p-2.5 bg-slate-50 border rounded-lg">
                                    <span class="font-semibold text-slate-700 block">Coarse Aggregate (Rori/Gitti)</span>
                                    <span class="text-base font-bold text-slate-700" x-text="lastResult.indian_market_materials.coarse_aggregate_rori_gitti.volume_brass + ' Brass (' + lastResult.indian_market_materials.coarse_aggregate_rori_gitti.volume_cft + ' CFT)'"></span>
                                </div>
                                <div class="p-2.5 bg-slate-50 border rounded-lg">
                                    <span class="font-semibold text-slate-700 block">1st Class Bricks (IS 2212)</span>
                                    <span class="text-base font-bold text-red-700" x-text="lastResult.indian_market_materials.first_class_bricks.total_bricks + ' Bricks'"></span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Bridge Pile Specifics -->
                    <template x-if="lastResult.scour_hydraulics">
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs space-y-1">
                            <span class="font-bold text-amber-900 block">Lacey Scour & Deep Pile Results:</span>
                            <div>Mean Scour Depth (R): <b x-text="lastResult.scour_hydraulics.mean_scour_depth_R_metres + ' m'"></b></div>
                            <div>Max Scour Under Pier (2*R): <b x-text="lastResult.scour_hydraulics.max_scour_depth_under_pier_Dpier_metres + ' m'"></b></div>
                            <div>Total Foundation Linear Drilling: <b x-text="lastResult.pile_geometry.total_drilled_linear_metres + ' m'"></b></div>
                            <div>Total Foundation Cost: <b class="text-emerald-700" x-text="formatINR(lastResult.cost_breakdown.total_foundation_cost_inr)"></b></div>
                        </div>
                    </template>

                </div>

                <!-- JSON / Dart Contract Display -->
                <div x-show="lastResult && viewMode === 'json'" class="relative">
                    <button @click="copyJson()" class="absolute right-2 top-2 bg-slate-700 hover:bg-slate-600 text-white text-[10px] px-2 py-1 rounded">
                        Copy JSON for Dart Model
                    </button>
                    <pre class="bg-slate-900 text-slate-200 text-xs p-4 rounded-lg overflow-x-auto max-h-[500px]" x-text="JSON.stringify(lastResult, null, 2)"></pre>
                </div>

            </div>

            <!-- Dart API Integration Help Box -->
            <div class="bg-sky-50 border border-sky-200 rounded-xl p-4 text-xs text-sky-900 space-y-2">
                <div class="flex items-center space-x-2 font-semibold">
                    <span>🎯</span>
                    <span>Dart / Flutter Endpoint Contract</span>
                </div>
                <p>All endpoints accept and return pure standard JSON matching this Laravel API backend:</p>
                <div class="font-mono bg-white p-2.5 rounded border border-sky-200 space-y-1 text-[11px] text-slate-700">
                    <div>POST /api/v1/chat &rarr; ChatRequest & { messages: [...] }</div>
                    <div>POST /api/v1/estimate/by-rooms &rarr; { rooms: 3, toilets: 3, floors: 1 }</div>
                    <div>POST /api/v1/estimate/residential &rarr; { built_up_area_sqft: 1200 }</div>
                    <div>POST /api/v1/estimate/rebar-schedule &rarr; { total_steel_kg: 5400 }</div>
                    <div>POST /api/v1/estimate/bridge-pile &rarr; { peak_discharge_Q: 2500 }</div>
                </div>
            </div>

        </div>

    </main>

    <script>
        function dashboardApp() {
            return {
                activeTab: 'chat',
                viewMode: 'visual',
                customApiKey: '',
                calcLoading: false,
                lastResult: null,

                // Forms data
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
                ratesCategory: 'all',

                // Chat state
                chatInput: '',
                chatLoading: false,
                chatMessages: [
                    {
                        role: 'assistant',
                        content: '🙏 **Namaste! I am BuildEstimate AI** — your Indian Civil Structural & Cost Estimation Engineer calibrated with Assam PWD SOR 2025-2026.\n\nAsk me about room configurations (e.g. 3 BHK with 3 toilets), built-up area estimations, 12m standard Sariya (rebar) schedules, bridge bored piles, or swimming pools!'
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

                copyJson() {
                    navigator.clipboard.writeText(JSON.stringify(this.lastResult, null, 2));
                    alert('Copied JSON payload to clipboard!');
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
                        alert('API Error: ' + e.message);
                    } finally {
                        this.calcLoading = false;
                    }
                },

                async fetchRates() {
                    this.calcLoading = true;
                    try {
                        const res = await fetch(`/api/v1/rates?category=${this.ratesCategory}`);
                        const data = await res.json();
                        this.lastResult = data;
                        this.viewMode = 'json';
                    } catch (e) {
                        alert('Rates Fetch Error: ' + e.message);
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
                            api_key: this.customApiKey || null
                        };

                        const res = await fetch('/api/v1/chat', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();
                        this.chatMessages.push({
                            role: 'assistant',
                            content: data.response || 'No response returned.',
                            tokens: data.tokens_used,
                            model: data.model_used
                        });

                        if (data.tool_results && Object.keys(data.tool_results).length > 0) {
                            // Extract first tool result to show in the visual summary
                            const firstTool = Object.values(data.tool_results)[0];
                            this.lastResult = firstTool;
                        }
                    } catch (e) {
                        this.chatMessages.push({
                            role: 'assistant',
                            content: '⚠️ **Error:** Unable to connect to `/api/v1/chat` endpoint. ' + e.message
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

