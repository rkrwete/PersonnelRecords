<template>
  <Header/>
  <div class="page">
    <div class="card">
      <div class="tabs">
        <button :class="['tab', activeTab==='structure' && 'active']" @click="activeTab='structure'">Структура</button>
        <button :class="['tab', activeTab==='personnel' && 'active']" @click="activeTab='personnel'">Личный состав</button>
      </div>

      <div v-if="activeTab==='structure'">
        <div class="title">Штат подразделения</div>
        <p class="subtitle">Категории и должности</p>
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
      <div v-else>
        <div class="title">Личный состав</div>
        <p class="subtitle">Редактирование таблице</p>
        <table class="table">
          <thead>
            <tr>
              <th>Должность</th>
              <th>Воинское звание</th>
              <th>ФИО</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in personnel" :key="p.id">
              <td>
                <select v-model="p.positionId">
                  <option disabled value="">Выберите должность</option>
                  <option v-for="pos in getAvailablePositions(p)" :key="pos.id" :value="pos.id">
                    {{ pos.title }}
                  </option>
                </select>
              </td>

              <td>
                <select v-model="p.rankId">
                  <option v-for="r in ranks" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </td>

              <td>
                <div class="fio">
                  <input v-model="p.lastName" placeholder="Фамилия" />
                  <input v-model="p.firstName" placeholder="Имя" />
                  <input v-model="p.middleName" placeholder="Отчество" />
                </div>
              </td>

              <td>
                <button class="delete" @click="removePerson(p)">✕</button>
              </td>
            </tr>
          </tbody>
        </table>
        <button class="btn-small" @click="addPerson">+ Добавить</button>
        <button class="btn btnmt btnml" @click="savePersonnel">Сохранить состав</button>
      </div>

    </div>
  </div>
</template>

<script setup>
  import { ref, computed, onMounted } from 'vue'
  import { useUnitStore } from '../stores/unit.js'
  import { storeToRefs } from 'pinia'
  import Header from "./Header.vue";
  import { ranks } from '../constants/ranks'

  const unitStore = useUnitStore()
  const { structure } = storeToRefs(unitStore)
  const activeTab = ref('structure')
  const newCategory = ref('')

  onMounted(async () => {
    await unitStore.fetchStructure()
    personnel.value = await unitStore.fetchPersonnel()
    console.log(personnel.value)
  })

  function addCategory() {
    if (!newCategory.value) return

    structure.value.push({
      id: 'new_' + Date.now(),
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
    try {
      await unitStore.updateStructure(structure.value)
    } catch (e) {
      alert('Ошибка при сохранении: ' + e.message)
    }
  }

  const personnel = ref([])

  const allPositions = computed(() => {
    return structure.value.flatMap(cat => cat.positions)
  })

  function getAvailablePositions(p) {
    return allPositions.value.filter(pos => {
      const used = personnel.value.filter(x => x.positionId === pos.id).length
      if (p.positionId === pos.id) return true
      return used < pos.count
    })
  }

  function getFirstAvailablePosition() {
    return allPositions.value.find(pos => {
      const used = personnel.value.filter(p => p.positionId === pos.id).length
      return used < pos.count
    })
  }

  function addPerson() {
    const pos = getFirstAvailablePosition()

    if (!pos) {
      alert('Нет свободных должностей')
      return
    }

    personnel.value.push({
      id: null,
      lastName: '',
      firstName: '',
      middleName: '',
      rankId: 1,
      positionId: pos.id
    })
    console.log(personnel)
  }

  function removePerson(p) {
    personnel.value = personnel.value.filter(x => x !== p)
  }

  async function savePersonnel() {
    try {
      const res = await unitStore.updatePersonnel(personnel.value)
      personnel.value = res.data ?? res
      console.log('Состав сохранен')
    } catch (e) {
      console.log('Ошибка при сохранении: ' + e.message)
    }
  }
</script>

<style scoped>
  .page {
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: system-ui;
  }

  .card {
    width: 800px;
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

  .tabs { display: flex; gap: 10px; }
  .tab { flex: 1; padding: 8px; border-radius: 10px; background: rgba(255,255,255,0.1); cursor: pointer; }
  .tab.active { background: #10b981; }

  .title { text-align: center; font-weight: 700; }
  .subtitle { text-align: center; font-size: 12px; opacity: 0.6; }

  .add-category, .add-position { display: flex; gap: 6px;padding-top: 10px; }

  input, select {
    width: 100%;
    padding: 6px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(0,0,0,0.25);
    color: white;
  }

  .table {
    width: 100%;
    border-collapse: collapse;
  }

  .table th, .table td {
    border: 1px solid rgba(255,255,255,0.1);
    padding: 6px;
  }

  .fio {
    display: flex;
    gap: 4px;
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

  .btnmt {
    margin-top: 10px;
  }

  .btnml {
    margin-left: 10px;
  }

  .btn-small {
    padding: 6px 10px;
    border-radius: 10px;
    border: none;
    background: #3b82f6;
    color: white;
    cursor: pointer;
  }

  .delete {
    background: transparent;
    border: none;
    color: #ff5c5c;
    cursor: pointer;
    margin-left: auto;
  }

  .category {
    padding-top: 10px;
  }

  .category-header {
    padding-bottom: 5px;
  }

  .positions .row {
    display: flex;
  }

  .row {
    display: flex;
    align-items: center;
    padding: 5px;
  }

  .col {
    width: 40vw;
  }

  .col.small {
    margin-left: auto;
  }
</style>
