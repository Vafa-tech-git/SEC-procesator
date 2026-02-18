<script setup>
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';
import { computed } from 'vue';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    history: {
        type: Array,
        default: () => []
    }
});

const chartData = computed(() => {
    // Standard industry practice: show oldest to newest (left to right)
    // Finnhub usually gives newest first, so we reverse it.
    const sortedData = [...props.history].reverse();
    
    return {
        labels: sortedData.map(h => `Q${h.quarter} ${h.year}`),
        datasets: [
            {
                label: 'Actual EPS',
                backgroundColor: '#10b981', // Emerald 500 (Growth/Success)
                borderRadius: 8,
                data: sortedData.map(h => h.epsActual)
            },
            {
                label: 'Estimated EPS',
                backgroundColor: '#475569', // Slate 600 (Benchmark)
                borderRadius: 8,
                data: sortedData.map(h => h.epsEstimate)
            }
        ]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'top',
            align: 'end',
            labels: {
                color: '#94a3b8',
                usePointStyle: true,
                pointStyle: 'circle',
                font: { size: 10, weight: 'bold', family: 'Inter, sans-serif' }
            }
        },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f1f5f9',
            bodyColor: '#94a3b8',
            borderColor: '#1e293b',
            borderWidth: 1,
            padding: 12,
            boxPadding: 6,
            usePointStyle: true,
        }
    },
    scales: {
        y: {
            grid: { color: 'rgba(30, 41, 59, 0.5)', drawBorder: false },
            ticks: { 
                color: '#64748b', 
                font: { size: 10, family: 'JetBrains Mono, monospace' },
                callback: (value) => '$' + value
            }
        },
        x: {
            grid: { display: false },
            ticks: { 
                color: '#64748b', 
                font: { size: 10, weight: 'bold' } 
            }
        }
    }
};
</script>

<template>
    <div class="h-64 w-full">
        <Bar v-if="history && history.length" :data="chartData" :options="chartOptions" />
        
        <!-- Professional Empty State -->
        <div v-else class="h-full flex flex-col items-center justify-center border border-dashed border-slate-800 rounded-[2rem] bg-slate-950/20 group hover:border-slate-700 transition-colors">
            <svg class="w-8 h-8 text-slate-800 mb-2 group-hover:text-slate-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span class="text-[10px] text-slate-600 uppercase font-black tracking-widest">Historical Data Pending</span>
        </div>
    </div>
</template>
