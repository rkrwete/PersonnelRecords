<style scoped>
.page {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 15px;
  gap: 20px;
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
  margin: 0 0 15px 0;
}

.expense-card {
  width: 1400px;
  padding: 26px;
  border-radius: 10px;
  box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
  margin-top: 10px;
  table-layout: fixed;
}

.table th, .table td {
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
  font-weight: 600;
  background: rgba(255, 255, 255, 0.2);
  text-align: center;
}
</style>

<template>
  <Header/>
  <div class="page">
    <div class="expense-card" v-if="userStore.roleId !== 3">
      <div class="title" v-if="userStore.roleId == 1">Строевой отдел</div>
      <div class="title" v-if="userStore.roleId == 2">Мед. служба</div>
      <div class="title">Выбор подразделения</div>
      <p class="subtitle">Укажите подразделение для работы с личным составом</p>
      <TreeSelect
          v-model="selectedUnitId"
          :options="allUnits"
      />
    </div>

    <div class="expense-card" v-if="categories.length">
      <div class="title">{{ unitName }}</div>
      <div class="subtitle">Расход личного состава</div>

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
          <col style="width: 150px">
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
          <th>Примечание</th>
        </tr>
        </thead>
        <tbody>
        <template v-for="(cat, index) in categories" :key="cat.id">
          <RowUnit
              :unit="cat"
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
          <td></td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import Header from "./Header.vue";
import TreeSelect from "./TreeSelect.vue";
import RowUnit from "./RowUnit.vue";
import { ref, onMounted, computed, watch, provide } from 'vue'
import api from "../services/api.js";
import { useUserStore } from "../stores/user.js";
import { ranks } from '../constants/ranks'
import { statuses } from "../constants/statuses.js";

const userStore = useUserStore()
const allUnits = ref([])
const selectedUnitId = ref(null)
const unitName = ref('')
const categories = ref([])
const expanded = ref(new Set())

const statusOrder = computed(() => statuses.map(s => s.id))
const statusMapById = computed(() => Object.fromEntries(statuses.map(s => [s.id, s])))
const rankMap = Object.fromEntries(ranks.map(r => [r.id, r.name]))

function getRank(id) {
  return rankMap[id] || '—'
}

const allowedStatuses = computed(() => {
  if (userStore.roleId === 1) return [1, 2, 5, 6]
  if (userStore.roleId === 2) return [1, 3, 4]
  if (userStore.roleId === 3) {
    const forbidden = [3, 4, 5, 6]
    return statusOrder.value.filter(id => !forbidden.includes(id))
  }
  return statusOrder.value
})

const lockedRowStatuses = computed(() => {
  if (userStore.roleId === 3) return [3, 4, 5, 6]
  return []
})

async function updateStatus(person, newStatusId) {
  if (person.current_status_id === newStatusId) return

  const oldStatus = person.current_status_id
  person.current_status_id = newStatusId

  try {
    await api.patch(`/api/personnel/${person.id}`, {
      current_status_id: newStatusId,
      note: person.note
    })
  } catch (e) {
    person.current_status_id = oldStatus
    console.error(e)
  }
}

async function saveNote(person, newNote) {
  if (person.note === newNote) return

  const oldNote = person.note
  person.note = newNote

  try {
    await api.patch(`/api/personnel/${person.id}`, {
      current_status_id: person.current_status_id,
      note: newNote
    })
  } catch (e) {
    person.note = oldNote
    console.error("Ошибка при сохранении примечания:", e)
  }
}

provide('updateStatus', updateStatus)
provide('saveNote', saveNote)
provide('statusOrder', statusOrder)
provide('getRank', getRank)
provide('countStatusRecursive', countStatusRecursive)
provide('allowedStatuses', allowedStatuses)
provide('lockedRowStatuses', lockedRowStatuses)
provide('showNoteColumn', true)
function countStatusRecursive(unit, statusId) {
  let count = 0
  if (unit.personnel) {
    for (const p of unit.personnel) {
      if (p.current_status_id === statusId) count++
    }
  }
  if (unit.children) {
    for (const child of unit.children) {
      count += countStatusRecursive(child, statusId)
    }
  }
  return count
}

const statusMap = computed(() => {
  const map = {}
  function traverse(units) {
    for (const u of units) {
      if (u.personnel) {
        for (const p of u.personnel) {
          const s = p.current_status_id
          map[s] = (map[s] || 0) + 1
        }
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
    shtat += c.totalShtat ?? c.shtat ?? 0
    list += c.totalPersonnelCount ?? c.personnel?.length ?? 0
  }
  return { shtat, list }
})

function toggle(id) {
  if (expanded.value.has(id)) expanded.value.delete(id)
  else expanded.value.add(id)
}

onMounted(async () => {
  if (userStore.roleId == 3) {
    selectedUnitId.value = userStore.unitId
    unitName.value = userStore.unitName
    await fetchUnitData(userStore.unitId)
  } else if (userStore.roleId == 3 && data.categories) {
    categories.value = data.categories.map(c => ({
      id: c.id || Math.random(),
      name: c.name,
      shtat: c.shtat,
      totalShtat: c.shtat,
      totalPersonnelCount: c.personnel?.length || 0,
      personnel: c.personnel || [],
      children: []
    }))
  } else {
    const { data } = await api.get('/api/units')
    console.log(data)
    function normalize(nodes) {
      return nodes.map(n => ({
        ...n,
        children: n.children ? normalize(n.children) : []
      }))
    }
    allUnits.value = normalize(data.data)
  }
})

watch(selectedUnitId, async (id) => {
  if (id && userStore.roleId != 3) {
    const findName = (nodes) => {
      for (let n of nodes) {
        if (n.id === id) return n.name
        if (n.children) {
          let res = findName(n.children)
          if (res) return res
        }
      }
      return 'Подразделение'
    }
    unitName.value = findName(allUnits.value)
    await fetchUnitData(id)
  }
})

async function fetchUnitData(id) {
  try {
    const { data } = await api.get(`/api/units/${id}/personnel/grouped`)
    if (data.units && data.units.length) {
      categories.value = buildStructure(data.units)
    } else if (data.categories) {
      categories.value = data.categories.map(c => ({
        id: c.id || Math.random(),
        name: c.name,
        totalShtat: c.shtat,
        totalPersonnelCount: c.personnel?.length || 0,
        personnel: c.personnel || [],
        children: []
      }))
    } else {
      categories.value = []
    }
  } catch (e) {
    console.error(e)
  }
}

function buildStructure(data) {
  return data.map(unit => {
    const localShtat = Number(unit.shtat) || 0
    const localPersonnel = extractLocalPersonnel(unit)
    const children = unit.units ? buildStructure(unit.units) : []

    let totalShtat = localShtat
    let totalPersonnelCount = localPersonnel.length

    for (const child of children) {
      totalShtat += child.totalShtat
      totalPersonnelCount += child.totalPersonnelCount
    }

    return {
      id: unit.id,
      name: unit.name,
      shtat: localShtat,
      totalShtat: totalShtat,
      personnel: localPersonnel,
      totalPersonnelCount: totalPersonnelCount,
      children: children
    }
  })
}

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
</script>