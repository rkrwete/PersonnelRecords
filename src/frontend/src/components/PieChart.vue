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
  import { statuses } from '../constants/statuses'
  import ChartDataLabels from 'chartjs-plugin-datalabels'

  ChartJS.register(ArcElement, Tooltip, Legend, ChartDataLabels)

  const props = defineProps({
    title: String,
    dataMap: Object
  })

  const options = {
    responsive: true,
    events: [],
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        enabled: false
      },
      datalabels: {
        color: '#003735',
        font: {
          family: 'Tektur',
          size: 11,
          weight: 'bold'
        },
        anchor: 'end',
        align: 'start',
        offset: 8,
        rotation: (ctx) => {
          const dataset = ctx.chart.data.datasets[0];
          const total = dataset.data.reduce((a, b) => a + b, 0);
          const values = dataset.data;
          
          let previous = 0;
          for (let i = 0; i < ctx.dataIndex; i++) {
            previous += values[i];
          }
          
          const current = values[ctx.dataIndex];
          let angle = (previous + current / 2) / total * 360;

          if (angle >= 0 && angle <= 90) {
            angle += 180;
          } else if (angle >= 0 && angle <= 180) {
            angle = angle + 180;
          } 
          else if (angle >= 300 && angle <= 360) {
            angle = angle + 180;
          } 
          
          return angle + 90;
        },
        formatter: (value, ctx) => {
          const displayValue = isNaN(value) ? 0 : value
          const label = ctx.chart.data.labels[ctx.dataIndex]
          // Укорачиваем длинные названия
          const shortLabel = label.length > 15 ? label.slice(0, 12) + '...' : label
          // Число в конце без переноса строки
          return `${shortLabel} ${displayValue}`
        }
      }
    }
  }

  const statusMapByName = Object.fromEntries(
      statuses.map(s => [s.name, s])
  )

  function getExtraColor(label) {
    const map = {
      'Отсутствуют': '#ed5e5e',
      'По списку': '#b0c9e6',
      'По штату': '#e1d6b3'
    }

    return map[label] || '#888'
  }

  const chartData = computed(() => {
    const labels = Object.keys(props.dataMap)
    const values = Object.values(props.dataMap).map(val => isNaN(val) ? 0 : val)

    return {
      labels,
      datasets: [
        {
          data: values,
          backgroundColor: labels.map(label => {
            return statusMapByName[label]?.color || getExtraColor(label)
          }),
          hoverOffset: 6
        }
      ]
    }
  })
</script>

<style scoped>
  .pie-card {
    min-width: 200px;
    max-width: 280px;
    flex: 1;
  }
  
  .pie-title {
    text-align: center;
    margin-bottom: 10px;
    font-weight: 600;
    font-size: 14px;
  }
</style>