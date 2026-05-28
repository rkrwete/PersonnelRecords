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

  .table-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    margin-top: 10px;
  }

  .search-container {
    width: 320px;
  }

  .table-search-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #fff;
    padding: 8px 12px;
    border-radius: 8px;
    outline: none;
    font-size: 13px;
    box-sizing: border-box;
    transition: border-color 0.15s ease, background-color 0.15s ease;
  }

  .table-search-input:focus {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.25);
  }

  .table-search-input::placeholder {
    color: rgba(255, 255, 255, 0.35);
  }

  .table-no-results {
    text-align: center;
    padding: 40px 0;
    color: rgba(255, 255, 255, 0.4);
    font-size: 14px;
    border: 1px dashed rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    margin-top: 15px;
  }

  .table {
    width: 100%;
    border-collapse: collapse;
    font-size: 16px; 
    margin-top: 10px;
    table-layout: auto; 
  }

  .table thead th {
    padding: 12px 4px;
    vertical-align: bottom; 
    position: sticky;
    top: 0;
    z-index: 10;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(50px);
  }

  .table thead th:nth-child(1),
  .table thead th:nth-child(2) {
    vertical-align: middle;
    height: auto;
  }

  .vertical-text {
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    display: inline-block;
    white-space: normal; 
    word-wrap: break-word;
    max-height: 125px; 
    text-align: left;
    line-height: 1.2; 
    margin: 0 auto;
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
            <div class="table-header-row">
              <div class="title" style="margin: 0; text-align: left;">Расход личного состава</div>
              <div class="search-container">
                <input 
                  v-model="tableSearchQuery" 
                  type="text" 
                  placeholder="Поиск подразделения / категории..." 
                  class="table-search-input"
                />
              </div>
            </div>

            <table class="table" v-if="filteredCategories.length > 0">
              <colgroup>
                <col style="width: 100px">
                <col style="">
                <col style="width: 40px">
                <col style="width: 40px">
                <col style="width: 40px">
                <col style="width: 40px">
                <col style="width: 40px">
                <col style="width: 40px">
                <col style="width: 40px">
                <col style="width: 40px">
                <col style="width: 40px">
                <col style="width: 60px">
                <col style="width: 40px">
                <col style="width: 40px">
                <col style="width: 40px">
              </colgroup>
              <thead>
                <tr>
                  <th>№<br>п/п</th>
                  <th>Подразделение / ФИО</th>
                  <th><span class="vertical-text">По штату</span></th>
                  <th><span class="vertical-text">По списку</span></th>
                  <th v-for="id in statusOrder" :key="id">
                    <span class="vertical-text">{{ statusMapById[id].name }}</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(unit, index) in filteredCategories" :key="unit.id">
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
            
            <div v-else class="table-no-results">
              По вашему запросу ничего не найдено
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
  import { ref, onMounted, computed, provide, watch } from 'vue'
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
  const tableSearchQuery = ref('')

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

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', fileName);

      document.body.appendChild(link);
      link.click();
      link.remove();

      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Ошибка при скачивании строевой записки:', error);
      alert('Не удалось скачать файл. Попробуйте позже.');
    }
  }

  function filterCategoriesTree(nodes, query) {
    if (!query) return nodes
    const cleanQuery = query.toLowerCase().trim()

    return nodes.reduce((acc, node) => {
      const isNodeNameMatch = node.name.toLowerCase().includes(cleanQuery)

      const filteredPersonnel = node.personnel 
        ? node.personnel.filter(p => {
            const fullName = `${p.last_name || ''} ${p.first_name || ''} ${p.middle_name || ''}`.toLowerCase()
            return fullName.includes(cleanQuery)
          })
        : []
      const hasPersonnelMatch = filteredPersonnel.length > 0

      const matchingChildren = node.children ? filterCategoriesTree(node.children, query) : []
      const hasChildrenMatch = matchingChildren.length > 0

      if (isNodeNameMatch || hasPersonnelMatch || hasChildrenMatch) {
        const newNode = { ...node }

        if (hasChildrenMatch) {
          newNode.children = matchingChildren
        } else if (node.children) {
          newNode.children = isNodeNameMatch ? node.children : []
        }

        newNode.personnel = isNodeNameMatch ? node.personnel : (hasPersonnelMatch ? filteredPersonnel : node.personnel)

        acc.push(newNode)
      }

      return acc
    }, [])
  }

  const filteredCategories = computed(() => {
    return filterCategoriesTree(categories.value, tableSearchQuery.value)
  })

  watch(tableSearchQuery, (newQuery) => {
    if (!newQuery) {
      expanded.value.clear() 
      return
    }

    const cleanQuery = newQuery.toLowerCase().trim()

    function checkAndExpand(nodes) {
      let anyChildMatched = false

      for (const node of nodes) {
        const isSelfMatch = node.name.toLowerCase().includes(cleanQuery)
        
        const isPersonnelMatch = node.personnel && node.personnel.some(p => {
          const fullName = `${p.last_name || ''} ${p.first_name || ''} ${p.middle_name || ''}`.toLowerCase()
          return fullName.includes(cleanQuery)
        })

        const isSubChildMatch = node.children ? checkAndExpand(node.children) : false

        if (isSelfMatch || isPersonnelMatch || isSubChildMatch) {
          expanded.value.add(node.id)
          anyChildMatched = true
        }
      }

      return anyChildMatched
    }

    checkAndExpand(categories.value)
  })

  watch(selectedUnit, () => {
    tableSearchQuery.value = ''
  })
</script>