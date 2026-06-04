<template>
  <CalendarHeader />
  <div class="page">
    <div class="layout">
      <!-- Левая боковая панель - КАРТОЧКИ ДНЕЙ С ЗАМЕТКАМИ -->
      <div class="sidebar">
        <div class="sidebar-header">
          <div class="title">Заметки по дням</div>
          <button class="reset-scroll-btn" @click="resetToTodayView" title="Вернуться к текущей дате (2 прошлых + текущий)">
            Сегодня
          </button>
        </div>
        
        <div class="day-cards-list" ref="dayCardsListRef">
          <div 
            v-for="dayCard in displayedDayCards" 
            :key="dayCard.dateStr" 
            class="day-card"
            :class="{ 
              'current-day': dayCard.isCurrentDay,
              'past-day': dayCard.isPast && !dayCard.isCurrentDay,
              'future-day': !dayCard.isPast && !dayCard.isCurrentDay,
              'has-notes': dayCard.notes.length > 0,
              'empty-day': dayCard.notes.length === 0 && !dayCard.isCurrentDay
            }"
            @click="selectDayFromCard(dayCard.dateStr)"
          >
            <div class="card-header">
              <div class="card-date">{{ formatDateLong(dayCard.dateStr) }}</div>
              <div class="card-badge" v-if="dayCard.isCurrentDay">Текущий день</div>
              <div class="card-badge past-badge" v-else-if="dayCard.isPast">Прошедший</div>
              <div class="card-badge future-badge" v-else>Будущий</div>
            </div>
            
            <div class="card-notes" v-if="dayCard.notes.length > 0">
              <div 
                v-for="note in dayCard.notes" 
                :key="note.id" 
                class="card-note-item"
                :style="{ borderLeftColor: note.color }"
              >
                <div class="note-title" v-if="note.title">
                  <strong :style="{ color: note.color }">{{ note.title }}</strong>
                </div>
                <div class="note-preview">{{ note.content.substring(0, 60) }}...</div>
                <div class="note-meta-small">
                  <span v-if="note.is_recurring" class="recurring-icon">🔄 ежегодно</span>
                  <span v-if="note.reminder_time && note.reminder_type !== 'none'" class="reminder-icon">⏰ {{ note.reminder_time }}</span>
                  <span class="note-color-dot" :style="{ backgroundColor: note.color }"></span>
                </div>
              </div>
            </div>
            
            <div class="empty-day-message" v-else>
              <span>📭 Нет заметок</span>
              <span class="add-hint">Нажмите, чтобы добавить</span>
            </div>
          </div>
          
          <div v-if="dayCards.length === 0" class="empty-notes-sidebar">Нет заметок</div>
        </div>
        
        <div class="sidebar-footer">
          <button class="scroll-up-btn" @click="scrollUp" :disabled="scrollIndex <= 0">
            ↑ Ранее
          </button>
          <span class="scroll-indicator">
            {{ scrollIndex + 1 }} - {{ Math.min(scrollIndex + visibleCardsCount, dayCards.length) }} из {{ dayCards.length }} дней
          </span>
          <button class="scroll-down-btn" @click="scrollDown" :disabled="scrollIndex + visibleCardsCount >= dayCards.length">
            ↓ Позже
          </button>
        </div>
      </div>

      <!-- Основной контент - календарь -->
      <div class="content">
        <div class="card">
          <div class="header-block">
            <div class="header-text">
              <div class="title">Календарь заметок и событий</div>
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
                  today: day.isToday,
                  'has-notes': day.notes && day.notes.length > 0,
                }"
                @click="selectDayFromCalendar(day)"
              >
                <div class="day-number">{{ day.day }}</div>
                
                <!-- Компактное отображение заметок (максимум 3, остальные сворачиваются) -->
                <div class="calendar-note-previews" v-if="day.notes && day.notes.length > 0">
                  <div 
                    v-for="note in day.notes.slice(0, 3)" 
                    :key="note.id"
                    class="calendar-note-preview"
                    :style="{ backgroundColor: note.color + '20', borderLeftColor: note.color }"
                    :title="(note.title || 'Заметка') + ': ' + note.content + (note.reminder_time ? ' ⏰ ' + note.reminder_time : '')"
                  >
                    <span class="note-dot" :style="{ backgroundColor: note.color }"></span>
                    <span class="note-short-text">{{ getNotePreview(note) }}</span>
                    <span v-if="note.reminder_time && note.reminder_type !== 'none'" class="note-time-icon">⏰</span>
                  </div>
                  <div v-if="day.notes.length > 3" class="calendar-note-more" :title="'Ещё ' + (day.notes.length - 3) + ' заметки'">
                    +{{ day.notes.length - 3 }} ещё
                  </div>
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

              <!-- Список существующих заметок -->
              <div class="notes-list-container" v-if="selectedDay.notes && selectedDay.notes.length">
                <div class="notes-title">Заметки на этот день ({{ selectedDay.notes.length }}):</div>
                <div v-for="note in selectedDay.notes" :key="note.id" class="note-card" :style="{ borderLeftColor: note.color }">
                  <div class="note-content">
                    <div class="note-title-large" v-if="note.title" :style="{ color: note.color }">
                      <strong>{{ note.title }}</strong>
                    </div>
                    <p class="note-text">{{ note.content }}</p>
                    <div class="note-meta">
                      <span v-if="note.is_recurring" class="recurring-badge">🔄 Повторяется ежегодно</span>
                      <span v-if="note.reminder_time && note.reminder_type !== 'none'" class="reminder-badge">
                        ⏰ {{ note.reminder_time }} 
                        ({{ getReminderTypeText(note.reminder_type) }})
                      </span>
                      <div class="note-actions">
                        <button class="edit-btn" @click="startEditNote(note)">✏️ Редактировать</button>
                        <button class="delete-btn" @click="deleteNote(note.id)">🗑️ Удалить</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Форма создания новой заметки -->
              <div class="new-note-section">
                <div class="notes-title">Новая заметка:</div>
                <input v-model="newNoteTitle" class="note-input" type="text" placeholder="Заголовок (необязательно)" />
                <textarea v-model="newNoteContent" class="note-input" rows="3" placeholder="Текст заметки..."></textarea>
                
                <div class="reminder-settings">
                  <div class="form-group">
                    <label class="form-label">⏰ Время напоминания:</label>
                    <input 
                      type="time" 
                      v-model="newNoteReminderTime" 
                      class="time-input"
                      :disabled="newNoteReminderType === 'none'"
                    />
                  </div>
                  
                  <div class="form-group">
                    <label class="form-label">🔄 Повтор напоминания:</label>
                    <select v-model="newNoteReminderType" class="select-input">
                      <option value="none">Не напоминать</option>
                      <option value="once">Один раз</option>
                      <option value="daily">Ежедневно</option>
                      <option value="weekly">Еженедельно</option>
                      <option value="monthly">Ежемесячно</option>
                    </select>
                  </div>
                </div>
                
                <div class="note-options">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="newNoteIsRecurring" /> Повторяющаяся (ежегодно)
                  </label>
                  <div class="color-picker">
                    <span>Цвет:</span>
                    <input type="color" v-model="newNoteColor" />
                  </div>
                </div>
                
                <button class="save-note-btn" @click="createNote" :disabled="!newNoteContent.trim() || saving">
                  {{ saving ? "Сохранение..." : "➕ Добавить заметку" }}
                </button>
              </div>

              <!-- Форма редактирования -->
              <div v-if="editingNoteId" class="edit-note-section">
                <div class="notes-title">Редактирование заметки:</div>
                <input v-model="editNoteTitle" class="note-input" type="text" placeholder="Заголовок" />
                <textarea v-model="editNoteContent" class="note-input" rows="3" placeholder="Текст заметки..."></textarea>
                
                <div class="reminder-settings">
                  <div class="form-group">
                    <label class="form-label">⏰ Время напоминания:</label>
                    <input 
                      type="time" 
                      v-model="editNoteReminderTime" 
                      class="time-input"
                      :disabled="editNoteReminderType === 'none'"
                    />
                  </div>
                  
                  <div class="form-group">
                    <label class="form-label">🔄 Повтор напоминания:</label>
                    <select v-model="editNoteReminderType" class="select-input">
                      <option value="none">Не напоминать</option>
                      <option value="once">Один раз</option>
                      <option value="daily">Ежедневно</option>
                      <option value="weekly">Еженедельно</option>
                      <option value="monthly">Ежемесячно</option>
                    </select>
                  </div>
                </div>
                
                <div class="note-options">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="editNoteIsRecurring" /> Повторяющаяся (ежегодно)
                  </label>
                  <div class="color-picker">
                    <span>Цвет:</span>
                    <input type="color" v-model="editNoteColor" />
                  </div>
                </div>
                
                <div class="edit-actions">
                  <button class="save-edit-btn" @click="updateNote" :disabled="saving">Сохранить</button>
                  <button class="cancel-edit-btn" @click="cancelEdit">Отмена</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
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
import { ref, computed, onMounted, onUnmounted, nextTick } from "vue";
import api from "../services/api.js";
import CalendarHeader from "./CalendarHeader.vue";
import notificationService from "../services/notificationService.js";

// Состояние
const currentDate = ref(new Date());
const allNotes = ref([]);
const selectedDay = ref(null);
const newNoteTitle = ref("");
const newNoteContent = ref("");
const newNoteIsRecurring = ref(false);
const newNoteColor = ref("#2196F3");
const newNoteReminderTime = ref("");
const newNoteReminderType = ref("none");
const editNoteTitle = ref("");
const editNoteContent = ref("");
const editNoteIsRecurring = ref(false);
const editNoteColor = ref("#2196F3");
const editNoteReminderTime = ref("");
const editNoteReminderType = ref("none");
const editingNoteId = ref(null);
const saving = ref(false);
const dayCardsListRef = ref(null);

// Пагинация для левой панели
const visibleCardsCount = ref(8);
const scrollIndex = ref(0);

// Интервал проверки напоминаний
let reminderCheckInterval = null;

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

// Группировка заметок по датам
const notesByDate = computed(() => {
  const map = new Map();
  allNotes.value.forEach((note) => {
    const dateKey = note.date;
    if (!map.has(dateKey)) {
      map.set(dateKey, []);
    }
    map.get(dateKey).push(note);
  });
  return map;
});

// Все карточки дней (с добавлением текущего дня, даже если нет заметок)
const dayCards = computed(() => {
  const todayStr = formatDateYMD(new Date());
  
  const dates = Array.from(notesByDate.value.keys())
    .sort((a, b) => {
      const dateA = new Date(a);
      const dateB = new Date(b);
      return dateA - dateB;
    });
  
  const cards = dates.map(dateStr => ({
    dateStr,
    notes: notesByDate.value.get(dateStr) || [],
    isPast: new Date(dateStr) < new Date() && dateStr !== todayStr,
    isCurrentDay: dateStr === todayStr
  }));
  
  const hasTodayCard = cards.some(card => card.dateStr === todayStr);
  
  if (!hasTodayCard) {
    const todayCard = {
      dateStr: todayStr,
      notes: notesByDate.value.get(todayStr) || [],
      isPast: false,
      isCurrentDay: true
    };
    
    let insertIndex = 0;
    for (let i = 0; i < cards.length; i++) {
      if (new Date(cards[i].dateStr) > new Date(todayStr)) {
        insertIndex = i;
        break;
      }
      insertIndex = i + 1;
    }
    
    cards.splice(insertIndex, 0, todayCard);
  } else {
    const todayCardIndex = cards.findIndex(card => card.dateStr === todayStr);
    if (todayCardIndex !== -1) {
      cards[todayCardIndex] = {
        ...cards[todayCardIndex],
        notes: notesByDate.value.get(todayStr) || [],
        isCurrentDay: true,
        isPast: false
      };
    }
  }
  
  return cards;
});

// Отображаемые карточки с учётом скролла
const displayedDayCards = computed(() => {
  const start = scrollIndex.value;
  const end = start + visibleCardsCount.value;
  return dayCards.value.slice(start, end);
});

// Найти индекс текущего дня
const findTodayIndex = () => {
  const todayStr = formatDateYMD(new Date());
  return dayCards.value.findIndex(card => card.dateStr === todayStr);
};

// Сброс к позиции: 2 предыдущие карточки, текущая, и далее с центрированием текущего дня
function resetToTodayView() {
  setTimeout(() => {
    const todayIndex = findTodayIndex();
    if (todayIndex !== -1) {
      let newIndex = Math.max(0, todayIndex - 2);
      scrollIndex.value = newIndex;
      
      nextTick(() => {
        if (dayCardsListRef.value) {
          const cards = dayCardsListRef.value.querySelectorAll('.day-card');
          const targetCardIndex = todayIndex - scrollIndex.value;
          if (cards[targetCardIndex]) {
            cards[targetCardIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
          } else {
            dayCardsListRef.value.scrollTop = 0;
          }
        }
      });
    } else {
      scrollIndex.value = 0;
      nextTick(() => {
        if (dayCardsListRef.value) {
          dayCardsListRef.value.scrollTop = 0;
        }
      });
    }
  }, 100);
}

// Прокрутка вверх/вниз
function scrollUp() {
  if (scrollIndex.value > 0) {
    scrollIndex.value = Math.max(0, scrollIndex.value - 1);
    nextTick(() => {
      if (dayCardsListRef.value) {
        dayCardsListRef.value.scrollTop = 0;
      }
    });
  }
}

function scrollDown() {
  const maxScroll = Math.max(0, dayCards.value.length - visibleCardsCount.value);
  if (scrollIndex.value < maxScroll) {
    scrollIndex.value = Math.min(maxScroll, scrollIndex.value + 1);
    nextTick(() => {
      if (dayCardsListRef.value) {
        dayCardsListRef.value.scrollTop = 0;
      }
    });
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
    resetToTodayView();
  } catch (error) {
    console.error("Ошибка загрузки заметок:", error);
    allNotes.value = [];
  }
}

// Получить краткий текст заметки
function getNotePreview(note) {
  let text = note.title || note.content;
  if (text.length > 20) {
    return text.substring(0, 18) + '...';
  }
  return text;
}

// Получить текст типа напоминания
function getReminderTypeText(type) {
  const types = {
    once: 'Один раз',
    daily: 'Ежедневно',
    weekly: 'Еженедельно',
    monthly: 'Ежемесячно',
    none: 'Не напоминать'
  };
  return types[type] || type;
}

// Построение календарной сетки
const calendarDays = computed(() => {
  const year = currentYear.value;
  const month = currentMonth.value;

  const firstDayOfMonth = new Date(Date.UTC(year, month, 1));
  let startWeekday = firstDayOfMonth.getUTCDay();
  startWeekday = startWeekday === 0 ? 6 : startWeekday - 1;

  const daysInMonth = new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
  
  const notesMap = new Map();
  allNotes.value.forEach((note) => {
    if (note && note.date) {
      const dateParts = note.date.split('-');
      if (dateParts.length === 3) {
        const noteYear = parseInt(dateParts[0]);
        const noteMonth = parseInt(dateParts[1]) - 1;
        if (noteYear === year && noteMonth === month) {
          if (!notesMap.has(note.date)) {
            notesMap.set(note.date, []);
          }
          notesMap.get(note.date).push(note);
        }
      }
    }
  });

  const days = [];
  const todayStr = formatDateYMD(new Date());

  const prevMonthLastDay = new Date(Date.UTC(year, month, 0)).getUTCDate();
  for (let i = startWeekday - 1; i >= 0; i--) {
    const dayDate = new Date(Date.UTC(year, month - 1, prevMonthLastDay - i));
    const dateStr = formatDateYMD(dayDate);
    days.push({
      day: prevMonthLastDay - i,
      date: dayDate,
      dateStr: dateStr,
      isCurrentMonth: false,
      isToday: false,
      notes: [],
    });
  }

  for (let i = 1; i <= daysInMonth; i++) {
    const dayDate = new Date(Date.UTC(year, month, i));
    const dateStr = formatDateYMD(dayDate);
    const isToday = dateStr === todayStr;
    const dayNotes = notesMap.get(dateStr) || [];

    days.push({
      day: i,
      date: dayDate,
      dateStr: dateStr,
      isCurrentMonth: true,
      isToday,
      notes: dayNotes,
    });
  }

  let remaining = 42 - days.length;
  for (let i = 1; i <= remaining; i++) {
    const dayDate = new Date(Date.UTC(year, month + 1, i));
    const dateStr = formatDateYMD(dayDate);
    days.push({
      day: i,
      date: dayDate,
      dateStr: dateStr,
      isCurrentMonth: false,
      isToday: false,
      notes: [],
    });
  }

  return days;
});
// Отправка уведомления
function sendNotificationForNote(note, dateStr) {
  if (!notificationService.hasPermission()) return;
  
  const formattedDate = formatDateLong(dateStr);
  const title = note.title || 'Новая заметка';
  const timeInfo = note.reminder_time ? ` ⏰ ${note.reminder_time}` : '';
  const body = `${formattedDate}${timeInfo}\n${note.content.substring(0, 100)}${note.content.length > 100 ? '...' : ''}`;
  
  notificationService.sendNotification(title, {
    body: body,
    icon: '/favicon.ico',
    tag: `note-${note.id}`,
    onClick: () => {
      selectDayFromCard(dateStr);
    }
  });
}

async function checkTimeReminders() {
  if (!notificationService.hasPermission()) return;
  
  try {
    console.log('=== ПРОВЕРКА НАПОМИНАНИЙ ===');
    console.log('Текущее время:', new Date().toLocaleTimeString());
    
    const response = await api.get("/api/calendar/upcoming-reminders");
    
    // Выводим полный ответ от сервера для отладки
    console.log('Полный ответ от сервера:', response.data);
    
    let reminders = [];
    
    // Если есть отладочная информация от сервера
    if (response.data && typeof response.data === 'object') {
      if (response.data.debug) {
        console.log('=== ОТЛАДОЧНАЯ ИНФОРМАЦИЯ ===');
        console.log('Текущее время (сервер):', response.data.current_time);
        console.log('Все заметки с напоминаниями:', response.data.notes);
        reminders = response.data.reminders || [];
      } else if (Array.isArray(response.data)) {
        reminders = response.data;
      } else if (response.data.reminders && Array.isArray(response.data.reminders)) {
        reminders = response.data.reminders;
      }
    }
    
    console.log('Найдено напоминаний для отправки:', reminders.length);
    
    for (const reminder of reminders) {
      const title = reminder.title || '⏰ Напоминание';
      const body = `${reminder.content.substring(0, 100)}${reminder.content.length > 100 ? '...' : ''}`;
      
      console.log('Отправляем уведомление для заметки:', reminder.id, title);
      
      notificationService.sendNotification(title, {
        body: body,
        icon: '/favicon.ico',
        tag: `reminder-${reminder.id}`,
        requireInteraction: true,
        onClick: () => {
          selectDayFromCard(reminder.date);
        }
      });
      
      // Отмечаем, что уведомление отправлено
      try {
        await api.put(`/api/calendar/notes/${reminder.id}`, {
          ...reminder,
          is_notification_sent: true
        });
        console.log('Статус заметки обновлён:', reminder.id);
      } catch (putError) {
        console.error('Ошибка обновления статуса заметки:', putError);
      }
    }
  } catch (error) {
    console.error('Ошибка проверки напоминаний:', error);
  }
}
// Создание заметки
async function createNote() {
  if (!selectedDay.value || !newNoteContent.value.trim()) return;

  const dateToSave = selectedDay.value.dateStr;
  console.log('Сохраняем дату:', dateToSave);


  saving.value = true;
  try {
    const response = await api.post("/api/calendar/notes", {
      title: newNoteTitle.value.trim() || null,
      content: newNoteContent.value.trim(),
      date: selectedDay.value.dateStr,
      reminder_time: newNoteReminderType.value !== 'none' ? newNoteReminderTime.value : null,
      reminder_type: newNoteReminderType.value,
      is_recurring: newNoteIsRecurring.value,
      color: newNoteColor.value,
    });
    await loadNotes();
    
    const todayStr = formatDateYMD(new Date());
    if (selectedDay.value.dateStr === todayStr && notificationService.hasPermission()) {
      sendNotificationForNote(response.data, todayStr);
    }
    
    newNoteTitle.value = "";
    newNoteContent.value = "";
    newNoteIsRecurring.value = false;
    newNoteColor.value = "#2196F3";
    newNoteReminderTime.value = "";
    newNoteReminderType.value = "none";

    const updatedDay = calendarDays.value.find((d) => d.dateStr === selectedDay.value.dateStr);
    if (updatedDay) {
      selectedDay.value = updatedDay;
    }
    showToast("Заметка добавлена");
    resetToTodayView();
  } catch (error) {
    console.error("Ошибка создания заметки:", error);
    showToast("Не удалось создать заметку", "error");
  } finally {
    saving.value = false;
  }
  console.log('Отправляемая дата:', selectedDay.value.dateStr);
  console.log('Текущая дата (локальная):', formatDateYMD(new Date()));
}

// Редактирование заметки
function startEditNote(note) {
  editingNoteId.value = note.id;
  editNoteTitle.value = note.title || "";
  editNoteContent.value = note.content;
  editNoteIsRecurring.value = note.is_recurring || false;
  editNoteColor.value = note.color || "#2196F3";
  editNoteReminderTime.value = note.reminder_time || "";
  editNoteReminderType.value = note.reminder_type || "none";
}

async function updateNote() {
  if (!editNoteContent.value.trim()) return;

  saving.value = true;
  try {
    await api.put(`/api/calendar/notes/${editingNoteId.value}`, {
      title: editNoteTitle.value.trim() || null,
      content: editNoteContent.value.trim(),
      is_recurring: editNoteIsRecurring.value,
      color: editNoteColor.value,
      reminder_time: editNoteReminderType.value !== 'none' ? editNoteReminderTime.value : null,
      reminder_type: editNoteReminderType.value,
    });
    await loadNotes();
    cancelEdit();
    const updatedDay = calendarDays.value.find((d) => d.dateStr === selectedDay.value.dateStr);
    if (updatedDay) {
      selectedDay.value = updatedDay;
    }
    showToast("Заметка обновлена");
    resetToTodayView();
  } catch (error) {
    console.error("Ошибка обновления заметки:", error);
    showToast("Не удалось обновить заметку", "error");
  } finally {
    saving.value = false;
  }
}

// Удаление заметки
async function deleteNote(noteId) {
  try {
    await api.delete(`/api/calendar/notes/${noteId}`);
    await loadNotes();
    const updatedDay = calendarDays.value.find((d) => d.dateStr === selectedDay.value.dateStr);
    if (updatedDay) {
      selectedDay.value = updatedDay;
    }
    showToast("Заметка удалена");
    resetToTodayView();
  } catch (error) {
    console.error("Ошибка удаления заметки:", error);
    showToast("Не удалось удалить заметку", "error");
  }
}

function cancelEdit() {
  editingNoteId.value = null;
  editNoteTitle.value = "";
  editNoteContent.value = "";
  editNoteIsRecurring.value = false;
  editNoteColor.value = "#2196F3";
  editNoteReminderTime.value = "";
  editNoteReminderType.value = "none";
}

// Выбор дня
function selectDayFromCalendar(day) {
  selectedDay.value = day;
  newNoteTitle.value = "";
  newNoteContent.value = "";
  newNoteIsRecurring.value = false;
  newNoteColor.value = "#2196F3";
  newNoteReminderTime.value = "";
  newNoteReminderType.value = "none";
  cancelEdit();
}

function selectDayFromCard(dateStr) {
  const day = calendarDays.value.find((d) => d.dateStr === dateStr);
  if (day) {
    selectDayFromCalendar(day);
    const date = new Date(dateStr);
    currentDate.value = new Date(date.getFullYear(), date.getMonth(), 1);
  }
}

function closeModal() {
  selectedDay.value = null;
  newNoteTitle.value = "";
  newNoteContent.value = "";
  cancelEdit();
}

// Навигация
function previousMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
}

function nextMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
}

function goToToday() {
  const now = new Date();
  currentDate.value = new Date(Date.UTC(now.getFullYear(), now.getMonth(), 1));
  resetToTodayView();
}

// Форматирование дат
function formatDateYMD(date) {
  const y = date.getUTCFullYear();
  const m = String(date.getUTCMonth() + 1).padStart(2, "0");
  const d = String(date.getUTCDate()).padStart(2, "0");
  return `${y}-${m}-${d}`;
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



onMounted(async () => {
  await loadNotes();
  resetToTodayView();
  
  // Первая проверка сразу после загрузки
  console.log('Запуск первой проверки напоминаний...');
  checkTimeReminders();
  
  // Запускаем интервал проверки каждую минуту
  reminderCheckInterval = setInterval(() => {
    console.log('Проверка напоминаний:', new Date().toLocaleTimeString());
    checkTimeReminders();
  }, 30000);
});

onUnmounted(() => {
  if (reminderCheckInterval) {
    clearInterval(reminderCheckInterval);
    reminderCheckInterval = null;
  }
});
</script>

<style scoped>
.page {
  display: flex;
  width: 100%;
  padding: 15px;
  height: calc(100vh - 100px);
  overflow: hidden;
}

:root {
  --bg: #1a1a2e;
  --bg-card: #16213e;
  --bg-input: #0f3460;
  --text: #eee;
  --btn: #0f3460;
  --btn-hover: #16213e;
  --text-hover: #fff;
}

.layout {
  display: flex;
  gap: 20px;
  width: 100%;
  height: 100%;
}

@media (max-width: 1000px) {
  .layout {
    flex-direction: column;
  }
}

/* Левая панель - карточки */
.sidebar {
  width: 380px;
  padding: 16px 10px;
  border-radius: 10px;
  box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--bg);
}

@media (max-width: 1000px) {
  .sidebar {
    width: 100%;
    height: 400px;
  }
}

.sidebar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  flex-shrink: 0;
}

.reset-scroll-btn {
  padding: 6px 12px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  background: #4caf50;
  color: white;
  font-size: 12px;
  transition: all 0.3s ease;
}

.reset-scroll-btn:hover {
  background: #45a049;
  transform: translateY(-1px);
}

.day-cards-list {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-right: 5px;
}

.day-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.day-card:hover {
  transform: translateX(3px);
  background: rgba(255, 255, 255, 0.1);
}

.day-card.current-day {
  background: rgba(76, 175, 80, 0.15);
  border: 1px solid #4caf50;
}

.day-card.past-day {
  opacity: 0.7;
  background: rgba(100, 100, 100, 0.1);
}

.day-card.has-notes {
  border-left: 3px solid #2196f3;
}

.day-card.empty-day {
  background: rgba(255, 255, 255, 0.02);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  flex-wrap: wrap;
  gap: 8px;
}

.card-date {
  font-weight: bold;
  font-size: 14px;
  color: #ff9800;
}

.card-badge {
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 4px;
  background: #4caf50;
  color: white;
}

.past-badge {
  background: #9e9e9e;
}

.future-badge {
  background: #2196f3;
}

.card-notes {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.card-note-item {
  padding: 8px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 6px;
  border-left: 2px solid;
}

.note-title {
  margin-bottom: 4px;
  font-size: 12px;
}

.note-preview {
  font-size: 11px;
  opacity: 0.8;
  word-wrap: break-word;
}

.note-meta-small {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 6px;
  margin-top: 5px;
  font-size: 9px;
}

.recurring-icon, .reminder-icon {
  background: rgba(76, 175, 80, 0.3);
  padding: 1px 4px;
  border-radius: 4px;
}

.note-color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

.empty-day-message {
  text-align: center;
  padding: 8px;
  font-size: 11px;
  opacity: 0.5;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.add-hint {
  font-size: 9px;
  opacity: 0.6;
}

.sidebar-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 15px;
  padding-top: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  flex-shrink: 0;
}

.scroll-up-btn, .scroll-down-btn {
  padding: 6px 12px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  background: #2196f3;
  color: white;
  font-size: 12px;
  transition: all 0.3s ease;
}

.scroll-up-btn:disabled, .scroll-down-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #9e9e9e;
}

.scroll-up-btn:hover:not(:disabled), .scroll-down-btn:hover:not(:disabled) {
  background: #1976d2;
  transform: translateY(-1px);
}

.scroll-indicator {
  font-size: 12px;
  opacity: 0.7;
}

/* Основной контент */
.content {
  flex: 1;
  height: 100%;
}

.card {
  width: 100%;
  height: 100%;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 2px 2px 5px 3px rgba(0, 0, 0, 0.3);
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
  background: #4caf50;
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
  display: flex;
  flex-direction: column;
  width: 100%;
  height: calc(100% - 93px);
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
  height: 100%;
}

.calendar-day {
  padding: 6px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.03);
  cursor: pointer;
  position: relative;
  display: flex;
  flex-direction: column;
  transition: all 0.3s ease;
  min-height: 85px;
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

.day-number {
  font-size: 13px;
  font-weight: bold;
  margin-bottom: 4px;
}

.calendar-note-previews {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-top: 4px;
  max-height: 50px;
  overflow-y: auto;
}

.calendar-note-previews::-webkit-scrollbar {
  width: 2px;
}

.calendar-note-previews::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
}

.calendar-note-previews::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 2px;
}

.calendar-note-preview {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 8px;
  padding: 2px 4px;
  border-radius: 3px;
  background: rgba(33, 150, 243, 0.1);
  border-left: 2px solid;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.note-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.note-short-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
}

.note-time-icon {
  font-size: 8px;
  flex-shrink: 0;
}

.calendar-note-more {
  font-size: 8px;
  padding: 2px 4px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  text-align: center;
  cursor: pointer;
  color: #ff9800;
}

.calendar-note-more:hover {
  background: rgba(255, 152, 0, 0.2);
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
  max-height: 300px;
  overflow-y: auto;
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
  border-left: 3px solid;
}

.note-title-large {
  margin-bottom: 6px;
}

.note-text {
  margin: 0 0 10px 0;
  word-wrap: break-word;
  font-size: 13px;
}

.note-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.recurring-badge, .reminder-badge {
  font-size: 10px;
  background: rgba(76, 175, 80, 0.3);
  padding: 2px 6px;
  border-radius: 4px;
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

.reminder-settings {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 15px;
}

.form-group {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.form-label {
  font-size: 12px;
  color: #ff9800;
  min-width: 130px;
}

.time-input, .select-input {
  flex: 1;
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: var(--bg-input, rgba(255, 255, 255, 0.08));
  color: var(--text, #fff);
  font-size: 13px;
  transition: all 0.3s ease;
}

.time-input:focus, .select-input:focus {
  outline: none;
  border-color: #ff9800;
  background: rgba(255, 255, 255, 0.12);
}

.time-input:disabled, .select-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Стили для опций select */
.select-input option {
  background: var(--bg-card, #2a2a2a);
  color: var(--text, #fff);
}

.note-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  flex-wrap: wrap;
  gap: 10px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 13px;
  color: var(--text, #fff);
}

.checkbox-label input {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: #ff9800;
}

.color-picker {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-picker input {
  width: 40px;
  height: 30px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  cursor: pointer;
}

.note-input {
  width: 100%;
  padding: 8px;
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.05);
  color: var(--text);
  font-family: inherit;
  margin-bottom: 10px;
  font-size: 13px;
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

/* Toast notifications */
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
  transition: opacity 0.3s ease, transform 0.3s ease;
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
</style>