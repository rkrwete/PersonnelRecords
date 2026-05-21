<template>
  <div class="page">
    <div class="layout">
      <!-- ЛЕВАЯ ПАНЕЛЬ: 7 памятных дат -->
      <div class="sidebar">
        <div class="title">Памятные даты</div>
        <div class="memorable-dates-list">
          <div 
            v-for="date in upcomingMemorableDates" 
            :key="date.id"
            class="memorable-date-item"
            :class="{ 'past': date.isPast, 'upcoming': !date.isPast }"
            :title="date.name"
          >
            <div class="date-number">{{ formatDateShort(date.date) }}</div>
            <div class="date-name">{{ date.name }}</div>
          </div>
        </div>

        <!-- БЛОК ЗАМЕТОК (появляется по кнопке) -->
        <div class="notes-sidebar" v-if="showNotes">
          <div class="title">Мои заметки</div>
          <div class="notes-list" v-if="userNotes.length">
            <div 
              v-for="note in userNotes.slice(0, 5)" 
              :key="note.id"
              class="note-item"
              @click="selectNoteDate(note.date)"
            >
              <div class="note-date">{{ formatDateShort(new Date(note.date)) }}</div>
              <div class="note-preview">{{ note.content.substring(0, 30) }}...</div>
            </div>
          </div>
          <div v-else class="empty-notes">Нет заметок</div>
        </div>
      </div>

      <!-- ОСНОВНОЙ КОНТЕНТ (календарь) -->
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
                v-for="(day, idx) in calendarDays" 
                :key="idx"
                class="calendar-day"
                :class="{
                  'other-month': !day.isCurrentMonth,
                  'today': day.isToday,
                  'has-memorable': day.hasMemorableDate,
                  'has-note': day.hasNote
                }"
                @click="selectDay(day)"
              >
                <div class="day-number">{{ day.day }}</div>
                <div class="memorable-indicators">
                  <div 
                    v-for="md in day.memorableDates" 
                    :key="md.id"
                    class="memorable-badge"
                    :style="{ backgroundColor: md.color }"
                    :title="md.name"
                  >
                    {{ md.name.substring(0, 2) }}
                  </div>
                </div>
                <div v-if="day.hasNote" class="note-indicator">📝</div>
              </div>
            </div>
          </div>

          <!-- БЛОК ВЫБРАННОГО ДНЯ (заметки) -->
          <div v-if="selectedDate" class="selected-date-info">
            <div class="selected-date-header">
              <div class="selected-date-title">{{ formatDateFull(selectedDate.date) }}</div>
              <button class="toggle-notes-btn" @click="showNotes = !showNotes">
                {{ showNotes ? 'Скрыть заметки' : 'Показать заметки' }}
              </button>
            </div>

            <div v-if="selectedDate.memorableDates.length" class="memorable-details">
              <div 
                v-for="md in selectedDate.memorableDates" 
                :key="md.id"
                class="memorable-detail-item"
                :style="{ borderLeftColor: md.color }"
              >
                <div class="detail-name">{{ md.name }}</div>
                <div class="detail-description" v-if="md.description">{{ md.description }}</div>
              </div>
            </div>

            <div class="notes-section">
              <div class="notes-title">Заметка на {{ formatDateShort(selectedDate.date) }}</div>
              <textarea 
                v-model="currentNote" 
                class="note-input"
                placeholder="Добавить заметку..."
                rows="3"
              ></textarea>
              <div class="note-actions">
                <button class="save-note-btn" @click="saveNote" :disabled="saving">
                  {{ saving ? 'Сохранение...' : 'Сохранить заметку' }}
                </button>
                <button class="delete-note-btn" @click="deleteNote" v-if="currentNoteId">
                  Удалить
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
import { ref, computed, onMounted } from 'vue'
import api from '../services/api.js'

// Состояния
const currentDate = ref(new Date())
const selectedDate = ref(null)
const showNotes = ref(false)
const currentNote = ref('')
const currentNoteId = ref(null)
const saving = ref(false)
const userNotes = ref([])
const memorableDates = ref([])

// Константы
const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

// Вычисляемые свойства
const currentYear = computed(() => currentDate.value.getFullYear())
const currentMonth = computed(() => currentDate.value.getMonth())
const currentMonthName = computed(() => currentDate.value.toLocaleString('ru', { month: 'long' }))

// ========== 1. Памятные даты (7 штук: 3 прошедшие + 4 будущие) ==========
async function loadMemorableDates() {
  try {
    const res = await api.get('/memorable-dates')
    memorableDates.value = res.data
  } catch (e) {
    console.error('Ошибка загрузки памятных дат:', e)
  }
}

function getMemorableDatesForMonth(year, month) {
  return memorableDates.value.filter(md => {
    const [mdMonth] = md.date.split('-')
    return parseInt(mdMonth) - 1 === month
  }).map(md => ({
    ...md,
    date: new Date(year, parseInt(md.date.split('-')[0]) - 1, parseInt(md.date.split('-')[1]))
  }))
}

// 7 дат: 3 ПРОШЕДШИХ + 4 БУДУЩИХ (от текущей даты)
const upcomingMemorableDates = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  const allDates = memorableDates.value.map(md => {
    const [month, day] = md.date.split('-')
    let targetDate = new Date(today.getFullYear(), parseInt(month) - 1, parseInt(day))
    if (targetDate < today) {
      targetDate = new Date(today.getFullYear() + 1, parseInt(month) - 1, parseInt(day))
    }
    return { ...md, date: targetDate, isPast: targetDate < today }
  })

  allDates.sort((a, b) => a.date - b.date)
  const pastDates = allDates.filter(d => d.isPast)
  const futureDates = allDates.filter(d => !d.isPast)

  // Берём 3 прошлые и 4 будущие (всего 7)
  const result = [
    ...pastDates.slice(-3),
    ...futureDates.slice(0, 4)
  ]
  return result.sort((a, b) => a.date - b.date)
})

// ========== 2. Календарная сетка ==========
const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value

  const firstDay = new Date(year, month, 1)
  let startWeekday = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1

  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const monthMemorable = getMemorableDatesForMonth(year, month)
  const notesMap = new Map(userNotes.value.map(n => [n.date, true]))

  const today = new Date()
  today.setHours(0, 0, 0, 0)

  const days = []

  // Дни предыдущего месяца
  const prevMonthLastDay = new Date(year, month, 0).getDate()
  for (let i = startWeekday - 1; i >= 0; i--) {
    const d = new Date(year, month - 1, prevMonthLastDay - i)
    days.push({
      day: prevMonthLastDay - i,
      date: d,
      isCurrentMonth: false,
      isToday: false,
      hasMemorableDate: false,
      hasNote: false,
      memorableDates: []
    })
  }

  // Дни текущего месяца
  for (let i = 1; i <= daysInMonth; i++) {
    const d = new Date(year, month, i)
    const isToday = d.toDateString() === today.toDateString()
    const dayMemorable = monthMemorable.filter(m => m.date.getDate() === i)

    days.push({
      day: i,
      date: d,
      isCurrentMonth: true,
      isToday,
      hasMemorableDate: dayMemorable.length > 0,
      hasNote: notesMap.has(formatDateYMD(d)),
      memorableDates: dayMemorable
    })
  }

  // Дни следующего месяца до 42 ячеек
  let remaining = 42 - days.length
  for (let i = 1; i <= remaining; i++) {
    const d = new Date(year, month + 1, i)
    days.push({
      day: i,
      date: d,
      isCurrentMonth: false,
      isToday: false,
      hasMemorableDate: false,
      hasNote: false,
      memorableDates: []
    })
  }

  return days
})

// ========== 3. Заметки (CRUD) ==========
async function loadUserNotes() {
  try {
    const res = await api.get('/calendar/notes')
    userNotes.value = res.data
  } catch (e) {
    console.error('Ошибка загрузки заметок:', e)
  }
}

async function loadNoteForDate(date) {
  if (!date) return
  const dateStr = formatDateYMD(date)
  try {
    const res = await api.get(`/calendar/notes/${dateStr}`)
    if (res.data && res.data.content) {
      currentNote.value = res.data.content
      currentNoteId.value = res.data.id
    } else {
      currentNote.value = ''
      currentNoteId.value = null
    }
  } catch (e) {
    currentNote.value = ''
    currentNoteId.value = null
  }
}

async function saveNote() {
  if (!selectedDate.value) return
  const content = currentNote.value.trim()
  if (!content) {
    alert('Заметка не может быть пустой')
    return
  }
  saving.value = true
  const dateStr = formatDateYMD(selectedDate.value.date)
  try {
    await api.post('/calendar/notes', { date: dateStr, content })
    await loadUserNotes()
    if (selectedDate.value) selectedDate.value.hasNote = true
    alert('Заметка сохранена')
  } catch (e) {
    alert('Ошибка сохранения')
  } finally {
    saving.value = false
  }
}

async function deleteNote() {
  if (!currentNoteId.value) return
  if (!confirm('Удалить заметку?')) return
  try {
    await api.delete(`/calendar/notes/${currentNoteId.value}`)
    currentNote.value = ''
    currentNoteId.value = null
    await loadUserNotes()
    if (selectedDate.value) selectedDate.value.hasNote = false
    alert('Заметка удалена')
  } catch (e) {
    alert('Ошибка удаления')
  }
}

function selectNoteDate(dateStr) {
  const date = new Date(dateStr)
  currentDate.value = new Date(date.getFullYear(), date.getMonth(), 1)
  setTimeout(() => {
    const dayToSelect = calendarDays.value.find(d => formatDateYMD(d.date) === dateStr)
    if (dayToSelect) selectDay(dayToSelect)
  }, 100)
}

// ========== 4. Выбор дня ==========
function selectDay(day) {
  if (!day) return
  selectedDate.value = {
    date: day.date,
    memorableDates: day.memorableDates || [],
    hasNote: day.hasNote || false
  }
  loadNoteForDate(day.date)
}

// ========== 5. Навигация ==========
function previousMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1)
}
function nextMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1)
}
function goToToday() {
  currentDate.value = new Date()
}

// ========== 6. Форматирование дат ==========
function formatDateYMD(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}
function formatDateShort(date) {
  return `${date.getDate()} ${date.toLocaleString('ru', { month: 'short' })}`
}
function formatDateFull(date) {
  return date.toLocaleString('ru', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long'
  })
}

// ========== Инициализация ==========
onMounted(async () => {
  await loadMemorableDates()
  await loadUserNotes()
  const today = new Date()
  selectDay({
    date: today,
    memorableDates: getMemorableDatesForMonth(today.getFullYear(), today.getMonth()).filter(md => md.date.getDate() === today.getDate()),
    hasNote: false
  })
})
</script>

<style scoped>
/* Стили – возьмите из вашего предыдущего рабочего варианта (они уже нормальные) */
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
  width: 280px;
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
.calendar {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
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
  min-height: 70px;
  padding: 6px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.03);
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  display: flex;
  flex-direction: column;
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
.calendar-day.has-note {
  background: rgba(33, 150, 243, 0.1);
  border: 1px solid rgba(33, 150, 243, 0.5);
}
.calendar-day.has-memorable.has-note {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(33, 150, 243, 0.1));
}
.day-number {
  font-size: 14px;
  font-weight: bold;
  margin-bottom: 4px;
}
.memorable-indicators {
  display: flex;
  flex-wrap: wrap;
  gap: 2px;
  margin-top: 2px;
}
.memorable-badge {
  font-size: 8px;
  padding: 2px 3px;
  background: #FF9800;
  color: white;
  border-radius: 3px;
  cursor: pointer;
  transition: all 0.3s ease;
}
.memorable-badge:hover {
  transform: scale(1.05);
}
.note-indicator {
  position: absolute;
  bottom: 4px;
  right: 4px;
  font-size: 12px;
}
.memorable-dates-list {
  margin-top: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 300px;
  overflow-y: auto;
}
.memorable-date-item {
  padding: 8px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.05);
  transition: all 0.3s ease;
  cursor: pointer;
}
.memorable-date-item:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateX(3px);
}
.memorable-date-item.past {
  border-left: 3px solid #9E9E9E;
}
.memorable-date-item.upcoming {
  border-left: 3px solid #4CAF50;
}
.date-number {
  font-size: 12px;
  font-weight: bold;
  color: #FF9800;
  margin-bottom: 3px;
}
.date-name {
  font-size: 11px;
}
.notes-sidebar {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}
.notes-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 300px;
  overflow-y: auto;
}
.note-item {
  padding: 8px;
  border-radius: 6px;
  background: rgba(33, 150, 243, 0.1);
  cursor: pointer;
  transition: all 0.3s ease;
}
.note-item:hover {
  background: rgba(33, 150, 243, 0.2);
  transform: translateX(3px);
}
.note-date {
  font-size: 11px;
  font-weight: bold;
  color: #2196F3;
  margin-bottom: 3px;
}
.note-preview {
  font-size: 10px;
  opacity: 0.8;
}
.selected-date-info {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}
.selected-date-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}
.selected-date-title {
  font-size: 18px;
  font-weight: bold;
  color: #FF9800;
}
.toggle-notes-btn {
  padding: 4px 10px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  background: #2196F3;
  color: white;
  font-size: 12px;
  transition: all 0.3s ease;
}
.toggle-notes-btn:hover {
  background: #1976D2;
  transform: translateY(-1px);
}
.memorable-details {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
}
.memorable-detail-item {
  padding: 10px;
  background: rgba(255, 152, 0, 0.1);
  border-radius: 6px;
  border-left: 3px solid #FF9800;
}
.detail-name {
  font-size: 14px;
  font-weight: bold;
  margin-bottom: 5px;
}
.detail-description {
  font-size: 12px;
  opacity: 0.8;
}
.notes-section {
  margin-top: 20px;
}
.notes-title {
  font-size: 14px;
  font-weight: bold;
  margin-bottom: 10px;
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
.note-actions {
  display: flex;
  gap: 10px;
}
.save-note-btn, .delete-note-btn {
  padding: 6px 12px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 12px;
}
.save-note-btn {
  background: #4CAF50;
  color: white;
}
.save-note-btn:hover:not(:disabled) {
  background: #45a049;
  transform: translateY(-1px);
}
.save-note-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.delete-note-btn {
  background: #f44336;
  color: white;
}
.delete-note-btn:hover {
  background: #da190b;
  transform: translateY(-1px);
}
.loading-text, .empty-notes {
  text-align: center;
  opacity: 0.6;
  padding: 20px;
  font-size: 12px;
}
</style>