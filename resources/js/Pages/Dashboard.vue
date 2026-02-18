<script setup>
import SentimentChart from '@/Components/SentimentChart.vue';
import EarningsChart from '@/Components/EarningsChart.vue';
import MetricTooltip from '@/Components/MetricTooltip.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, router} from '@inertiajs/vue3';
import {ref, onMounted, computed, watch} from 'vue';

const props = defineProps({
    filings: Object,
    stats: Object,
    watchlist: Array
});

const timer = ref(60);
const searchQuery = ref('');
const selectedCategory = ref('All');
const newFilingIds = ref(new Set());
const selectedFiling = ref(null);

// Search Autocomplete State
const suggestions = ref([]);
const isSearching = ref(false);
const selectedIndex = ref(-1);
const searchContainer = ref(null);
let debounceTimeout = null;

const categories = [
    'All', 'Watchlist', 'Earnings', 'Insider trading', 'Ownership', 'Corporate events', 'Legal'
];

// Smart Polling Logic
onMounted(() => {
  setInterval(() => {
    timer.value--;

    if(timer.value <= 0){
      refreshData();
      timer.value = 60;
    }
    
    if(timer.value % 5 === 0 && timer.value !== 60) {
      refreshData();
    }
  }, 1000);

  // Close suggestions when clicking outside
  document.addEventListener('click', handleClickOutside);
});

const handleClickOutside = (event) => {
    if (searchContainer.value && !searchContainer.value.contains(event.target)) {
        suggestions.value = [];
        selectedIndex.value = -1;
    }
};

const handleKeyDown = (e) => {
    if (!suggestions.value.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value + 1) % suggestions.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value - 1 + suggestions.value.length) % suggestions.value.length;
    } else if (e.key === 'Enter') {
        if (selectedIndex.value >= 0) {
            selectSuggestion(suggestions.value[selectedIndex.value]);
        }
    } else if (e.key === 'Escape') {
        suggestions.value = [];
        selectedIndex.value = -1;
    }
};

const refreshData = () => {
  router.reload({
    only: ['filings', 'stats', 'watchlist'],
    preserveScroll: true,
    preserveState: true,
  });
};

const selectFiling = (filing) => {
    if(selectedFiling.value?.id === filing.id){
        selectedFiling.value = null;
    } else{
        selectedFiling.value = filing;
    }
};

const toggleWatchlist = (ticker) => {
    if (!isValidTicker(ticker)) return;
    router.post(route('watchlist.toggle'), { ticker }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const isValidTicker = (ticker) => {
    if (!ticker) return false;
    const t = String(ticker).trim().toUpperCase();
    return t !== '' && t !== 'NULL' && t !== 'UNDEFINED' && t !== '???';
};

const isInWatchlist = (ticker) => {
    if (!isValidTicker(ticker)) return false;
    return props.watchlist && props.watchlist.includes(ticker);
};

// Autocomplete Logic
watch(searchQuery, (newVal) => {
    clearTimeout(debounceTimeout);
    
    if (!newVal || newVal.length < 2) {
        suggestions.value = [];
        selectedIndex.value = -1;
        return;
    }


    if (suggestions.value.some(s => s.name === newVal || s.ticker === newVal)) {
        return;
    }

    isSearching.value = true;
    debounceTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/api/search/companies?q=${newVal}`);
            const json = await response.json();
            suggestions.value = json.data || json;
            selectedIndex.value = -1; // Reset selection on new results
        } catch (e) {
            console.error("Search failed", e);
        } finally {
            isSearching.value = false;
        }
    }, 300);
});

const selectSuggestion = (company) => {
    searchQuery.value = company.name;
    suggestions.value = []; // Clear suggestions
    selectedIndex.value = -1;
};

const filteredFilings = computed(() => {
  const filingsData = props.filings.data || [];
  return filingsData.filter(filing => {
    const search = searchQuery.value.toLowerCase();
    const matchesSearch = filing.title.toLowerCase().includes(search) ||
                         (filing.summary && filing.summary.toLowerCase().includes(search)) ||
                         (filing.ticker && filing.ticker.toLowerCase().includes(search));
    
    let matchesCategory = true;
    if (selectedCategory.value === 'Watchlist') {
        matchesCategory = filing.ticker && isInWatchlist(filing.ticker);
    } else if (selectedCategory.value !== 'All') {
        matchesCategory = filing.category === selectedCategory.value;
    }

    return matchesSearch && matchesCategory;
  });
});

const cleanTitle = (title) => {
  let cleaned = title.split(' - ').slice(1).join(' - ');
  cleaned = cleaned.replace(/\s*\(.*?\)/g, '');
  cleaned = cleaned.replace(/\s*(Inc\.?|Corp\.?|Ltd\.?|LLC|PLC|LP|SA|N\.A\.?|Group)(\/.*?)?$/i, '');
  return cleaned.replace(/[\/\s,]+$/, '').trim();
};

watch(()=> props.filings.data, (newData, oldData) => {
    if (!oldData) return;
    const oldIds = new Set(oldData.map(f => f.id));
    newData.forEach(f => {
        if (!oldIds.has(f.id)){
            newFilingIds.value.add(f.id);
            setTimeout(() => {
              newFilingIds.value.delete(f.id);
            }, 5000);
        }
    });
}, {deep: true});

const formatNum = (val, suffix = '') => {
    if (val === null || val === undefined) return '---';
    return Number(val).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + suffix;
};

</script>

<template>
    <Head title="Latest SEC Insights" />

    <AuthenticatedLayout>
        <template #header>
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold leading-tight text-white tracking-tight">
              Latest SEC Insights
            </h2>
            <div class="flex items-center space-x-2 bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700 shadow-inner">
              <span class="text-slate-400 text-xs uppercase font-bold tracking-widest">Next scan</span>
              <span class="text-blue-400 font-mono font-bold">{{ timer }}s</span>
            </div>
          </div>
        </template>

        <div class="py-12 bg-slate-950 min-h-screen">
            <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    
                    <!-- SIDEBAR: CATEGORIES -->
                    <aside class="w-full lg:w-64 shrink-0 space-y-6 lg:sticky lg:top-8">
                        <div class="bg-slate-900/50 backdrop-blur-md border border-slate-800 rounded-3xl p-6">
                            <h3 class="text-slate-500 text-[10px] uppercase font-black tracking-widest mb-6 text-center md:text-left">Filter by Category</h3>
                            <nav class="space-y-2">
                                <button 
                                    v-for="cat in categories" :key="cat"
                                    @click="selectedCategory = cat"
                                    :class="[
                                        'w-full flex items-center px-4 py-3 rounded-2xl text-sm font-bold transition-all duration-200 border',
                                        selectedCategory === cat 
                                            ? 'bg-blue-500/10 border-blue-500/50 text-blue-400 shadow-[0_0_20px_rgba(59,130,246,0.1)]' 
                                            : 'bg-transparent border-transparent text-slate-500 hover:bg-slate-800/50 hover:text-slate-300',
                                        cat === 'Watchlist' && selectedCategory === 'Watchlist' ? 'text-rose-400 border-rose-500/50 bg-rose-500/10 shadow-[0_0_20px_rgba(244,63,94,0.1)]' : ''
                                    ]"
                                >
                                    <svg v-if="cat === 'Watchlist'" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 2 7.5 2c1.74 0 3.41.81 4.5 2.09C13.09 2.81 14.76 2 16.5 2 19.58 2 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                    {{ cat }}
                                </button>
                            </nav>
                        </div>
                    </aside>

                    <!-- MAIN CONTENT: FEED -->
                    <main :class="['flex-1 space-y-8 transition-all duration-500', selectedFiling ? 'lg:max-w-2xl' : '']">
                        
                        <!-- Analytics Section -->
                        <div v-if="!selectedFiling" class="bg-slate-900/50 backdrop-blur-md border border-slate-800 rounded-3xl p-8 flex flex-col md:flex-row items-center justify-between gap-8 shadow-2xl">
                            <div class="flex-1 text-center md:text-left">
                                <h3 class="text-2xl font-bold text-white mb-2 tracking-tight">Market sentiment</h3>
                                <p class="text-slate-400 text-sm mb-6">Real-time AI analysis of the latest SEC activity.</p>
                                
                                <div class="flex flex-wrap justify-center md:justify-start gap-6">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1">Positive</span>
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                            <span class="text-xl font-mono font-bold text-emerald-400">{{ stats.positive }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1">Negative</span>
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                            <span class="text-xl font-mono font-bold text-rose-400">{{ stats.negative }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1">Neutral</span>
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 rounded-full bg-slate-500"></div>
                                            <span class="text-xl font-mono font-bold text-slate-300">{{ stats.neutral }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-full md:w-auto bg-slate-950/50 p-4 rounded-2xl border border-slate-800/50">
                                <SentimentChart :stats="stats" />
                            </div>
                        </div>

                        <!-- Search Bar with Autocomplete -->
                        <div class="relative group z-50" ref="searchContainer">
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                @keydown="handleKeyDown"
                                placeholder="Search company, ticker or keyword..."
                                class="w-full bg-slate-900/50 backdrop-blur-md border border-slate-800 text-white rounded-2xl py-4 px-6 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-slate-500 shadow-xl"
                            >
                            <div class="absolute right-6 top-4 text-slate-500 font-mono text-xs hidden md:block">
                                {{ filteredFilings.length }} MATCHES
                            </div>

                            <!-- Autocomplete Dropdown -->
                            <Transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="transform scale-95 opacity-0"
                                enter-to-class="transform scale-100 opacity-100"
                                leave-active-class="transition duration-75 ease-in"
                                leave-from-class="transform scale-100 opacity-100"
                                leave-to-class="transform scale-95 opacity-0"
                            >
                                <div v-if="suggestions.length > 0 && searchQuery.length >= 2" class="absolute top-full left-0 w-full mt-2 bg-slate-900/95 backdrop-blur-xl border border-slate-700 rounded-2xl shadow-2xl overflow-hidden z-50">
                                    <ul>
                                        <li 
                                            v-for="(company, index) in suggestions" 
                                            :key="company.ticker"
                                            @click="selectSuggestion(company)"
                                            :class="[
                                                'px-6 py-3 cursor-pointer transition-colors border-b border-slate-800/50 last:border-0 group',
                                                index === selectedIndex ? 'bg-blue-600/20' : 'hover:bg-slate-800/50'
                                            ]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <span :class="[
                                                        'border px-2 py-1 rounded-md font-mono text-xs font-bold transition-all',
                                                        index === selectedIndex ? 'bg-blue-500 text-white border-blue-400' : 'bg-blue-500/10 text-blue-400 border-blue-500/20 group-hover:bg-blue-500 group-hover:text-white'
                                                    ]">
                                                        {{ company.ticker }}
                                                    </span>
                                                    <span :class="[
                                                        'font-medium text-sm transition-colors',
                                                        index === selectedIndex ? 'text-blue-400' : 'text-slate-200'
                                                    ]">{{ company.name }}</span>
                                                </div>
                                                <svg :class="['w-4 h-4 transition-all', index === selectedIndex ? 'text-blue-400 translate-x-1' : 'text-slate-600 group-hover:text-blue-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </Transition>
                        </div>

                        <!-- News Grid -->
                        <div :class="['grid gap-6', selectedFiling ? 'grid-cols-1' : 'grid-cols-1 xl:grid-cols-2']">
                          <div v-for="filing in filteredFilings" :key="filing.id" 
                               @click="selectFiling(filing)"
                               :class="[
                                   'group cursor-pointer bg-slate-900/40 backdrop-blur-md border rounded-3xl p-6 transition-all duration-300 shadow-lg hover:shadow-2xl relative overflow-hidden',
                                   selectedFiling?.id === filing.id ? 'border-blue-500 ring-1 ring-blue-500/50 bg-slate-900/80 scale-[1.02]' : 'border-slate-800 hover:border-slate-600',
                                   newFilingIds.has(filing.id) ? 'animate-flash' : '',
                               ]">
                            
                            <!-- Watchlist Heart Button: Strict check using helper -->
                            <button 
                                v-if="isValidTicker(filing.ticker)"
                                @click.stop="toggleWatchlist(filing.ticker)"
                                :class="[
                                    'absolute top-4 right-4 z-10 p-2 rounded-full transition-all duration-300 bg-slate-950/50 border backdrop-blur-md',
                                    isInWatchlist(filing.ticker) ? 'text-rose-500 border-rose-500/50 scale-110 shadow-[0_0_15px_rgba(244,63,94,0.3)]' : 'text-slate-600 border-slate-800 hover:text-slate-400'
                                ]"
                            >
                                <svg class="w-4 h-4" :fill="isInWatchlist(filing.ticker) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 2 7.5 2c1.74 0 3.41.81 4.5 2.09C13.09 2.81 14.76 2 16.5 2 19.58 2 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            </button>

                            <div class="flex justify-between items-start mb-4">
                                <span :class="[
                                    'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-tighter border',
                                    filing.sentiment === 'Positive' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                                    filing.sentiment === 'Negative' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' :
                                    'bg-slate-700/50 text-slate-400 border-slate-600'
                                ]">{{ filing.sentiment }}</span>
                                <span class="text-[10px] text-slate-500 font-medium mr-10">{{ new Date(filing.filed_at).toLocaleDateString() }}</span>
                            </div>
                            <h3 class="text-base font-bold text-slate-100 group-hover:text-blue-400 transition-colors leading-snug mb-3">
                                {{ cleanTitle(filing.title) }}
                            </h3>
                            <p class="text-slate-400 text-xs leading-relaxed line-clamp-2">{{ filing.summary }}</p>
                            
                            <!-- Inline Mobile Details -->
                            <Transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="transform -translate-y-4 opacity-0"
                                enter-to-class="transform translate-y-0 opacity-100"
                                leave-active-class="transition duration-200 ease-in"
                                leave-from-class="transform translate-y-0 opacity-100"
                                leave-to-class="transform -translate-y-4 opacity-0"
                            >
                                <div v-if="selectedFiling?.id === filing.id" class="lg:hidden mt-6 pt-6 border-t border-slate-700/50 space-y-6">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-slate-950/50 p-3 rounded-xl border border-slate-800">
                                            <div class="text-[9px] uppercase text-slate-500 font-bold mb-1">P/E Ratio</div>
                                            <div class="text-sm font-mono text-blue-400">{{ formatNum(filing.pe_ratio) }}</div>
                                        </div>
                                        <div class="bg-slate-950/50 p-3 rounded-xl border border-slate-800">
                                            <div class="text-[9px] uppercase text-slate-500 font-bold mb-1">Profit Margin</div>
                                            <div class="text-sm font-mono text-emerald-400">{{ formatNum(filing.profit_margin, '%') }}</div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-300 leading-relaxed">{{ filing.summary }}</p>
                                </div>
                            </Transition>

                            <div class="flex justify-between items-center pt-4 mt-4 border-t border-slate-700/50">
                                <div class="text-[9px] text-slate-500 uppercase font-black tracking-widest">SEC FORM {{ filing.category }}</div>
                                <a :href="filing.link" target="_blank" @click.stop class="text-[10px] font-bold text-blue-400 hover:text-blue-300 uppercase tracking-widest flex items-center transition-colors">
                                    Source
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </a>
                            </div>
                          </div> 
                        </div>
                    </main>

                    <!-- RIGHT SIDEBAR: ANALYSIS PANEL (Desktop Only) -->
                    <Transition
                        enter-active-class="transition duration-500 ease-out"
                        enter-from-class="transform translate-x-full opacity-0"
                        enter-to-class="transform translate-x-0 opacity-100"
                        leave-active-class="transition duration-400 ease-in"
                        leave-from-class="transform translate-x-0 opacity-100"
                        leave-to-class="transform translate-x-full opacity-0"
                    >
                        <aside v-if="selectedFiling" class="hidden lg:block w-[450px] sticky top-8 h-[calc(100vh-4rem)]">
                            <div class="bg-slate-900/80 backdrop-blur-2xl border border-blue-500/20 rounded-[2.5rem] p-8 h-full overflow-y-auto scrollbar-hide shadow-2xl shadow-blue-500/5 flex flex-col">
                                <div class="flex justify-between items-start mb-8">
                                    <div>
                                        <div class="flex items-center space-x-3 mb-2">
                                            <span class="bg-blue-500 text-white px-3 py-1 rounded-lg text-lg font-black font-mono shadow-lg shadow-blue-500/20">
                                                {{ selectedFiling.ticker || '???' }}
                                            </span>
                                            <span class="text-slate-500 text-xs font-bold uppercase tracking-widest">Analysis</span>
                                        </div>
                                        <h2 class="text-xl font-bold text-white leading-tight">{{ cleanTitle(selectedFiling.title) }}</h2>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button 
                                            v-if="isValidTicker(selectedFiling.ticker)"
                                            @click="toggleWatchlist(selectedFiling.ticker)"
                                            :class="[
                                                'p-2 rounded-xl transition-all duration-300 border',
                                                isInWatchlist(selectedFiling.ticker) ? 'bg-rose-500/10 text-rose-500 border-rose-500/50 shadow-lg shadow-rose-500/10' : 'bg-slate-800/50 text-slate-500 border-slate-700 hover:text-white'
                                            ]"
                                        >
                                            <svg class="w-5 h-5" :fill="isInWatchlist(selectedFiling.ticker) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 2 7.5 2c1.74 0 3.41.81 4.5 2.09C13.09 2.81 14.76 2 16.5 2 19.58 2 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                        </button>
                                        <button @click="selectedFiling = null" class="text-slate-500 hover:text-white transition-colors p-2">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Metrics Grid -->
                                <div class="grid grid-cols-2 gap-4 mb-8">
                                    <MetricTooltip content="Earnings Per Share (Trailing Twelve Months) shows the portion of a company's profit allocated to each outstanding share.">
                                        <div class="bg-slate-950/50 p-4 rounded-2xl border border-slate-800/50 group hover:border-amber-500/50 transition-all cursor-help h-full">
                                            <div class="text-[10px] uppercase text-slate-500 font-black tracking-widest mb-2 group-hover:text-amber-400 transition-colors">EPS (TTM)</div>
                                            <div class="text-2xl font-mono font-bold text-amber-400">{{ formatNum(selectedFiling.reported_eps) }}</div>
                                            <div class="text-[9px] text-slate-600 mt-1">Earnings per share</div>
                                        </div>
                                    </MetricTooltip>

                                    <MetricTooltip content="Net Profit Margin indicates how much net income a company generates as a percentage of revenue. Higher is usually better.">
                                        <div class="bg-slate-950/50 p-4 rounded-2xl border border-slate-800/50 group hover:border-emerald-500/50 transition-all cursor-help h-full">
                                            <div class="text-[10px] uppercase text-slate-500 font-black tracking-widest mb-2 group-hover:text-emerald-400 transition-colors">Profit Margin</div>
                                            <div class="text-2xl font-mono font-bold text-emerald-400">{{ formatNum(selectedFiling.profit_margin, '%') }}</div>
                                            <div class="text-[9px] text-slate-600 mt-1">Net profitability</div>
                                        </div>
                                    </MetricTooltip>
    
                                    <!-- Valuation -->
                                    <MetricTooltip content="Price-to-Earnings (P/E) ratio relates a company's share price to its earnings. High P/E could mean growth expectations or overvaluation.">
                                        <div class="bg-slate-950/50 p-4 rounded-2xl border border-slate-800/50 group hover:border-blue-500/50 transition-all cursor-help h-full">
                                            <div class="text-[10px] uppercase text-slate-500 font-black tracking-widest mb-2 group-hover:text-blue-400 transition-colors">P/E Ratio</div>
                                            <div class="text-2xl font-mono font-bold text-blue-400">{{ formatNum(selectedFiling.pe_ratio) }}</div>
                                            <div class="text-[9px] text-slate-600 mt-1">Price to Earnings</div>
                                        </div>
                                    </MetricTooltip>

                                    <MetricTooltip content="Price-to-Sales (P/S) ratio compares a company’s stock price to its revenues. Useful for valuing companies not yet profitable.">
                                        <div class="bg-slate-950/50 p-4 rounded-2xl border border-slate-800/50 group hover:border-indigo-500/50 transition-all cursor-help h-full">
                                            <div class="text-[10px] uppercase text-slate-500 font-black tracking-widest mb-2 group-hover:text-indigo-400 transition-colors">P/S Ratio</div>
                                            <div class="text-2xl font-mono font-bold text-indigo-400">{{ formatNum(selectedFiling.ps_ratio) }}</div>
                                            <div class="text-[9px] text-slate-600 mt-1">Price to Sales</div>
                                        </div>
                                    </MetricTooltip>
    
                                    <!-- Financial Health -->
                                    <MetricTooltip content="Debt-to-Equity measures financial leverage. High ratios indicate a company is aggressively financing growth with debt.">
                                        <div class="bg-slate-950/50 p-4 rounded-2xl border border-slate-800/50 group hover:border-rose-500/50 transition-all cursor-help h-full">
                                            <div class="text-[10px] uppercase text-slate-500 font-black tracking-widest mb-2 group-hover:text-rose-400 transition-colors">Debt / Equity</div>
                                            <div class="text-2xl font-mono font-bold text-rose-400">{{ formatNum(selectedFiling.debt_to_equity) }}</div>
                                            <div class="text-[9px] text-slate-600 mt-1">Leverage risk</div>
                                        </div>
                                    </MetricTooltip>

                                    <MetricTooltip content="Current Ratio measures ability to pay short-term obligations. A ratio below 1.0 may indicate liquidity issues.">
                                        <div class="bg-slate-950/50 p-4 rounded-2xl border border-slate-800/50 group hover:border-cyan-500/50 transition-all cursor-help h-full">
                                            <div class="text-[10px] uppercase text-slate-500 font-black tracking-widest mb-2 group-hover:text-cyan-400 transition-colors">Current Ratio</div>
                                            <div class="text-2xl font-mono font-bold text-cyan-400">{{ formatNum(selectedFiling.current_ratio) }}</div>
                                            <div class="text-[9px] text-slate-600 mt-1">Liquidity health</div>
                                        </div>
                                    </MetricTooltip>
    
                                    <!-- Performance & Yield -->
                                    <MetricTooltip content="Return on Equity (ROE) measures how effectively management is using a company’s assets to create profits.">
                                        <div class="bg-slate-950/50 p-4 rounded-2xl border border-slate-800/50 group hover:border-purple-500/50 transition-all cursor-help h-full">
                                            <div class="text-[10px] uppercase text-slate-500 font-black tracking-widest mb-2 group-hover:text-purple-400 transition-colors">ROE</div>
                                            <div class="text-2xl font-mono font-bold text-purple-400">{{ formatNum(selectedFiling.roe, '%') }}</div>
                                            <div class="text-[9px] text-slate-600 mt-1">Return on Equity</div>
                                        </div>
                                    </MetricTooltip>

                                    <MetricTooltip content="Dividend Yield is the annual dividend payments divided by the stock price. It represents the cash return on the investment.">
                                        <div class="bg-slate-950/50 p-4 rounded-2xl border border-slate-800/50 group hover:border-lime-500/50 transition-all cursor-help h-full">
                                            <div class="text-[10px] uppercase text-slate-500 font-black tracking-widest mb-2 group-hover:text-lime-400 transition-colors">Div. Yield</div>
                                            <div class="text-2xl font-mono font-bold text-lime-400">{{ formatNum(selectedFiling.dividend_yield, '%') }}</div>
                                            <div class="text-[9px] text-slate-600 mt-1">Annual dividend</div>
                                        </div>
                                    </MetricTooltip>
                                </div>

                                <!-- Earnings History Chart -->
                                <div class="mb-8">
                                    <h4 class="text-[10px] uppercase text-slate-500 font-black tracking-widest mb-4">Earnings Surprise History</h4>
                                    <div class="bg-slate-950/50 p-6 rounded-[2rem] border border-slate-800/50 shadow-inner">
                                        <EarningsChart :history="selectedFiling.financial_history" />
                                    </div>
                                </div>

                                <!-- Summary Full -->
                                <div class="flex-1">
                                    <h4 class="text-[10px] uppercase text-slate-500 font-black tracking-widest mb-4">Executive Summary</h4>
                                    <div class="bg-slate-950/30 p-6 rounded-3xl border border-slate-800/50 text-slate-300 text-sm leading-relaxed mb-8 italic">
                                        "{{ selectedFiling.summary }}"
                                    </div>
                                </div>

                                <!-- Action Footer -->
                                <div class="pt-6 border-t border-slate-800/50 mt-auto">
                                    <a :href="selectedFiling.link" target="_blank" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 rounded-2xl transition-all flex items-center justify-center space-x-2 shadow-lg shadow-blue-600/20">
                                        <span>View official SEC report</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </a>
                                </div>
                            </div>
                        </aside>
                    </Transition>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
