<script setup>
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale);

const props = defineProps({stats: Object});

const chartData = {
  labels: ['Positive', 'Negative', 'Neutral'],
  datasets:[{
    backgroundColor: ['#10b981', '#f43f5e', '#64748b'],
    hoverOffset: 4,
    data: [props.stats.positive, props.stats.negative, props.stats.neutral]
  }]
};

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  }
};
</script>

<template>
  <div class="h-48 w-48 mx-auto">
    <Doughnut :data="chartData" :options="chartOptions" />
  </div>
</template>