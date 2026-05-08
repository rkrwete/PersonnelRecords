<style scoped>
  .page {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 15px;
  }

  .layout {
    display: flex;
    gap: 20px;
    width: 1400px;
  }

  .sidebar {
    width: 260px;
    padding: 16px 10px;
    border-radius: 10px;
    height: fit-content;
    max-height: 80vh;
    overflow-x: visible;
    overflow-y: auto;
    box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
    scrollbar-width: none;
  }

  .sidebar::-webkit-scrollbar {
    display: none;
  }

  .content {
    flex: 1;
  }

  .card {
    padding: 10px;
    border-radius: 10px;
    box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
  }

  .header-block {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .header-text {
    text-align: center;
  }

  .header-btn {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    padding: 6px 12px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    background: var(--btn);
    color: var(--text);
    font-size: 16px;
    text-decoration: auto;
    transition: background-color 0.5s ease, color 0.5s ease, transform 0.5s ease;
    font-family: "Tektur", sans-serif;
  }

  .header-btn:hover {
    background: var(--btn-hover);
    color: var(--text-hover);
    transform: translateY(calc(-50% - 2px));
  }

  .title {
    text-align: center;
    font-weight: 700;
    margin: 10px 0;
    font-size: 18px;
  }

  .subtitle {
    text-align: center;
    font-size: 14px;
    opacity: 0.8;
    margin-bottom: 5px;
  }

  .tabs {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
  }

  .tab {
    padding: 8px 14px;
    border-radius: 10px;
    cursor: pointer;
    transition: background-color 0.5s ease, color 0.5s ease, transform 0.5s ease;
    box-shadow: 1px 1px 4px 2px rgba(0, 0, 0, 0.3);
  }

  .tab.active {
    background: var(--btn);
  }

  .tab:hover {
    background: var(--btn-hover);
    color: var(--text-hover);
    transform: translateY(-2px);
  }

  .stats {
    display: flex;
    gap: 0 5vw;
    flex-wrap: wrap;
  }

  .status-circles {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    margin: 20px auto;
  }

  .status-circle {
    text-align: center;
    width: 70px;
  }

  .circle {
    width: 70px;
    height: 70px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 6px;
    font-weight: 900;
    font-size: 30px;
    color: var(--text-hover);
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
  }

  .label {
    font-size: 10px;
    margin: auto;
  }

  .table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-top: 10px;
    table-layout: fixed;
  }

  .table th,
  .table td {
    border: 1px solid rgba(255, 255, 255, 0.08);
    padding: 10px 0;
    vertical-align: middle;
  }

  .table thead th {
    background: rgba(255, 255, 255, 0.05);
    font-weight: 600;
    text-align: center;

    position: sticky;
    top: 0;
    z-index: 10;
    backdrop-filter: blur(50px);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08),
    inset 0 -1px 0 rgba(255, 255, 255, 0.08);
  }

  .table td:nth-child(n+3),
  .table th:nth-child(n+3) {
    text-align: center;
  }

  .str-total {
    font-weight:600;
    background: rgba(255,255,255,0.2);
    text-align: center;
  }
</style>

<template>
  <HeaderFirst />
  <div class="page">
    <div class="layout">
      <div class="sidebar">
        <UnitNode
            :node="{ id: 1, name: 'Военная академия связи' }"
            :selected="selectedUnit"
            @select="selectUnit"
        />
        <div class="title">Подразделения</div>
        <UnitNode
            v-for="u in units"
            :key="u.id"
            :node="u"
            :selected="selectedUnit"
            @select="selectUnit"
        />
      </div>
      <div class="content">
        <div class="card" v-if="selectedUnit">
          <div class="header-block">
            <div class="header-text">
              <div class="title">{{ selectedUnit.name }}</div>
              <div class="subtitle">Состояние личного состава</div>
            </div>
            <button class="header-btn" @click="downloadDutyRoster">Строевая записка</button>
          </div>
          <div class="tabs">
            <div class="tab" :class="{ active: tab === 1 }" @click="tab = 1">
              Дашборд
            </div>
            <div class="tab" :class="{ active: tab === 2 }" @click="tab = 2">
              Расход
            </div>
          </div>
          <div v-if="tab === 1" class="stats">
            <PieChart
                title="По списку / по штату"
                :dataMap="{
                'По списку': stats.list,
                'По штату': stats.shtat
              }"
            />
            <PieChart
                title="Налицо / отсутствуют"
                :dataMap="{
                'Налицо': statusMap[1] || 0,
                'Отсутствуют': stats.list - (statusMap[1] || 0)
              }"
            />
            <PieChart
                title="Отсутствующие по категориям"
                :dataMap="statusOrder.reduce((acc, id) => {
                  const value = statusMap[id] || 0
                  if (value > 0 && id !== 1) {
                    acc[statusMapById[id]?.name] = value
                  }
                  return acc
                }, {})"
            />
            <div class="status-circles">
              <div
                  class="status-circle"
                  v-for="status in statuses"
                  :key="status.id"
              >
                <div
                    class="circle"
                    :style="{ background: status.color }"
                >
                  {{ statusMap[status.id] || 0 }}
                </div>
                <div class="label">{{ status.name }}</div>
              </div>
            </div>
          </div>
          <div v-if="tab === 2">
            <div class="title">Расход личного состава</div>
            <table class="table">
              <colgroup>
                <col style="width: 70px">
                <col style="">
                <col style="width: 70px">
                <col style="width: 70px">
                <col style="width: 60px">
                <col style="width: 50px">
                <col style="width: 60px">
                <col style="width: 70px">
                <col style="width: 50px">
                <col style="width: 100px">
                <col style="width: 80px">
                <col style="width: 80px">
                <col style="width: 40px">
                <col style="width: 40px">
                <col style="width: 50px">
              </colgroup>
              <thead>
                <tr>
                  <th>№<br>п/п</th>
                  <th>Подразделение / ФИО</th>
                  <th>По штату</th>
                  <th>По списку</th>
                  <th v-for="id in statusOrder" :key="id">
                    {{ statusMapById[id].name }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(unit, index) in categories" :key="unit.id">
                  <RowUnit
                      :unit="unit"
                      :level="0"
                      :index="String(index + 1)"
                      :expanded="expanded"
                      @toggle="toggle"
                  />
                </template>
                <tr class="str-total">
                  <td></td>
                  <td>Итого</td>
                  <td>{{ stats.shtat }}</td>
                  <td>{{ stats.list }}</td>
                  <td v-for="id in statusOrder" :key="id">
                    {{ statusMap[id] || 0 }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
  import { ref, onMounted, computed, provide } from 'vue'
  import api from '../services/api.js'
  import HeaderFirst from './HeaderFirst.vue'
  import UnitNode from './UnitNode.vue'
  import PieChart from './PieChart.vue'
  import RowUnit from "./RowUnit.vue";
  import { ranks } from '../constants/ranks'
  import { statuses } from '../constants/statuses'

  const units = ref([])
  const selectedUnit = ref(null)
  const categories = ref([])
  const tab = ref(1)

  const statusMapById = computed(() => {
    return Object.fromEntries(statuses.map(s => [s.id, s]))
  })

  const statusOrder = computed(() => statuses.map(s => s.id))

  const rankMap = Object.fromEntries(
      ranks.map(r => [r.id, r.name])
  )

  function getRank(id) {
    return rankMap[id] || '—'
  }

  const statusMap = computed(() => {
    const map = {}

    function traverse(units) {
      for (const u of units) {
        for (const p of u.personnel) {
          const s = p.current_status_id
          map[s] = (map[s] || 0) + 1
        }
        if (u.children) traverse(u.children)
      }
    }

    traverse(categories.value)
    return map
  })

  const stats = computed(() => {
    let shtat = 0
    let list = 0

    for (const c of categories.value) {
      shtat += c.totalShtat
      list += c.totalPersonnelCount
    }

    const present = statusMap.value[1] || 0

    return {
      shtat,
      list,
      present,
      absent: list - present,
    }
  })

  function countStatusRecursive(unit, statusId) {
    let count = 0

    for (const p of unit.personnel) {
      if (p.current_status_id === statusId) count++
    }

    for (const child of unit.children) {
      count += countStatusRecursive(child, statusId)
    }

    return count
  }

  function buildStructure(data) {
    return data.map(item => {
      const childUnits = item.units && item.units.length ? buildStructure(item.units) : []

      const categoryNodes = (item.categories || []).map(cat => {
        return {
          id: `cat_${item.id}_${cat.id}`,
          name: cat.name,
          shtat: Number(cat.shtat) || 0,
          totalShtat: Number(cat.shtat) || 0,
          personnel: cat.personnel || [],
          totalPersonnelCount: (cat.personnel || []).length,
          children: []
        }
      })

      const allChildren = [...categoryNodes, ...childUnits]

      let totalShtat = 0
      let totalPersonnelCount = 0

      for (const child of allChildren) {
        totalShtat += child.totalShtat
        totalPersonnelCount += child.totalPersonnelCount
      }

      return {
        id: item.id,
        name: item.name,
        shtat: Number(item.shtat) || 0,
        totalShtat: totalShtat,
        personnel: [],
        totalPersonnelCount: totalPersonnelCount,
        children: allChildren
      }
    })
  }

  async function selectUnit(unit) {
    selectedUnit.value = unit
    const { data } = await api.get(`/api/units/${unit.id}/personnel/grouped`)
    const builtTree = buildStructure([data])
    const rootNode = builtTree[0]
    categories.value = rootNode && rootNode.children ? rootNode.children : []
  }

  provide('countStatusRecursive', countStatusRecursive)

  onMounted(async () => {
    const { data } = await api.get('/api/units')
    units.value = data.data
    const academy = { id: 1, name: 'Военная академия связи' }
    selectedUnit.value = academy
    await selectUnit(academy)
  })


  function countStatus(cat, statusId) {
    let count = 0

    for (const p of cat.personnel) {
      if (p.current_status_id === statusId) {
        count++
      }
    }

    return count
  }

  const expanded = ref(new Set())

  function toggle(id) {
    if (expanded.value.has(id)) {
      expanded.value.delete(id)
    } else {
      expanded.value.add(id)
    }
  }

  provide('statusOrder', statusOrder)
  provide('getRank', getRank)
  provide('countStatus', countStatus)
  provide('showNoteColumn', false)

  function extractLocalPersonnel(unit) {
    let result = []

    if (Array.isArray(unit.categories)) {
      for (const c of unit.categories) {
        if (Array.isArray(c.personnel)) {
          result.push(...c.personnel)
        }
      }
    }

    return result
  }

  async function downloadDutyRoster() {
    if (!selectedUnit.value) return;

    const unitId = selectedUnit.value.id;

    const endpoint = unitId === 1
        ? `/api/academic-duty-roster/export/${unitId}`
        : `/api/duty-roster/export/${unitId}`;

    try {
      const response = await api.get(endpoint, { responseType: 'blob' });
      const safeName = selectedUnit.value.name.replace(/\s+/g, '_');
      const fileName = `Строевая_записка_${safeName}.xlsx`;

      // Создаем виртуальную ссылку для скачивания файла
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', fileName);

      // Добавляем ссылку в DOM, кликаем по ней и удаляем
      document.body.appendChild(link);
      link.click();
      link.remove();

      // Очищаем память
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Ошибка при скачивании строевой записки:', error);
      alert('Не удалось скачать файл. Попробуйте позже.');
    }
  }
</script>