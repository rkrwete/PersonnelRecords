<template>
  <Header/>
  <div class="page">
    <div class="card">
      <div>
        <div class="title">Личный состав</div>
        <p class="subtitle">Редактирование таблицы</p>
        <table class="table">
          <thead>
          <tr>
            <th class="num-col">№</th>
            <th>Фото</th> <th>Должность</th>
            <th>Воинское звание</th>
            <th>ФИО</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(p, index) in personnel" :key="p.id || index">
            <td class="text-center">{{ index + 1 }}</td>
            
            <td class="text-center">
              <div class="photo-upload">
                <label :for="'photo-input-' + index" class="photo-label">
                  <img v-if="p.photo" :src="p.photo" alt="Фото" class="photo-preview" />
                  <div v-else class="photo-placeholder">
                    <span>+</span>
                  </div>
                </label>
                <input 
                  :id="'photo-input-' + index" 
                  type="file" 
                  accept="image/*" 
                  class="hidden-input" 
                  @change="e => onPhotoChange(e, p)" 
                />
              </div>
            </td>
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
  const activeTab = ref('personnel')
  const newCategory = ref('')
  const personnel = ref([])

  const initData = async () => {
    await unitStore.fetchStructure()
    personnel.value = await unitStore.fetchPersonnel()
  }

  initData()

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
    try {
      await unitStore.updateStructure(structure.value)
    } catch (e) {
      alert('Ошибка при сохранении: ' + e.message)
    }
  }

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

  function onPhotoChange(event, p) {
    const file = event.target.files[0]
    if (file) {
      const reader = new FileReader()
      reader.onload = (e) => {
        p.photo = e.target.result
      }
      reader.readAsDataURL(file)
    }
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
      positionId: pos.id,
      photo: null
    })
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
    padding: 15px;
  }

  .card {
    width: 1200px;
    padding: 26px;
    border-radius: 10px;
    box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .tabs { display: flex; gap: 10px; }
  .tab {
    border: none;
    flex: 1;
    padding: 8px;
    border-radius: 10px;
    transition: background-color 0.5s ease, color 0.5s ease, transform 0.5s ease;
    box-shadow: 1px 1px 4px 2px rgba(0, 0, 0, 0.3);
    cursor: pointer;
    color: var(--text);
    background: var(--bg);
  }

  .tab.active {
    background: var(--btn);
  }

  .tab:hover {
    background: var(--btn-hover);
    color: var(--text-hover);
    transform: translateY(-2px);
  }

  .title { text-align: center; font-weight: 700; }
  .subtitle { text-align: center; font-size: 12px; opacity: 0.8; }

  .add-category, .add-position { display: flex; gap: 6px;padding-top: 10px; }

  input, select {
    width: 100%;
    padding: 6px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(0,0,0,0.2);
    font-family: "Tektur", sans-serif;
    color: rgba(255,255,255,0.9);
  }

  .table {
    width: 100%;
    border-collapse: collapse;
  }

  .table th, .table td {
    border: 1px solid rgba(255,255,255,0.2);
    padding: 6px;
    vertical-align: middle;
  }

  .num-col {
    width: 40px;
    text-align: center;
  }
  .text-center {
    text-align: center;
  }

  .photo-upload {
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .photo-label {
    cursor: pointer;
    display: block;
    transition: transform 0.2s ease;
  }

  .photo-label:hover {
    transform: scale(1.05);
  }

  .photo-preview {
    width: 40px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid rgba(255,255,255,0.3);
  }

  .photo-placeholder {
    width: 40px;
    height: 50px;
    border: 1px dashed rgba(255,255,255,0.4);
    border-radius: 6px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: rgba(255,255,255,0.6);
    font-size: 20px;
  }

  .photo-placeholder:hover {
    border-color: rgba(255,255,255,0.8);
    color: rgba(255,255,255,0.9);
  }

  .hidden-input {
    display: none; 
  }

  .fio {
    display: flex;
    gap: 4px;
  }

  .btn {
    padding: 10px;
    border-radius: 10px;
    border: none;
    background: var(--btn);
    color: var(--text);
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.5s ease, color 0.5s ease, transform 0.5s ease;
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
    background: var(--btn);
    color: var(--text);
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.5s ease, color 0.5s ease, transform 0.5s ease;
  }

  .btn:hover, .btn-small:hover  {
    background: var(--btn-hover);
    color: var(--text-hover);
    transform: translateY(-2px);
  }

  .delete {
    background: transparent;
    border: none;
    color: #ff5c5c;
    cursor: pointer;
    margin-left: auto;
    transition: transform 0.5s ease;
  }

  .delete:hover {
    transform: translateY(-2px);
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