<template>
  <Header />
  <div class="page">
    <div class="card">
      <div class="title">Панель администратора</div>
      <p class="subtitle">Управление структурой академии и доступом пользователей</p>

      <div class="tabs">
        <button :class="['tab', activeTab === 'units' && 'active']" @click="activeTab = 'units'">Подразделения</button>
        <button :class="['tab', activeTab === 'users' && 'active']" @click="activeTab = 'users'">Пользователи</button>
        <button :class="['tab', activeTab === 'structure' && 'active']" @click="activeTab = 'structure'">Структура штата</button>
      </div>

      <div v-if="activeTab === 'units'">
        <div class="form-section">
          <div class="section-title">{{ editingUnit ? 'Редактирование подразделения' : 'Новое подразделение' }}</div>
          <div class="form-row">
            <input v-model="unitForm.name" placeholder="Название подразделения" />
            <select v-model="unitForm.parent_id">
              <option :value="1" disabled v-if="!flatUnits.find(u => u.id === 1)">
                -- Корневое подразделение --
              </option>
              <option v-for="u in flatUnits" :key="u.id" :value="u.id" :disabled="u.id === editingUnit?.id">
                {{ u.name }}
              </option>
            </select>
            <button class="btn" @click="saveUnit">{{ editingUnit ? 'Сохранить' : 'Добавить' }}</button>
            <button v-if="editingUnit" class="btn delete-btn-style" @click="cancelEditUnit">Отмена</button>
          </div>
        </div>

        <table class="table">
          <thead>
          <tr>
            <th class="num-col">ID</th>
            <th>Название</th>
            <th>Родительское подразд.</th>
            <th class="action-col">Действия</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="u in flatUnits" :key="u.id">
            <td class="text-center">{{ u.id }}</td>
            <td>{{ u.name }}</td>
            <td class="opacity-text">{{ getUnitName(u.parent_id) }}</td>
            <td class="text-center">
              <button class="btn-small btn-edit" @click="editUnit(u)">✎</button>
              <button class="delete btnml" @click="deleteUnit(u.id)">✕</button>
            </td>
          </tr>
          <tr v-if="!flatUnits.length">
            <td colspan="4" class="text-center opacity-text">Нет доступных подразделений</td>
          </tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="activeTab === 'users'">
        <div class="form-section">
          <div class="section-title">{{ editingUser ? 'Редактирование пользователя' : 'Новый пользователь' }}</div>
          <div class="form-row">
            <input v-model="userForm.name" placeholder="Имя" />
            <input v-model="userForm.login" placeholder="Логин" />
            <input v-model="userForm.password" type="password" :placeholder="editingUser ? 'Новый пароль (оставьте пустым)' : 'Пароль'" />
            <select v-model="userForm.role_id">
              <option value="" disabled>Выберите роль</option>
              <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
            </select>
            <select v-model="userForm.unit_id">
              <option value="" disabled>Выберите подразделение</option>
              <option v-for="u in flatUnits" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
            <button class="btn" @click="saveUser">{{ editingUser ? 'Сохранить' : 'Добавить' }}</button>
            <button v-if="editingUser" class="btn delete-btn-style" @click="cancelEditUser">Отмена</button>
          </div>
        </div>

        <table class="table">
          <thead>
          <tr>
            <th class="num-col">ID</th>
            <th>Имя</th>
            <th>Логин</th>
            <th>Роль</th>
            <th>Подразделение</th>
            <th class="action-col">Действия</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="user in users" :key="user.id">
            <td class="text-center">{{ user.id }}</td>
            <td>{{ user.name }}</td>
            <td>{{ user.login }}</td>
            <td>{{ getRoleName(user.role_id) }}</td>
            <td>{{ getUnitName(user.unit_id) }}</td>
            <td class="text-center">
              <button class="btn-small btn-edit" @click="editUser(user)">✎</button>
              <button class="delete btnml" @click="deleteUser(user.id)">✕</button>
            </td>
          </tr>
          <tr v-if="!users.length">
            <td colspan="6" class="text-center opacity-text">Пользователи не найдены</td>
          </tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="activeTab === 'structure'">
        <div class="form-section">
          <div class="section-title">Выберите подразделение для редактирования штата</div>
          <select v-model="selectedUnitForStructure" @change="loadStructureForUnit">
            <option value="" disabled>-- Выберите подразделение --</option>
            <option v-for="u in flatUnits" :key="u.id" :value="u.id">{{ u.name }}</option>
          </select>
        </div>

        <div v-if="selectedUnitForStructure">
          <div class="table">
            <div v-for="cat in structure" :key="cat.id" class="category">
              <div class="category-header">
                <b>{{ cat.name }}</b>
                <button class="delete" @click="removeCategory(cat)">✕</button>
              </div>
              <div class="positions">
                <div v-for="pos in cat.positions" :key="pos.id" class="row">
                  <div class="col">{{ pos.title }}</div>
                  <div class="col small">{{ pos.count }}</div>
                  <button class="delete" @click="removePosition(cat, pos)">✕</button>
                </div>
              </div>
              <div class="add-position">
                <input v-model="cat._newTitle" placeholder="Должность" />
                <input v-model="cat._newCount" type="number" placeholder="Кол-во" />
                <button class="btn-small" @click="addPosition(cat)">+</button>
              </div>
            </div>
          </div>
          <div class="add-category">
            <input v-model="newCategory" placeholder="Новая категория" />
            <button class="btn" @click="addCategory">Добавить</button>
          </div>
          <button class="btn btnmt" @click="saveStructure">Сохранить структуру</button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import api from '../services/api.js'
import Header from "./Header.vue"

const activeTab = ref('units')
const flatUnits = ref([])
const users = ref([])

const roles = ref([
  { id: 1, name: 'Строевой отдел' },
  { id: 2, name: 'Медицинская служба' },
  { id: 3, name: 'Командир подразделения' },
  { id: 4, name: 'Администратор' }
])

const editingUnit = ref(null)
const unitForm = reactive({ name: '', parent_id: 1 })

async function fetchUnits() {
  try {
    const { data } = await api.get('/api/units')
    const flatten = (nodes, parentId = null) => {
      let result = []
      for (const node of nodes) {
        result.push({ id: node.id, name: node.name, parent_id: parentId })
        if (node.children && node.children.length) {
          result = result.concat(flatten(node.children, node.id))
        }
      }
      return result
    }
    flatUnits.value = flatten(data.data || data)
  } catch (e) {
    console.error('Ошибка загрузки подразделений:', e)
  }
}

function getUnitName(id) {
  if (!id) return '—'
  const unit = flatUnits.value.find(u => u.id === id)
  return unit ? unit.name : 'Неизвестно'
}

function editUnit(unit) {
  editingUnit.value = unit
  unitForm.name = unit.name
  unitForm.parent_id = unit.parent_id ?? 1
}

function cancelEditUnit() {
  editingUnit.value = null
  unitForm.name = ''
  unitForm.parent_id = 1
}

async function saveUnit() {
  try {
    if (editingUnit.value) {
      await api.patch(`/api/units/${editingUnit.value.id}`, unitForm)
    } else {
      await api.post('/api/units', unitForm)
    }
    await fetchUnits()
    cancelEditUnit()
  } catch (e) {
    console.error(e)
  }
}

async function deleteUnit(id) {
  try {
    await api.delete(`/api/units/${id}`)
    if (selectedUnitForStructure.value === id) {
      selectedUnitForStructure.value = ''
      structure.value = []
    }
    await fetchUnits()
  } catch (e) {
    console.error(e)
  }
}

const editingUser = ref(null)
const userForm = reactive({ name: '', login: '', password: '', role_id: '', unit_id: '' })

async function fetchUsers() {
  try {
    const { data } = await api.get('/api/users')
    users.value = data.data || data
  } catch (e) {
    console.error('Ошибка загрузки пользователей:', e)
  }
}

function getRoleName(id) {
  const role = roles.value.find(r => r.id === id)
  return role ? role.name : '—'
}

function editUser(user) {
  editingUser.value = user
  userForm.name = user.name
  userForm.login = user.login
  userForm.password = '' 
  userForm.role_id = user.role_id
  userForm.unit_id = user.unit_id
}

function cancelEditUser() {
  editingUser.value = null
  userForm.name = ''
  userForm.login = ''
  userForm.password = ''
  userForm.role_id = ''
  userForm.unit_id = ''
}

async function saveUser() {
  const payload = { ...userForm }
  if (editingUser.value && !payload.password) delete payload.password

  try {
    if (editingUser.value) {
      await api.patch(`/api/users/${editingUser.value.id}`, payload)
    } else {
      await api.post('/api/users', payload)
    }
    await fetchUsers()
    cancelEditUser()
  } catch (e) {
    console.error(e)
  }
}

async function deleteUser(id) {
  try {
    await api.delete(`/api/users/${id}`)
    await fetchUsers()
  } catch (e) {
    console.error(e)
  }
}

const selectedUnitForStructure = ref('')
const structure = ref([])
const newCategory = ref('')

async function loadStructureForUnit() {
  if (!selectedUnitForStructure.value) return
  try {
    const { data } = await api.get(`/api/units/${selectedUnitForStructure.value}/structure`)
    structure.value = data.data || data || []
  } catch (e) {
    console.error('Ошибка загрузки структуры:', e)
    structure.value = [] 
  }
}

function addCategory() {
  if (!newCategory.value) return
  structure.value.push({
    id: null,
    name: newCategory.value,
    positions: [],
    _newTitle: '',
    _newCount: null
  })
  newCategory.value = ''
}

function removeCategory(cat) {
  structure.value = structure.value.filter(c => c.id !== cat.id)
}

function addPosition(cat) {
  if (!cat._newTitle || !cat._newCount) return
  cat.positions.push({
    id: Date.now(),
    title: cat._newTitle,
    count: Number(cat._newCount)
  })
  cat._newTitle = ''
  cat._newCount = null
}

function removePosition(cat, pos) {
  cat.positions = cat.positions.filter(p => p.id !== pos.id)
}

async function saveStructure() {
  if (!selectedUnitForStructure.value) return
  try {
    await api.post(`/api/units/${selectedUnitForStructure.value}/structure`, {
      categories: structure.value
    })
  } catch (e) {
    console.error(e)
  }
}

onMounted(async () => {
  await fetchUnits()
  await fetchUsers()
})
</script>

<style scoped>
.page { display: flex; justify-content: center; align-items: flex-start; padding: 15px; }
.card { width: 1100px; padding: 26px; border-radius: 10px; box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3); display: flex; flex-direction: column; gap: 15px; background: rgba(255, 255, 255, 0.02); }
.title { text-align: center; font-weight: 700; font-size: 18px; }
.subtitle { text-align: center; font-size: 14px; opacity: 0.8; margin-bottom: 10px; }

.tabs { display: flex; gap: 10px; margin-bottom: 10px; }
.tab { border: none; flex: 1; padding: 10px; border-radius: 10px; transition: background-color 0.5s ease, color 0.5s ease, transform 0.5s ease; box-shadow: 1px 1px 4px 2px rgba(0, 0, 0, 0.3); cursor: pointer; color: var(--text); background: var(--bg); font-family: "Tektur", sans-serif; font-size: 14px; font-weight: 600; }
.tab.active { background: var(--btn); }
.tab:hover { background: var(--btn-hover); color: var(--text-hover); transform: translateY(-2px); }

.form-section { background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid rgba(255, 255, 255, 0.05); }
.section-title { font-size: 14px; font-weight: 600; margin-bottom: 10px; opacity: 0.9; }
.form-row { display: grid; gap: 10px; align-items: center; }
input, select { flex: 1; padding: 8px 10px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(0, 0, 0, 0.3); font-family: "Tektur", sans-serif; color: rgba(255, 255, 255, 0.9); outline: none; }
input:focus, select:focus { border-color: var(--btn); }

.table { width: 100%; border-collapse: collapse; font-size: 13px; }
.table th, .table td { border: 1px solid rgba(255, 255, 255, 0.08); padding: 10px; vertical-align: middle; }
.table thead th { background: rgba(255, 255, 255, 0.05); font-weight: 600; text-align: left; }
.num-col { width: 60px; text-align: center !important; }
.action-col { width: 100px; text-align: center !important; }
.text-center { text-align: center; }
.opacity-text { opacity: 0.6; }

.btn { padding: 8px 16px; border-radius: 10px; border: none; background: var(--btn); color: var(--text); font-weight: 600; cursor: pointer; transition: background-color 0.5s ease, color 0.5s ease, transform 0.5s ease; font-family: "Tektur", sans-serif; }
.btn-small { padding: 6px 10px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
.btn-edit { background: rgba(255, 255, 255, 0.1); color: white; }
.btn-edit:hover { background: rgba(255, 255, 255, 0.2); transform: translateY(-2px); }
.delete-btn-style { background: rgba(255, 92, 92, 0.2); color: #ff5c5c; }
.delete-btn-style:hover { background: #ff5c5c; color: white; }
.btn:hover, .btn-small:hover { background: var(--btn-hover); color: var(--text-hover); transform: translateY(-2px); }
.delete { background: transparent; border: none; color: #ff5c5c; cursor: pointer; transition: transform 0.5s ease; font-size: 14px; }
.delete:hover { transform: translateY(-2px); }
.btnml { margin-left: 8px; }
.btnmt { margin-top: 10px; }

.category { padding-top: 10px; }
.category-header { display: flex; justify-content: space-between; padding-bottom: 5px; }
.positions .row { display: flex; align-items: center; padding: 5px; border-bottom: 1px solid rgba(255,255,255,0.05); }
.add-category, .add-position { display: flex; gap: 6px; padding-top: 10px; }
.col { flex: 1; }
.col.small { flex: 0.2; text-align: center; margin-left: auto; }
</style>