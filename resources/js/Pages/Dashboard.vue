<script setup>
import SentimentChart from '@/Components/SentimentChart.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, router} from '@inertiajs/vue3';
import {ref, onMounted, computed} from 'vue';

const props = defineProps({
    filings: Array,
    stats: Object
});

const timer = ref(60);
const searchQuery = ref('');

onMounted(() => {
  // Tick every 1 second
  setInterval(() => {
    timer.value--;

    if(timer.value <= 0){
      router.reload({only: ['filings', 'stats']});
      timer.value = 60;
    }
  }, 1000);
})

const filteredFilings = computed(() => {
  return props.filings.filter(filing => 
    filing.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    (filing.summary && filing.summary.toLowerCase().includes(searchQuery.value.toLowerCase()))
  );
});

const cleanTitle = (title) => {
  // Remove the form part at the sart
  let cleaned = title.split(' - ').slice(1).join(' - ');

  // Remove the CIK and subject part using regex
  return cleaned.replace(/\s*\(.*?\)/g, '').trim();
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
              <span class="text-slate-400 text-xs uppercase font-bold tracking-widest">
                Next scan
              </span>
              <span class="text-blue-400 font-mono font-bold">{{ timer }}s</span>
            </div>
          </div>
        </template>

        <div class="py-12 bg-slate-900 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Analytics Section -->
                <div class="mb-10 bg-slate-800/30 border border-slate-700 rounded-3xl p-8 flex flex-col md:flex-row items-center justify-between gap-8 shadow-2xl">
                    <div class="flex-1 text-center md:text-left">
                        <h3 class="text-2xl font-bold text-white mb-2 tracking-tight">Market sentiment</h3>
                        <p class="text-slate-400 text-sm mb-6">Real-time AI analysis of the latest SEC activity.</p>
                        
                        <!-- Legend -->
                        <div class="flex flex-wrap justify-center md:justify-start gap-6">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1">Positive</span>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)]"></div>
                                    <span class="text-xl font-mono font-bold text-emerald-400">{{ stats.positive }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1">Negative</span>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.6)]"></div>
                                    <span class="text-xl font-mono font-bold text-rose-400">{{ stats.negative }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1">Neutral</span>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 rounded-full bg-slate-500 shadow-[0_0_8px_rgba(100,116,139,0.6)]"></div>
                                    <span class="text-xl font-mono font-bold text-slate-300">{{ stats.neutral }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- The Chart Component -->
                    <div class="w-full md:w-auto bg-slate-900/50 p-4 rounded-2xl border border-slate-700/50">
                        <SentimentChart :stats="stats" />
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="mb-8 relative">
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search company or keyword..."
                        class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-2xl py-4 px-6 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-slate-500 shadow-xl"
                    >
                    <div class="absolute right-6 top-4 text-slate-500 font-mono text-xs">
                        {{ filteredFilings.length }} MATCHES
                    </div>
                </div>

                <!-- News Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div v-for="filing in filteredFilings" :key="filing.id" 
                       class="group bg-slate-800/50 backdrop-blur-sm border border-slate-700 rounded-2xl p-6 hover:bg-slate-800 hover:border-slate-500 transition-all duration-300 shadow-lg">
                    
                    <!-- 1. Header: Sentiment & Time -->
                    <div class="flex justify-between items-start mb-4">
                        <span :class="[
                            'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-tighter border',
                            filing.sentiment === 'Positive' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                            filing.sentiment === 'Negative' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' :
                            'bg-slate-700/50 text-slate-400 border-slate-600'
                        ]">
                            {{ filing.sentiment }}
                        </span>
                        
                        <span class="text-[10px] text-slate-500 font-medium">
                            {{ new Date(filing.filed_at).toLocaleDateString() }}
                        </span>
                    </div>

                    <!-- 2. Title -->
                    <h3 class="text-base font-bold text-slate-100 group-hover:text-blue-400 transition-colors leading-snug mb-3">
                        {{ cleanTitle(filing.title) }}
                    </h3>

                    <!-- 3. Summary -->
                    <p class="text-slate-400 text-xs leading-relaxed mb-6 line-clamp-3 group-hover:line-clamp-none transition-all">
                        {{ filing.summary }}
                    </p>

                    <!-- 4. Footer -->
                    <div class="flex justify-between items-center pt-4 border-t border-slate-700/50">
                        <div class="text-[9px] text-slate-500 uppercase font-bold tracking-widest">
                            SEC FORM {{ filing.category }}
                        </div>
                        
                        <a :href="filing.link" target="_blank" 
                           class="text-[10px] font-bold text-blue-400 hover:text-blue-300 uppercase tracking-widest flex items-center transition-colors">
                            View source
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </a>
                    </div>
                  </div> 
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
