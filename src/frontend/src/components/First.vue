<style scoped>
.page {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  font-family: system-ui;
  padding: 20px;
}

.layout {
  display: flex;
  gap: 20px;
  width: 1400px;
}

.sidebar {
  width: 260px;
  padding: 16px 0;
  border-radius: 16px;
  background: rgba(0,0,0,0.35);
  color: #e6f4ef;
  height: fit-content;
  max-height: 80vh;
  overflow-x: visible;
}

.content {
  flex: 1;
}

.card {
  padding: 26px;
  border-radius: 20px;
  background: rgba(0,0,0,0.35);
  backdrop-filter: blur(14px);
  color: #e6f4ef;
  box-shadow: 0 20px 80px rgba(0,0,0,0.5);
}

.title {
  text-align: center;
  font-weight: 700;
  margin-bottom: 10px;
}

.subtitle {
  text-align: center;
  font-size: 12px;
  opacity: 0.6;
  margin-bottom: 10px;
}

.tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
}

.tab {
  padding: 8px 14px;
  border-radius: 10px;
  cursor: pointer;
  background: rgba(255,255,255,0.08);
}

.tab.active {
  background: rgba(255,255,255,0.2);
}

.stats {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}

.stat-card {
  flex: 1;
  min-width: 200px;
  padding: 16px;
  border-radius: 14px;
  background: rgba(255,255,255,0.08);
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
  margin-top: 10px;
}

.table th, .table td {
  border: 1px solid rgba(255,255,255,0.1);
  padding: 6px;
}

.status-circles {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-top: 20px;
}

.status-circle {
  width: 100px;
  text-align: center;
}

.circle {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 6px;

  font-weight: bold;
  font-size: 16px;
  color: white;

  box-shadow: 0 4px 20px rgba(0,0,0,0.4);
}

.label {
  font-size: 11px;
  opacity: 0.8;
}
</style>

<template>
  <HeaderFirst />

  <div class="page">
    <div class="layout">
      <div class="sidebar">
        <UnitNode
            :node="{ id: 1, name: 'Академия' }"
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
          <div class="title">{{ selectedUnit.name }}</div>
          <div class="subtitle">Состояние личного состава</div>

          <div class="tabs">
            <div class="tab" :class="{ active: tab === 1 }" @click="tab = 1">
              Карточки
            </div>
            <div class="tab" :class="{ active: tab === 2 }" @click="tab = 2">
              Таблицы
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
                  acc[statuses[id]] = value
                }
                return acc
              }, {})"
            />
            <div class="status-circles">
              <div
                  class="status-circle"
                  v-for="id in statusOrder"
                  :key="id"
              >
                <div
                    class="circle"
                    :style="{ background: getStatusColor(statuses[id]) }"
                >
                  {{ statusMap[id] || 0 }}
                </div>

                <div class="label">
                  {{ statuses[id] }}
                </div>
              </div>
            </div>
          </div>
          <div v-if="tab === 2">
            <div class="title">По категориям</div>
            <table class="table">
              <colgroup>
                <col style="width: 170px;" />
                <col style="width: 155px;" />
                <col style="width: 155px;" />
              </colgroup>
              <thead>
              <tr>
                <th>Категория</th>
                <th>По штату</th>
                <th>По списку</th>
                <th v-for="id in statusOrder" :key="id">
                  {{ statuses[id] }}
                </th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="c in categories" :key="c.id">
                <td>{{ c.name }}</td>
                <td>{{ c.shtat }}</td>
                <td>{{ c.personnel.length }}</td>
                <td v-for="id in statusOrder" :key="id">
                  {{ countStatus(c, id) }}
                </td>
              </tr>
              <tr style="font-weight:700;border-top:2px solid rgba(255,255,255,0.3);">
                <td>Итого</td>
                <td>{{ stats.shtat }}</td>
                <td>{{ stats.list }}</td>
                <td v-for="id in statusOrder" :key="id">
                  {{ statusMap[id] || 0 }}
                </td>
              </tr>
              </tbody>
            </table>
            <div class="title" style="margin-top:20px;">
              Личный состав
            </div>
            <table class="table">
              <colgroup>
                <col style="width: 60px;" />
                <col style="width: 160px;" />
                <col style="width: 260px;" />
              </colgroup>
              <thead>
              <tr>
                <th>№ <br>п/п</th>
                <th>Воинское звание</th>
                <th>ФИО</th>
                <th v-for="id in statusOrder" :key="id">
                  {{ statuses[id] }}
                </th>
              </tr>
              </thead>
              <tbody>
              <template v-for="c in categories" :key="c.id">
                <!-- Заголовок категории -->
                <tr style="background: rgba(255,255,255,0.05); font-weight:600;">
                  <td colspan="100%">
                    {{ c.name }} ({{ c.personnel.length }})
                  </td>
                </tr>

                <!-- Список людей -->
                <tr v-for="(p, index) in c.personnel" :key="p.id">
                  <td>{{ index + 1 }}</td>
                  <td>{{ getRank(p.rank_id) }}</td>
                  <td>{{ p.last_name }} {{ p.first_name }} {{ p.middle_name }}</td>

                  <td v-for="id in statusOrder" :key="id" style="text-align:center;">
                    <span v-if="p.current_status_id === id">✔</span>
                  </td>
                </tr>
              </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
  import { ref, onMounted, computed } from 'vue'
  import api from '../services/api.js'
  import HeaderFirst from './HeaderFirst.vue'
  import UnitNode from './UnitNode.vue'
  import PieChart from './PieChart.vue'
  import { ranks } from '../constants/ranks'

  const units = ref([])
  const selectedUnit = ref(null)
  const categories = ref([])
  const tab = ref(1)

  const statusOrder = [1, 5, 2, 3, 4, 6, 9, 7]

  const statuses = {
    1: 'Налицо',
    2: 'Лазарет',
    3: 'Госпиталь',
    4: 'Отпуск',
    5: 'Наряд',
    6: 'Командировка',
    9: 'Увольнение',
    7: 'Прочее',
  }

  const rankMap = Object.fromEntries(
      ranks.map(r => [r.id, r.name])
  )

  function getRank(id) {
    return rankMap[id] || '—'
  }

  const statusMap = computed(() => {
    const map = {}

    for (const c of categories.value) {
      for (const p of c.personnel) {
        const s = p.current_status_id
        map[s] = (map[s] || 0) + 1
      }
    }

    return map
  })

  const stats = computed(() => {
    let shtat = 0
    let list = 0

    for (const c of categories.value) {
      shtat += c.shtat
      list += c.personnel.length
    }

    const present = statusMap.value[1] || 0

    return {
      shtat,
      list,
      present,
      absent: list - present,
    }
  })

  const flatPersonnel = computed(() =>
      categories.value.flatMap(c => c.personnel)
  )

  onMounted(async () => {
    const { data } = await api.get('/api/units')
    units.value = data.data
  })

  async function selectUnit(unit) {
    selectedUnit.value = unit

    const { data } = await api.get(
        `/api/units/${unit.id}/personnel/grouped`
    )

    if (data.units && data.units.length) {
      categories.value = buildStructure(data.units)
    } else {
      categories.value = data.categories || []
    }
  }

  function countStatus(cat, statusId) {
    let count = 0

    for (const p of cat.personnel) {
      if (p.current_status_id === statusId) {
        count++
      }
    }

    return count
  }

  function getStatusColor(label) {
    const map = {
      'Налицо': '#22c55e',
      'Лазарет': '#f59e0b',
      'Госпиталь': '#ef4444',
      'Отпуск': '#3b82f6',
      'Наряд': '#a855f7',
      'Командировка': '#06b6d4',
      'Увольнение': '#f97316',
      'Прочее': '#94a3b8',
    }
    return map[label] || '#888'
  }

  function buildStructure(data) {
    let res = []

    for (let i = 0; i < data.length; i++) {
      let shtat = 0
      let personals = []

      if (data[i].units && Array.isArray(data[i].units) && data[i].units.length > 0) {
        shtat = countShtatUnits(data[i], shtat)
        personals = getPersonals(data[i])
      } else {
        shtat = Number(data[i].shtat) || 0
        personals = getPersonals(data[i])
      }

      res.push({
        id: data[i].id,
        name: data[i].name,
        shtat: shtat,
        personnel: personals,
      })
    }

    return res
  }

  function countShtatUnits(data, countShtat) {
    countShtat += Number(data.shtat) || 0

    if (data.units && Array.isArray(data.units)) {
      for (let i = 0; i < data.units.length; i++) {
        countShtat = countShtatUnits(data.units[i], countShtat)
      }
    }

    return countShtat
  }

  function getPersonals(data) {
    let personals = []

    if (Array.isArray(data.categories) && data.categories.length > 0) {
      for (let i = 0; i < data.categories.length; i++) {
        const personnel = data.categories[i].personnel
        if (Array.isArray(personnel) && personnel.length > 0) {
          personals = personals.concat(personnel)
        }
      }
    }

    if (data.units && Array.isArray(data.units) && data.units.length > 0) {
      for (let i = 0; i < data.units.length; i++) {
        const childUnit = data.units[i]
        personals = personals.concat(getPersonals(childUnit))
      }
    }
    return personals
  }
</script>