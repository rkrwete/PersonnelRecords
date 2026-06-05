<template>
  <HeaderFirst />
  <div class="page">
    <div class="layout">
      <!-- Левая боковая панель - ЗАМЕТКИ (только десктоп) -->
      <div class="sidebar">
        <div class="title">Мои заметки</div>
        <div class="notes-list-sidebar" v-if="allNotes.length">
          <div v-for="note in sortedNotes" :key="note.id" class="note-item-sidebar" @click="selectNoteDate(note.date)">
            <div class="note-date-sidebar">{{ formatDateLong(note.date) }}</div>
            <div class="note-preview-sidebar">{{ note.content.substring(0, 50) }}...</div>
          </div>
        </div>
        <div v-else class="empty-notes-sidebar">Нет заметок</div>
      </div>

      <!-- Основной контент - календарь -->
      <div class="content">
        <div class="card">
          <!-- Десктоп-хедер -->
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

          <!-- Мобильный хедер -->
          <div class="mobile-header">
            <button class="mobile-arrow-btn" @click="previousMonth">‹</button>
            <div class="mobile-header-center">
              <span class="mobile-cal-title">Памятные даты</span>
              <span class="mobile-month-year">{{ currentMonthName }} {{ currentYear }}</span>
              <button class="mobile-today-btn" @click="goToToday">Сегодня</button>
            </div>
            <button class="mobile-arrow-btn" @click="nextMonth">›</button>
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
                  today: day.isToday,
                  'has-memorable': day.hasMemorableDate,
                  'has-notes': day.notes && day.notes.length > 0,
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
                <div v-for="note in selectedDay.notes" :key="note.id" class="note-card">
                  <div class="note-content">
                    <p class="note-text">{{ note.content }}</p>
                    <div class="note-actions">
                      <button class="edit-btn" @click="startEditNote(note)">✏️ Редактировать</button>
                      <button class="delete-btn" @click="deleteNote(note.id)">🗑️ Удалить</button>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else>
                <div class="notes-title" style="text-align: center">Нет заметок на эту дату</div>
              </div>

              <div v-if="editingNoteId" class="edit-note-section">
                <div class="notes-title">Редактирование заметки:</div>
                <textarea v-model="editNoteContent" class="note-input" rows="3" placeholder="Текст заметки..."></textarea>
                <div class="edit-actions">
                  <button class="save-edit-btn" @click="updateNote" :disabled="saving">Сохранить</button>
                  <button class="cancel-edit-btn" @click="cancelEdit">Отмена</button>
                </div>
              </div>

              <div class="new-note-section">
                <div class="notes-title">Новая заметка:</div>
                <textarea v-model="newNoteContent" class="note-input" rows="3" placeholder="Введите текст заметки..."></textarea>
                <button class="save-note-btn" @click="createNote" :disabled="!newNoteContent.trim() || saving">
                  {{ saving ? "Сохранение..." : "➕ Добавить заметку" }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FAB для заметок (только мобиле) -->
  <button class="notes-fab" @click="isDrawerOpen = true" aria-label="Открыть заметки">
    📝
    <span v-if="allNotes.length" class="fab-badge">{{ allNotes.length }}</span>
  </button>

  <!-- Drawer с заметками (мобиле) -->
  <Teleport to="body">
    <Transition name="backdrop-fade">
      <div v-if="isDrawerOpen" class="drawer-backdrop" @click="isDrawerOpen = false" />
    </Transition>
    <Transition name="drawer-up">
      <div v-if="isDrawerOpen" class="notes-drawer" @touchstart="onDrawerTouchStart" @touchend="onDrawerTouchEnd">
        <div class="drawer-grip" @click="isDrawerOpen = false"></div>
        <div class="drawer-top">
          <span class="drawer-heading">Мои заметки</span>
          <button class="drawer-x-btn" @click="isDrawerOpen = false">×</button>
        </div>
        <div class="drawer-body">
          <div class="notes-list-sidebar" v-if="allNotes.length">
            <div
              v-for="note in sortedNotes"
              :key="note.id"
              class="note-item-sidebar"
              @click="
                selectNoteDate(note.date);
                isDrawerOpen = false;
              "
            >
              <div class="note-date-sidebar">{{ formatDateLong(note.date) }}</div>
              <div class="note-preview-sidebar">{{ note.content.substring(0, 50) }}...</div>
            </div>
          </div>
          <div v-else class="empty-notes-sidebar">Нет заметок</div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Toast notifications -->
  <Teleport to="body">
    <div class="toast-container">
      <transition-group name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast"
          :class="[`toast-${toast.type}`, { 'toast-visible': toast.visible }]"
          @click="dismissToast(toast.id)"
        >
          <span class="toast-icon">{{ toast.type === "success" ? "✓" : "✕" }}</span>
          <span class="toast-message">{{ toast.message }}</span>
        </div>
      </transition-group>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import api from "../services/api.js";
import Header from "./Header.vue";
import HeaderFirst from "./HeaderFirst.vue";

// Состояние
const currentDate = ref(new Date());
const memorableDates = ref([]);
const allNotes = ref([]);
const selectedDay = ref(null);
const newNoteContent = ref("");
const editNoteContent = ref("");
const editingNoteId = ref(null);
const saving = ref(false);

// Drawer
const isDrawerOpen = ref(false);
let drawerTouchStartY = 0;

function onDrawerTouchStart(e) {
  drawerTouchStartY = e.touches[0].clientY;
}

function onDrawerTouchEnd(e) {
  const delta = e.changedTouches[0].clientY - drawerTouchStartY;
  if (delta > 60) {
    isDrawerOpen.value = false;
  }
}

// Блокировка скролла при открытом drawer
watch(isDrawerOpen, (val) => {
  document.body.style.overflow = val ? "hidden" : "";
});

onUnmounted(() => {
  document.body.style.overflow = "";
});

// Toast
const toasts = ref([]);
let toastId = 0;

function showToast(message, type = "success") {
  const id = ++toastId;
  toasts.value.push({ id, message, type, visible: false });
  setTimeout(() => {
    const t = toasts.value.find((t) => t.id === id);
    if (t) t.visible = true;
  }, 10);
  setTimeout(() => dismissToast(id), 3200);
}

function dismissToast(id) {
  const t = toasts.value.find((t) => t.id === id);
  if (t) {
    t.visible = false;
    setTimeout(() => {
      toasts.value = toasts.value.filter((t) => t.id !== id);
    }, 350);
  }
}

// Константы
const weekdays = ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"];

// Вычисляемые свойства
const currentYear = computed(() => currentDate.value.getFullYear());
const currentMonth = computed(() => currentDate.value.getMonth());
const currentMonthName = computed(() => currentDate.value.toLocaleString("ru", { month: "long" }));

const sortedNotes = computed(() => {
  return [...allNotes.value].sort((a, b) => new Date(a.date) - new Date(b.date));
});

// Загрузка памятных дат
async function loadMemorableDates() {
  try {
    const response = await api.get("/api/memorable-dates");
    memorableDates.value = response.data;
    console.log("Загружено памятных дат:", memorableDates.value.length);
  } catch (error) {
    console.error("Ошибка загрузки памятных дат:", error);
  }
}

// Загрузка заметок
async function loadNotes() {
  try {
    const response = await api.get("/api/calendar/notes");
    allNotes.value = (Array.isArray(response.data) ? response.data : []).map((note) => ({
      ...note,
      date: note.date ? note.date.split("T")[0] : note.date,
    }));
    console.log("Загружено заметок:", allNotes.value.length);
  } catch (error) {
    console.error("Ошибка загрузки заметок:", error);
    allNotes.value = [];
  }
}

function getMemorableDatesForMonth(year, month) {
  return memorableDates.value
    .filter((md) => {
      const [mdMonth] = md.date.split("-");
      return parseInt(mdMonth) - 1 === month;
    })
    .map((md) => ({
      ...md,
      date: new Date(year, parseInt(md.date.split("-")[0]) - 1, parseInt(md.date.split("-")[1])),
    }));
}

const calendarDays = computed(() => {
  const year = currentYear.value;
  const month = currentMonth.value;

  const firstDayOfMonth = new Date(year, month, 1);
  let startWeekday = firstDayOfMonth.getDay();
  startWeekday = startWeekday === 0 ? 6 : startWeekday - 1;

  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const monthMemorableDates = getMemorableDatesForMonth(year, month);

  const notesByDate = new Map();
  allNotes.value.forEach((note) => {
    if (note && note.date) {
      if (!notesByDate.has(note.date)) {
        notesByDate.set(note.date, []);
      }
      notesByDate.get(note.date).push(note);
    }
  });

  const days = [];
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const prevMonthLastDay = new Date(year, month, 0).getDate();
  for (let i = startWeekday - 1; i >= 0; i--) {
    const dayDate = new Date(year, month - 1, prevMonthLastDay - i);
    const dateStr = formatDateYMD(dayDate);
    days.push({
      day: prevMonthLastDay - i,
      date: dayDate,
      dateStr,
      isCurrentMonth: false,
      isToday: false,
      hasMemorableDate: false,
      memorableDates: [],
      notes: notesByDate.get(dateStr) || [],
    });
  }

  for (let i = 1; i <= daysInMonth; i++) {
    const dayDate = new Date(year, month, i);
    const isToday = dayDate.toDateString() === today.toDateString();
    const dateStr = formatDateYMD(dayDate);
    const dayMemorableDates = monthMemorableDates.filter((md) => md.date.getDate() === i);
    const dayNotes = notesByDate.get(dateStr) || [];

    days.push({
      day: i,
      date: dayDate,
      dateStr,
      isCurrentMonth: true,
      isToday,
      hasMemorableDate: dayMemorableDates.length > 0,
      memorableDates: dayMemorableDates,
      notes: dayNotes,
    });
  }

  let remaining = 42 - days.length;
  for (let i = 1; i <= remaining; i++) {
    const dayDate = new Date(year, month + 1, i);
    const dateStr = formatDateYMD(dayDate);
    days.push({
      day: i,
      date: dayDate,
      dateStr,
      isCurrentMonth: false,
      isToday: false,
      hasMemorableDate: false,
      memorableDates: [],
      notes: notesByDate.get(dateStr) || [],
    });
  }

  return days;
});

async function createNote() {
  if (!selectedDay.value || !newNoteContent.value.trim()) return;

  saving.value = true;
  try {
    await api.post("/api/calendar/notes", {
      date: selectedDay.value.dateStr,
      content: newNoteContent.value.trim(),
    });
    await loadNotes();
    newNoteContent.value = "";

    const updatedDay = calendarDays.value.find((d) => d.dateStr === selectedDay.value.dateStr);
    if (updatedDay) selectedDay.value = updatedDay;
    showToast("Заметка добавлена");
  } catch (error) {
    console.error("Ошибка создания заметки:", error);
    showToast("Не удалось создать заметку", "error");
  } finally {
    saving.value = false;
  }
}

function startEditNote(note) {
  editingNoteId.value = note.id;
  editNoteContent.value = note.content;
}

async function updateNote() {
  if (!editNoteContent.value.trim()) return;

  saving.value = true;
  try {
    await api.put(`/api/calendar/notes/${editingNoteId.value}`, {
      content: editNoteContent.value.trim(),
    });
    await loadNotes();
    cancelEdit();
    const updatedDay = calendarDays.value.find((d) => d.dateStr === selectedDay.value.dateStr);
    if (updatedDay) selectedDay.value = updatedDay;
    showToast("Заметка обновлена");
  } catch (error) {
    console.error("Ошибка обновления заметки:", error);
    showToast("Не удалось обновить заметку", "error");
  } finally {
    saving.value = false;
  }
}

async function deleteNote(noteId) {
  if (!confirm("Удалить заметку?")) return;

  try {
    await api.delete(`/api/calendar/notes/${noteId}`);
    await loadNotes();
    const updatedDay = calendarDays.value.find((d) => d.dateStr === selectedDay.value.dateStr);
    if (updatedDay) selectedDay.value = updatedDay;
    showToast("Заметка удалена");
  } catch (error) {
    console.error("Ошибка удаления заметки:", error);
    showToast("Не удалось удалить заметку", "error");
  }
}

function cancelEdit() {
  editingNoteId.value = null;
  editNoteContent.value = "";
}

function selectDay(day) {
  selectedDay.value = day;
  newNoteContent.value = "";
  cancelEdit();
}

function selectNoteDate(dateStr) {
  const day = calendarDays.value.find((d) => d.dateStr === dateStr);
  if (day) {
    selectDay(day);
    const date = new Date(dateStr);
    currentDate.value = new Date(date.getFullYear(), date.getMonth(), 1);
  }
}

function closeModal() {
  selectedDay.value = null;
  newNoteContent.value = "";
  cancelEdit();
}

function previousMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
}

function nextMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
}

function goToToday() {
  currentDate.value = new Date();
}

function formatDateYMD(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, "0");
  const d = String(date.getDate()).padStart(2, "0");
  return `${y}-${m}-${d}`;
}

function formatDateShort(date) {
  if (!date) return "";
  const day = date.getDate();
  const month = date.toLocaleString("ru", { month: "short" });
  return `${day} ${month}`;
}

function formatDateLong(dateStr) {
  if (!dateStr) return "";
  const date = new Date(dateStr);
  return date.toLocaleString("ru", {
    year: "numeric",
    month: "long",
    day: "numeric",
    weekday: "long",
  });
}

watch(
  allNotes,
  () => {
    if (selectedDay.value) {
      const updatedDay = calendarDays.value.find((d) => d.dateStr === selectedDay.value.dateStr);
      if (updatedDay) selectedDay.value = updatedDay;
    }
  },
  { deep: true },
);

onMounted(async () => {
  await loadMemorableDates();
  await loadNotes();
});
</script>

<style scoped>
/* ─── Базовый макет ────────────────────────────────────────── */

.page {
  display: flex;
  width: 100%;
  padding: 15px;
  height: calc(100vh - 100px);
  overflow: hidden;
}

.layout {
  display: flex;
  gap: 20px;
  width: 100%;
  height: 100%;
}

/* ─── Сайдбар ──────────────────────────────────────────────── */

.sidebar {
  width: 320px;
  padding: 16px 10px;
  border-radius: 10px;
  box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
  overflow-y: auto;
  scrollbar-width: thin;
}

.content {
  flex: 1;
  height: 100%;
  min-width: 0;
}

/* ─── Карточка ─────────────────────────────────────────────── */

.card {
  width: 100%;
  height: 100%;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* ─── Десктоп-хедер ────────────────────────────────────────── */

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
  background: #4caf50;
}

.today-btn:hover {
  background: #45a049;
}

/* ─── Мобильный хедер ─────────────────────────────────────── */

.mobile-header {
  display: none;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 12px;
  flex-shrink: 0;
}

.mobile-arrow-btn {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  background: var(--btn);
  color: var(--text);
  font-size: 26px;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-family: "Tektur", sans-serif;
  transition: all 0.2s ease;
  -webkit-tap-highlight-color: transparent;
}

.mobile-arrow-btn:active {
  background: var(--btn-hover);
  transform: scale(0.93);
}

.mobile-header-center {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  flex: 1;
  min-width: 0;
}

.mobile-cal-title {
  font-size: 11px;
  opacity: 0.55;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-family: "Tektur", sans-serif;
}

.mobile-month-year {
  font-size: 16px;
  font-weight: 700;
  font-family: "Tektur", sans-serif;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.mobile-today-btn {
  padding: 3px 14px;
  border-radius: 20px;
  border: none;
  cursor: pointer;
  background: #4caf50;
  color: white;
  font-size: 11px;
  font-family: "Tektur", sans-serif;
  transition: background 0.2s;
  -webkit-tap-highlight-color: transparent;
}

.mobile-today-btn:active {
  background: #388e3c;
}

/* ─── Заголовки ────────────────────────────────────────────── */

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

/* ─── Календарная сетка ────────────────────────────────────── */

.calendar {
  display: flex;
  flex-direction: column;
  width: 100%;
  flex: 1;
  min-height: 0;
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
  grid-auto-rows: 1fr;
  gap: 4px;
  flex: 1;
  min-height: 0;
}

.calendar-day {
  padding: 4px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.03);
  cursor: pointer;
  position: relative;
  display: flex;
  flex-direction: column;
  transition: all 0.3s ease;
  overflow: hidden;
  -webkit-tap-highlight-color: transparent;
}

.calendar-day:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-1px);
}

.calendar-day.other-month {
  opacity: 0.3;
}

.calendar-day.today {
  border: 2px solid #4caf50;
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
  scrollbar-width: thin;
  min-height: 0;
}

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

/* ─── Сайдбар-заметки ──────────────────────────────────────── */

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
  border-left: 3px solid #2196f3;
}

.note-item-sidebar:hover {
  background: rgba(33, 150, 243, 0.2);
  transform: translateX(3px);
}

.note-date-sidebar {
  font-size: 12px;
  font-weight: bold;
  color: #2196f3;
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

/* ─── Модальное окно ───────────────────────────────────────── */

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
  color: #ff9800;
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
  color: #2196f3;
}

.note-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 10px;
  border-left: 3px solid #2196f3;
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

.edit-btn,
.delete-btn,
.save-edit-btn,
.cancel-edit-btn {
  padding: 4px 12px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.3s ease;
}

.edit-btn {
  background: #2196f3;
  color: white;
}

.edit-btn:hover {
  background: #1976d2;
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
  background: #4caf50;
  color: white;
}

.save-edit-btn:hover {
  background: #45a049;
  transform: translateY(-1px);
}

.cancel-edit-btn {
  background: #9e9e9e;
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
  box-sizing: border-box;
}

.note-input:focus {
  outline: none;
  border-color: #ff9800;
}

.save-note-btn {
  width: 100%;
  padding: 8px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  background: #4caf50;
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

/* ─── FAB (мобиле) ─────────────────────────────────────────── */

.notes-fab {
  display: none;
  position: fixed;
  bottom: 24px;
  right: 20px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  background: #2196f3;
  color: white;
  font-size: 22px;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(33, 150, 243, 0.45);
  z-index: 500;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
  -webkit-tap-highlight-color: transparent;
}

.notes-fab:active {
  transform: scale(0.92);
  box-shadow: 0 2px 8px rgba(33, 150, 243, 0.35);
}

.fab-badge {
  position: absolute;
  top: -3px;
  right: -3px;
  background: #ff5722;
  color: white;
  font-size: 10px;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  padding: 0 3px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: "Tektur", sans-serif;
}

/* ─── Drawer (мобиле) ──────────────────────────────────────── */

.drawer-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  z-index: 800;
}

.notes-drawer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--bg, #1a1a2e);
  border-radius: 20px 20px 0 0;
  padding: 0 16px env(safe-area-inset-bottom, 16px);
  padding-bottom: max(env(safe-area-inset-bottom, 0px), 16px);
  z-index: 900;
  max-height: 75dvh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 -8px 40px rgba(0, 0, 0, 0.5);
  will-change: transform;
}

.drawer-grip {
  width: 44px;
  height: 4px;
  background: rgba(255, 255, 255, 0.25);
  border-radius: 2px;
  margin: 14px auto 12px;
  flex-shrink: 0;
  cursor: pointer;
  transition: background 0.2s;
}

.drawer-grip:hover {
  background: rgba(255, 255, 255, 0.45);
}

.drawer-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 12px;
  margin-bottom: 4px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  flex-shrink: 0;
}

.drawer-heading {
  font-size: 17px;
  font-weight: 700;
  font-family: "Tektur", sans-serif;
}

.drawer-x-btn {
  background: none;
  border: none;
  font-size: 30px;
  line-height: 1;
  cursor: pointer;
  color: var(--text);
  opacity: 0.6;
  padding: 0 4px;
  transition: opacity 0.2s;
  -webkit-tap-highlight-color: transparent;
}

.drawer-x-btn:active {
  opacity: 1;
}

.drawer-body {
  overflow-y: auto;
  flex: 1;
  scrollbar-width: thin;
  padding-bottom: 8px;
}

/* ─── Анимации Drawer ──────────────────────────────────────── */

.backdrop-fade-enter-active,
.backdrop-fade-leave-active {
  transition: opacity 0.28s ease;
}

.backdrop-fade-enter-from,
.backdrop-fade-leave-to {
  opacity: 0;
}

.drawer-up-enter-active,
.drawer-up-leave-active {
  transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1);
}

.drawer-up-enter-from,
.drawer-up-leave-to {
  transform: translateY(100%);
}

/* ─── Toast ────────────────────────────────────────────────── */

.toast-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}

.toast {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 18px;
  border-radius: 10px;
  font-family: "Tektur", sans-serif;
  font-size: 14px;
  font-weight: 500;
  min-width: 220px;
  max-width: 340px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
  cursor: pointer;
  pointer-events: all;
  opacity: 0;
  transform: translateX(30px);
  transition:
    opacity 0.3s ease,
    transform 0.3s ease;
}

.toast.toast-visible {
  opacity: 1;
  transform: translateX(0);
}

.toast-success {
  background: #003f3d;
  border: 1px solid #009e97;
  color: #fff;
}

.toast-error {
  background: #3d1414;
  border: 1px solid #ff5c5c;
  color: #fff;
}

.toast-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.toast-success .toast-icon {
  background: #009e97;
  color: #003735;
}

.toast-error .toast-icon {
  background: #ff5c5c;
  color: #fff;
}

.toast-message {
  flex: 1;
}

.toast-enter-active,
.toast-leave-active {
  transition:
    opacity 0.3s ease,
    transform 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* ─── Мобиле ≤ 768px ───────────────────────────────────────── */

@media (max-width: 768px) {
  .page {
    padding: 8px 6px;
    /* height: calc(100dvh - 60px); */
  }

  /* Скрыть сайдбар — заметки доступны через drawer */
  .sidebar {
    display: none !important;
  }

  /* Layout: без flex-direction override от 1000px медиа — только content */
  .layout {
    flex-direction: column;
    gap: 0;
  }

  .content {
    height: 100%;
  }

  .card {
    padding: 10px 8px;
  }

  /* Переключение хедеров */
  .header-block {
    display: none;
  }

  .mobile-header {
    display: flex;
  }

  /* FAB видим */
  .notes-fab {
    display: flex;
  }

  /* Ячейки календаря компактнее */
  .weekday {
    padding: 5px 0;
    font-size: 11px;
  }

  .calendar-day {
    padding: 3px 2px;
    border-radius: 5px;
  }

  .day-number {
    font-size: 12px;
    margin-bottom: 1px;
  }

  .memorable-name {
    font-size: 7px;
    padding: 1px 2px;
  }

  .notes-count {
    font-size: 9px;
    padding: 1px 2px;
  }

  /* Модал как bottom-sheet */
  .modal-overlay {
    align-items: flex-end;
    padding: 0;
  }

  .modal-content {
    width: 100%;
    max-width: 100%;
    max-height: 92dvh;
    border-radius: 20px 20px 0 0;
    padding: 16px;
    padding-bottom: max(env(safe-area-inset-bottom, 0px), 16px);
  }

  .modal-header h3 {
    font-size: 14px;
  }

  /* Toast на мобиле — по центру снизу */
  .toast-container {
    bottom: 16px;
    right: 8px;
    left: 8px;
    align-items: center;
  }

  .toast {
    min-width: unset;
    width: 100%;
    max-width: 420px;
    font-size: 13px;
    transform: translateY(20px);
  }

  .toast.toast-visible {
    transform: translateY(0);
  }

  .toast-enter-from,
  .toast-leave-to {
    transform: translateY(20px);
  }
}

/* ─── Очень маленькие экраны ≤ 390px ──────────────────────── */

@media (max-width: 390px) {
  .day-number {
    font-size: 11px;
  }

  .weekday {
    font-size: 10px;
    padding: 4px 0;
  }

  .memorable-name {
    font-size: 6px;
  }

  .mobile-month-year {
    font-size: 14px;
  }

  .mobile-arrow-btn {
    width: 38px;
    height: 38px;
    font-size: 22px;
  }
}
</style>
