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
          size: 14
        },
        anchor: 'center',
        align: 'center',
        textAlign: 'center',
        formatter: (value, ctx) => {
          const displayValue = isNaN(value) ? 0 : value
          const label = ctx.chart.data.labels[ctx.dataIndex]
          return `${label}\n${value}`
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
  .pie-title {
    text-align: center;
    margin-bottom: 10px;
  }
</style>