<template>
  <div class="page">
    <div class="layout">
      <!-- Левая боковая панель - ЗАМЕТКИ -->
      <div class="sidebar">
        <div class="title">Мои заметки</div>
        <div class="notes-list-sidebar" v-if="allNotes.length">
          <div 
            v-for="note in sortedNotes" 
            :key="note.id"
            class="note-item-sidebar"
            @click="selectNoteDate(note.date)"
          >
            <div class="note-date-sidebar">{{ formatDateLong(note.date) }}</div>
            <div class="note-preview-sidebar">{{ note.content.substring(0, 50) }}...</div>
          </div>
        </div>
        <div v-else class="empty-notes-sidebar">Нет заметок</div>
      </div>

      <!-- Основной контент - календарь -->
      <div class="content">
        <div class="card">
          <div class="header-block">
            <div class="header-text">
              <div class="title">Календарь памятных дат</div>
              <div class="subtitle">{{ currentMonthName }} {{ currentYear }}</div>
            </div>
            <div class="calendar-controls">
              <button class="header-btn" @click="previousMonth">← Предыдущий</button>
              <button class="header-btn today-btn" @click="goToToday">Сегодня</button>
              <button class="header-btn" @click="nextMonth">Следующий →</button>
            </div>
          </div>

          <div class="calendar">
            <div class="weekdays">
              <div v-for="day in weekdays" :key="day" class="weekday">{{ day }}</div>
            </div>
            <div class="calendar-days">
              <div 
                v-for="(day, index) in calendarDays" 
                :key="index"
                class="calendar-day"
                :class="{
                  'other-month': !day.isCurrentMonth,
                  'today': day.isToday,
                  'has-memorable': day.hasMemorableDate,
                  'has-notes': day.notes && day.notes.length > 0
                }"
                @click="selectDay(day)"
              >
                <div class="day-number">{{ day.day }}</div>
                <div class="memorable-names">
                  <div 
                    v-for="md in day.memorableDates" 
                    :key="md.id"
                    class="memorable-name"
                    :style="{ backgroundColor: md.color }"
                    :title="md.name"
                  >
                    {{ md.name }}
                  </div>
                </div>
                <div v-if="day.notes && day.notes.length > 0" class="notes-count" :title="'Заметок: ' + day.notes.length">
                  📝 {{ day.notes.length }}
                </div>
              </div>
            </div>
          </div>

          <!-- Модальное окно для заметок -->
          <div v-if="selectedDay" class="modal-overlay" @click="closeModal">
            <div class="modal-content" @click.stop>
              <div class="modal-header">
                <h3>{{ formatDateLong(selectedDay.dateStr) }}</h3>
                <button class="close-btn" @click="closeModal">×</button>
              </div>
              
              <div class="notes-list-container" v-if="selectedDay.notes && selectedDay.notes.length">
                <div class="notes-title">Заметки ({{ selectedDay.notes.length }}):</div>
                <div 
                  v-for="note in selectedDay.notes" 
                  :key="note.id"
                  class="note-card"
                >
                  <div class="note-content">
                    <p class="note-text">{{ note.content }}</p>
                    <div class="note-actions">
                      <button class="edit-btn" @click="startEditNote(note)">✏️ Редактировать</button>
                      <button class="delete-btn" @click="deleteNote(note.id)">🗑️ Удалить</button>
                    </div>
                  </div>
                </div>
              </div>
              
              <div v-if="editingNoteId" class="edit-note-section">
                <div class="notes-title">Редактирование заметки:</div>
                <textarea 
                  v-model="editNoteContent" 
                  class="note-input"
                  rows="3"
                  placeholder="Текст заметки..."
                ></textarea>
                <div class="edit-actions">
                  <button class="save-edit-btn" @click="updateNote" :disabled="saving">Сохранить</button>
                  <button class="cancel-edit-btn" @click="cancelEdit">Отмена</button>
                </div>
              </div>
              
              <div class="new-note-section">
                <div class="notes-title">Новая заметка:</div>
                <textarea 
                  v-model="newNoteContent" 
                  class="note-input"
                  rows="3"
                  placeholder="Введите текст заметки..."
                ></textarea>
                <button 
                  class="save-note-btn" 
                  @click="createNote" 
                  :disabled="!newNoteContent.trim() || saving"
                >
                  {{ saving ? 'Сохранение...' : '➕ Добавить заметку' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../services/api.js'

// Состояние
const currentDate = ref(new Date())
const memorableDates = ref([])
const allNotes = ref([])
const selectedDay = ref(null)
const newNoteContent = ref('')
const editNoteContent = ref('')
const editingNoteId = ref(null)
const saving = ref(false)

// Константы
const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

// Вычисляемые свойства
const currentYear = computed(() => currentDate.value.getFullYear())
const currentMonth = computed(() => currentDate.value.getMonth())
const currentMonthName = computed(() => currentDate.value.toLocaleString('ru', { month: 'long' }))

// Сортировка заметок по календарной дате (от старых к новым)
const sortedNotes = computed(() => {
  return [...allNotes.value].sort((a, b) => new Date(a.date) - new Date(b.date))
})

// Загрузка памятных дат
async function loadMemorableDates() {
  try {
    const response = await api.get('/api/memorable-dates')
    memorableDates.value = response.data
    console.log('Загружено памятных дат:', memorableDates.value.length)
  } catch (error) {
    console.error('Ошибка загрузки памятных дат:', error)
  }
}

// Загрузка заметок с нормализацией дат
async function loadNotes() {
  try {
    const response = await api.get('/api/calendar/notes')
    allNotes.value = (Array.isArray(response.data) ? response.data : []).map(note => ({
      ...note,
      date: note.date ? note.date.split('T')[0] : note.date
    }))
    console.log('Загружено заметок:', allNotes.value.length)
  } catch (error) {
    console.error('Ошибка загрузки заметок:', error)
    allNotes.value = []
  }
}

// Получение памятных дат для конкретного месяца и года
function getMemorableDatesForMonth(year, month) {
  return memorableDates.value.filter(md => {
    const [mdMonth] = md.date.split('-')
    return parseInt(mdMonth) - 1 === month
  }).map(md => ({
    ...md,
    date: new Date(year, parseInt(md.date.split('-')[0]) - 1, parseInt(md.date.split('-')[1]))
  }))
}

// Построение календарной сетки
const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value

  const firstDayOfMonth = new Date(year, month, 1)
  let startWeekday = firstDayOfMonth.getDay()
  startWeekday = startWeekday === 0 ? 6 : startWeekday - 1

  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const monthMemorableDates = getMemorableDatesForMonth(year, month)

  const notesByDate = new Map()
  allNotes.value.forEach(note => {
    if (note && note.date) {
      if (!notesByDate.has(note.date)) {
        notesByDate.set(note.date, [])
      }
      notesByDate.get(note.date).push(note)
    }
  })

  const days = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  const prevMonthLastDay = new Date(year, month, 0).getDate()
  for (let i = startWeekday - 1; i >= 0; i--) {
    const dayDate = new Date(year, month - 1, prevMonthLastDay - i)
    const dateStr = formatDateYMD(dayDate)
    days.push({
      day: prevMonthLastDay - i,
      date: dayDate,
      dateStr: dateStr,
      isCurrentMonth: false,
      isToday: false,
      hasMemorableDate: false,
      memorableDates: [],
      notes: notesByDate.get(dateStr) || []
    })
  }

  for (let i = 1; i <= daysInMonth; i++) {
    const dayDate = new Date(year, month, i)
    const isToday = dayDate.toDateString() === today.toDateString()
    const dateStr = formatDateYMD(dayDate)
    const dayMemorableDates = monthMemorableDates.filter(md => md.date.getDate() === i)
    const dayNotes = notesByDate.get(dateStr) || []

    days.push({
      day: i,
      date: dayDate,
      dateStr: dateStr,
      isCurrentMonth: true,
      isToday,
      hasMemorableDate: dayMemorableDates.length > 0,
      memorableDates: dayMemorableDates,
      notes: dayNotes
    })
  }

  let remaining = 42 - days.length
  for (let i = 1; i <= remaining; i++) {
    const dayDate = new Date(year, month + 1, i)
    const dateStr = formatDateYMD(dayDate)
    days.push({
      day: i,
      date: dayDate,
      dateStr: dateStr,
      isCurrentMonth: false,
      isToday: false,
      hasMemorableDate: false,
      memorableDates: [],
      notes: notesByDate.get(dateStr) || []
    })
  }

  return days
})

// Создание заметки
async function createNote() {
  if (!selectedDay.value || !newNoteContent.value.trim()) return
  
  saving.value = true
  try {
    await api.post('/api/calendar/notes', {
      date: selectedDay.value.dateStr,
      content: newNoteContent.value.trim()
    })
    await loadNotes()
    newNoteContent.value = ''
    
    const updatedDay = calendarDays.value.find(d => d.dateStr === selectedDay.value.dateStr)
    if (updatedDay) {
      selectedDay.value = updatedDay
    }
    alert('Заметка добавлена')
  } catch (error) {
    console.error('Ошибка создания заметки:', error)
    alert('Не удалось создать заметку')
  } finally {
    saving.value = false
  }
}

// Редактирование заметки
function startEditNote(note) {
  editingNoteId.value = note.id
  editNoteContent.value = note.content
}

async function updateNote() {
  if (!editNoteContent.value.trim()) return
  
  saving.value = true
  try {
    await api.put(`/api/calendar/notes/${editingNoteId.value}`, {
      content: editNoteContent.value.trim()
    })
    await loadNotes()
    cancelEdit()
    const updatedDay = calendarDays.value.find(d => d.dateStr === selectedDay.value.dateStr)
    if (updatedDay) {
      selectedDay.value = updatedDay
    }
    alert('Заметка обновлена')
  } catch (error) {
    console.error('Ошибка обновления заметки:', error)
    alert('Не удалось обновить заметку')
  } finally {
    saving.value = false
  }
}

// Удаление заметки
async function deleteNote(noteId) {
  if (!confirm('Удалить заметку?')) return
  
  try {
    await api.delete(`/api/calendar/notes/${noteId}`)
    await loadNotes()
    const updatedDay = calendarDays.value.find(d => d.dateStr === selectedDay.value.dateStr)
    if (updatedDay) {
      selectedDay.value = updatedDay
    }
    alert('Заметка удалена')
  } catch (error) {
    console.error('Ошибка удаления заметки:', error)
    alert('Не удалось удалить заметку')
  }
}

function cancelEdit() {
  editingNoteId.value = null
  editNoteContent.value = ''
}

// Выбор дня
function selectDay(day) {
  selectedDay.value = day
  newNoteContent.value = ''
  cancelEdit()
}

// Выбор заметки из левой панели
function selectNoteDate(dateStr) {
  const day = calendarDays.value.find(d => d.dateStr === dateStr)
  if (day) {
    selectDay(day)
    const date = new Date(dateStr)
    currentDate.value = new Date(date.getFullYear(), date.getMonth(), 1)
  }
}

function closeModal() {
  selectedDay.value = null
  newNoteContent.value = ''
  cancelEdit()
}

// Навигация
function previousMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1)
}

function nextMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1)
}

function goToToday() {
  currentDate.value = new Date()
}

// Форматирование дат
function formatDateYMD(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

function formatDateShort(date) {
  if (!date) return ''
  const day = date.getDate()
  const month = date.toLocaleString('ru', { month: 'short' })
  return `${day} ${month}`
}

function formatDateLong(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleString('ru', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long'
  })
}

// Следим за изменением заметок
watch(allNotes, () => {
  if (selectedDay.value) {
    const updatedDay = calendarDays.value.find(d => d.dateStr === selectedDay.value.dateStr)
    if (updatedDay) {
      selectedDay.value = updatedDay
    }
  }
}, { deep: true })

// Инициализация
onMounted(async () => {
  await loadMemorableDates()
  await loadNotes()
})
</script>

<style scoped>
.page {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 15px;
  height: calc(100vh - 70px);
  overflow: hidden;
}

.layout {
  display: flex;
  gap: 20px;
  width: 1400px;
  max-width: 100%;
  height: 100%;
}

.sidebar {
  width: 320px;
  padding: 16px 10px;
  border-radius: 10px;
  height: 100%;
  overflow-y: auto;
  box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
  scrollbar-width: thin;
}

.content {
  flex: 1;
  height: 100%;
  overflow-y: auto;
}

.card {
  padding: 20px;
  border-radius: 10px;
  box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;
}

.header-block {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-shrink: 0;
}

.header-text {
  text-align: center;
  flex: 1;
}

.calendar-controls {
  display: flex;
  gap: 10px;
}

.header-btn {
  padding: 6px 12px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  background: var(--btn);
  color: var(--text);
  font-size: 14px;
  transition: all 0.3s ease;
  font-family: "Tektur", sans-serif;
}

.header-btn:hover {
  background: var(--btn-hover);
  color: var(--text-hover);
  transform: translateY(-2px);
}

.today-btn {
  background: #4CAF50;
}

.today-btn:hover {
  background: #45a049;
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

/* Календарь - фиксированные ячейки */
.calendar {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  width: 100%;
}

.weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  margin-bottom: 8px;
  flex-shrink: 0;
}

.weekday {
  text-align: center;
  padding: 8px;
  font-weight: bold;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 5px;
  font-size: 13px;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}

.calendar-day {
  aspect-ratio: 1 / 1;
  min-width: 0;
  padding: 4px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.03);
  cursor: pointer;
  position: relative;
  display: flex;
  flex-direction: column;
  transition: all 0.3s ease;
  overflow: hidden;
}

/* Адаптивные минимальные высоты */
@media (min-width: 1400px) {
  .calendar-day {
    min-height: 90px;
  }
}

@media (max-width: 1399px) and (min-width: 1000px) {
  .calendar-day {
    min-height: 75px;
  }
}

@media (max-width: 999px) {
  .calendar-day {
    min-height: 60px;
  }
}

.calendar-day:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-1px);
}

.calendar-day.other-month {
  opacity: 0.3;
}

.calendar-day.today {
  border: 2px solid #4CAF50;
  background: rgba(76, 175, 80, 0.1);
}

.calendar-day.has-memorable {
  background: rgba(255, 193, 7, 0.15);
  border: 1px solid rgba(255, 193, 7, 0.5);
}

.calendar-day.has-notes {
  background: rgba(33, 150, 243, 0.15);
  border: 1px solid rgba(33, 150, 243, 0.5);
}

.calendar-day.past-date {
  opacity: 0.6;
  background: rgba(100, 100, 100, 0.1);
}

.day-number {
  font-size: 14px;
  font-weight: bold;
  margin-bottom: 2px;
  flex-shrink: 0;
}

.memorable-names {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

/* Стилизация скроллбара */
.memorable-names::-webkit-scrollbar {
  width: 3px;
}

.memorable-names::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
}

.memorable-names::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

.memorable-name {
  font-size: 8px;
  padding: 2px 3px;
  border-radius: 3px;
  color: white;
  white-space: normal;
  word-wrap: break-word;
  overflow-wrap: break-word;
  line-height: 1.2;
  max-width: 100%;
  display: block;
}

.notes-count {
  position: absolute;
  bottom: 2px;
  right: 2px;
  font-size: 10px;
  background: rgba(33, 150, 243, 0.9);
  padding: 1px 3px;
  border-radius: 3px;
  color: white;
  font-weight: bold;
  cursor: pointer;
}

/* Стили для списка заметок в левой панели */
.notes-list-sidebar {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 10px;
}

.note-item-sidebar {
  padding: 12px;
  border-radius: 8px;
  background: rgba(33, 150, 243, 0.1);
  cursor: pointer;
  transition: all 0.3s ease;
  border-left: 3px solid #2196F3;
}

.note-item-sidebar:hover {
  background: rgba(33, 150, 243, 0.2);
  transform: translateX(3px);
}

.note-date-sidebar {
  font-size: 12px;
  font-weight: bold;
  color: #2196F3;
  margin-bottom: 6px;
}

.note-preview-sidebar {
  font-size: 11px;
  opacity: 0.8;
  word-wrap: break-word;
}

.empty-notes-sidebar {
  text-align: center;
  opacity: 0.6;
  padding: 40px 20px;
  font-size: 14px;
}

/* Модальное окно */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: var(--bg);
  border-radius: 12px;
  width: 500px;
  max-width: 90%;
  max-height: 80vh;
  overflow-y: auto;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header h3 {
  margin: 0;
  color: #FF9800;
}

.close-btn {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: var(--text);
  opacity: 0.7;
  transition: opacity 0.3s;
}

.close-btn:hover {
  opacity: 1;
}

.notes-list-container {
  margin-bottom: 20px;
}

.notes-title {
  font-size: 14px;
  font-weight: bold;
  margin-bottom: 10px;
  color: #2196F3;
}

.note-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 10px;
  border-left: 3px solid #2196F3;
}

.note-text {
  margin: 0 0 10px 0;
  word-wrap: break-word;
  font-size: 14px;
}

.note-actions {
  display: flex;
  gap: 10px;
}

.edit-btn, .delete-btn, .save-edit-btn, .cancel-edit-btn {
  padding: 4px 12px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.3s ease;
}

.edit-btn {
  background: #2196F3;
  color: white;
}

.edit-btn:hover {
  background: #1976D2;
  transform: translateY(-1px);
}

.delete-btn {
  background: #f44336;
  color: white;
}

.delete-btn:hover {
  background: #da190b;
  transform: translateY(-1px);
}

.edit-note-section {
  margin-bottom: 20px;
  padding: 15px;
  background: rgba(33, 150, 243, 0.1);
  border-radius: 8px;
}

.edit-actions {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.save-edit-btn {
  background: #4CAF50;
  color: white;
}

.save-edit-btn:hover {
  background: #45a049;
  transform: translateY(-1px);
}

.cancel-edit-btn {
  background: #9E9E9E;
  color: white;
}

.cancel-edit-btn:hover {
  background: #757575;
  transform: translateY(-1px);
}

.new-note-section {
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.note-input {
  width: 100%;
  padding: 8px;
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.05);
  color: var(--text);
  font-family: inherit;
  resize: vertical;
  margin-bottom: 10px;
  font-size: 13px;
}

.note-input:focus {
  outline: none;
  border-color: #FF9800;
}

.save-note-btn {
  width: 100%;
  padding: 8px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  background: #4CAF50;
  color: white;
  transition: all 0.3s ease;
  font-size: 14px;
}

.save-note-btn:hover:not(:disabled) {
  background: #45a049;
  transform: translateY(-1px);
}

.save-note-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading-text {
  text-align: center;
  opacity: 0.6;
  padding: 20px;
  font-size: 12px;
}
</style>