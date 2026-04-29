<template>
  <div class="pie-card">
    <div class="pie-title">{{ title }}</div>
    <Pie :data="chartData" :options="options" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Pie } from 'vue-chartjs'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
  title: String,
  dataMap: Object
})

const options = {
  responsive: true,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
}

const statusColors = {
  'Налицо': '#22c55e',
  'Лазарет': '#f59e0b',
  'Госпиталь': '#ef4444',
  'Отпуск': '#3b82f6',
  'Наряд': '#a855f7',
  'Командировка': '#06b6d4',
  'Увольнение': '#f97316',
  'Прочее': '#94a3b8',
  'Отсутствуют': '#ef4444',
  'По списку': '#60a5fa',
  'По штату': '#d8c48b'
}

const chartData = computed(() => {
  const values = Object.values(props.dataMap)

  return {
    labels: Object.keys(props.dataMap),
    datasets: [
      {
        data: values,
        backgroundColor: Object.keys(props.dataMap).map(
            label => statusColors[label] || '#888'
        ),
        hoverOffset: 6
      }
    ]
  }
})

</script>

<style scoped>
.pie-card {
  padding: 12px;
  border-radius: 14px;
  background: rgba(255,255,255,0.08);
}

.pie-title {
  text-align: center;
  font-size: 14px;
  margin-bottom: 6px;
}
</style>