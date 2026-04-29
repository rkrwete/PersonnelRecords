<style scoped>
.page {
  display: flex;
  justify-content: center;
  align-items: center;
  font-family: system-ui;
}

.title {
  text-align: center;
  font-weight: 700;
}
.subtitle {
  text-align: center;
  font-size: 12px; opacity: 0.6;
}

.category-header {
  margin-bottom: 10px;
}

input, select {
  width: 95%;
  padding: 6px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.15);
  background: rgba(0,0,0,0.25);
  color: white;
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.table th, .table td {
  border: 1px solid rgba(255,255,255,0.1);
  padding: 6px;
}

.expense-card {
  width: 1200px;
  padding: 26px;
  border-radius: 20px;
  background: rgba(0,0,0,0.35);
  backdrop-filter: blur(14px);
  color: #e6f4ef;
  box-shadow: 0 20px 80px rgba(0,0,0,0.5);
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.table th:nth-of-type(1) { width: 17vw; }
.table th:nth-of-type(2) { width: 10vw; }
.table th:nth-of-type(3) { width: 25vw; }

.dropdown {
  position: absolute;
  top: 100%;
  width: 100%;
  background: #0e2d21;
  border-radius: 10px;
  margin-top: 4px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 100;
}

.select-wrapper {
  position: relative;
}

.option {
  padding: 6px;
  cursor: pointer;
}

.option:hover {
  background: rgba(255,255,255,0.1);
}

.btn {
  padding: 10px;
  border-radius: 12px;
  border: none;
  background: #10b981;
  color: white;
  font-weight: 600;
  cursor: pointer;
}

.fl {
  display: flex;
  flex-direction: column;
}

.fl input {
  width: 99%;
}

.fl select {
  width: unset;
}
</style>

<template>
  <Header/>
  <div class="page">
    <div class="expense-card" v-if="userStore.roleId !== 2">
      <div class="title">Расход личного состава</div>
      <p class="subtitle">Состояние и примечания</p>

      <table class="table">
        <thead>
        <tr>
          <th>Категория</th>
          <th>По штату</th>
          <th>По списку</th>
          <th>Налицо</th>
          <th>Наряд</th>
          <th>Госпиталь</th>
          <th>Лазарет</th>
          <th>Увольнение</th>
          <th>Отпуск</th>
          <th>Командировка</th>
          <th>Прочее</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="row in summary" :key="row.name">
          <td><b>{{ row.name }}</b></td>
          <td>{{ row.shtat }}</td>
          <td>{{ row.list }}</td>
          <td>{{ row.present }}</td>
          <td>{{ row.duty }}</td>
          <td>{{ row.hospital }}</td>
          <td>{{ row.lazaret }}</td>
          <td>{{ row.leave }}</td>
          <td>{{ row.vacation }}</td>
          <td>{{ row.trip }}</td>
          <td>{{ row.other }}</td>
        </tr>
        </tbody>
      </table>

      <div class="title">Список личного состава</div>

      <div v-for="cat in categories" :key="cat.id" class="category">
        <div class="category-header">
          <b>{{ cat.name }}</b>
        </div>

        <table class="table">
          <thead>
          <tr>
            <th>Должность</th>
            <th>Воинское звание</th>
            <th>ФИО</th>
            <th>Состояние</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="p in cat.personnel" :key="p.id">
            <td>{{ p.position.title }}</td>
            <td>{{ ranks.find(r => r.id === p.rank_id)?.name || '—' }}</td>
            <td>{{ p.last_name }} {{ p.first_name }} {{ p.middle_name }}</td>
            <td>
              <select v-model="p.current_status_id" @change="updateStatus(p)">
                <option v-for="s in statuses" :value="s.id">
                  {{ s.name }}
                </option>
              </select>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="expense-card" v-else>
      <div class="title">Назначение статуса</div>
      <p class="subtitle">Выбор подразделения и военнослужащего</p>
      <div>
        <TreeSelect
            v-model="selectedCategoryId"
            :options="units"
        />
      </div>
      <div v-if="selectedCategoryId" class="fl">
        <label class="title">Военнослужащий</label>
        <div class="select-wrapper">
        <input
            v-model="personSearch"
            placeholder="Поиск..."
            @focus="showDropdown = true"
            @input="showDropdown = true"
        />
        <div v-if="showDropdown && filteredPersonnel.length" class="dropdown">
          <div
              v-for="p in filteredPersonnel"
              :key="p.id"
              class="option"
              @click="selectPerson(p)"
          >
            {{ p.lastName }} {{ p.firstName }} {{ p.middleName }}
          </div>
        </div></div>
      </div>
      <div v-if="selectedPersonId" class="fl">
        <label class="title">Статус</label>
        <select v-model="selectedStatus">
          <option :value="2">Лазарет</option>
          <option :value="3">Госпиталь</option>
        </select>
      </div>
      <button @click="assignStatus" v-if="selectedPersonId" class="btn">
        Назначить
      </button>
    </div>
  </div>
</template>

<script setup>
import Header from "./Header.vue";
import { ref, onMounted, watch, computed } from 'vue'
import api from "../services/api.js";
import { useUserStore } from "../stores/user.js";
import { ranks } from '../constants/ranks'
import TreeSelect from "./TreeSelect.vue";

const statuses = [
  { id: 1, name: 'Налицо' },
  { id: 5, name: 'Наряд' },
  { id: 9, name: 'Увольнение' },
  { id: 7, name: 'Прочее' },
]

const unit = ref(null)
const categories = ref([])
const userStore = useUserStore()
if (userStore.roleId == 3) {
  onMounted(async () => {
    const { data } = await api.get(`/api/units/${userStore.unitId}/personnel/grouped`)
    unit.value = data
    categories.value = data?.categories || []
  })
} else {
  onMounted(async () => {
    const { data } = await api.get('/api/units')

    function normalize(nodes) {
      return nodes.map(n => ({
        ...n,
        children: n.children ? normalize(n.children) : []
      }))
    }

    units.value = normalize(data.data)
  })
}


const summary = computed(() => {
  const statusMap = {
    1: 'present',
    2: 'lazaret',
    3: 'hospital',
    4: 'vacation',
    5: 'duty',
    6: 'trip',
    9: 'leave',
    7: 'other',
  }

  const rows = categories.value.map(cat => {
    const row = {
          name: cat.name,
          shtat: cat.shtat,
          list: cat.personnel?.length || 0,
          present: 0,
          duty: 0,
          hospital: 0,
          lazaret: 0,
          leave: 0,
          vacation: 0,
          trip: 0,
          other: 0,
        }

    ;(cat.personnel || []).forEach(p => {
      const key = statusMap[p.current_status_id] || 'other'
      row[key]++
    })

    return row
  })

  const total = {
    name: 'Итого',
    shtat: 0,
    list: 0,
    present: 0,
    duty: 0,
    hospital: 0,
    lazaret: 0,
    leave: 0,
    vacation: 0,
    trip: 0,
    other: 0,
  }

  rows.forEach(r => {
    Object.keys(total).forEach(k => {
      if (k !== 'name') total[k] += r[k]
    })
  })

  return [...rows, total]
})

async function updateStatus(p) {
  const old = p.current_status_id

  try {
    await api.patch(`/api/personnel/${p.id}`, {
      current_status_id: p.current_status_id
    })
  } catch (e) {
    p.current_status_id = old
    console.error(e)
  }
}

/////
const selectedCategoryId = ref('')
const selectedPersonId = ref('')
const selectedStatus = ref(2)
const personSearch = ref('')
const units = ref([])
const personnel = ref([])
const showDropdown = ref(false)

async function assignStatus() {
  if (!selectedPersonId.value) return

  try {
    await api.patch(`/api/personnel/${selectedPersonId.value}`, {
      current_status_id: selectedStatus.value
    })

    const person = personnel.value.find(
        p => p.id === selectedPersonId.value
    )

    if (person) {
      person.current_status_id = selectedStatus.value
    }

  } catch (e) {
    console.error(e)
  }
}

watch(selectedCategoryId, async (id) => {
  if (!id) return

  selectedPersonId.value = ''
  personnel.value = []

  try {
    const { data } = await api.get(`/api/units/${id}/personnel`)
    personnel.value = data.data || data
    console.log(personnel.value)
  } catch (e) {
    console.error(e)
  }
})

const filteredPersonnel = computed(() => {
  if (!personSearch.value) return personnel.value

  const q = personSearch.value.toLowerCase()

  return personnel.value.filter(p =>
      `${p.lastName} ${p.firstName} ${p.middleName || ''}`
          .toLowerCase()
          .includes(q)
  )
})

function selectPerson(p) {
  selectedPersonId.value = p.id
  personSearch.value = `${p.lastName} ${p.firstName} ${p.middleName}`
  showDropdown.value = false
}
</script>