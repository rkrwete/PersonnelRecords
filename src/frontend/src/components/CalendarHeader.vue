<template>
  <header class="calendar-header">
    <div class="header-content">
      <h1 class="header-title">Календарь событий Военной академии связи</h1>
      <div class="header-buttons">
        <button 
          v-if="!notificationsEnabled" 
          class="enable-notifications-btn"
          @click="enableNotifications"
          title="Включить системные уведомления"
        >
          🔔 Включить уведомления
        </button>
        <span v-else class="notifications-enabled" title="Уведомления включены">
          🔔 Уведомления активны
        </span>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import notificationService from '../services/notificationService.js';

const notificationsEnabled = ref(false);

onMounted(async () => {
  const permissionStatus = notificationService.getPermissionStatus();
  notificationsEnabled.value = permissionStatus === 'granted';
  if (notificationsEnabled.value) {
    notificationService.permissionGranted = true;
  }
});

async function enableNotifications() {
  const granted = await notificationService.requestPermission();
  notificationsEnabled.value = granted;
  
  if (granted) {
    notificationService.sendNotification('Уведомления включены', {
      body: 'Теперь вы будете получать напоминания о заметках',
      icon: '/favicon.ico'
    });
  } else {
    alert('Не удалось включить уведомления. Разрешите уведомления в настройках браузера.');
  }
}
</script>

<style scoped>
.calendar-header {
  background: linear-gradient(135deg, rgba(33, 150, 243, 0.15), rgba(255, 152, 0, 0.08));
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding: 16px 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
}

.header-title {
  font-family: "Tektur", sans-serif;
  font-size: 28px;
  font-weight: 700;
  margin: 0;
  background: linear-gradient(135deg, #fff, #ff9800);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  letter-spacing: 1px;
}

.header-buttons {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.enable-notifications-btn {
  padding: 8px 16px;
  border-radius: 20px;
  border: none;
  cursor: pointer;
  background: #4caf50;
  color: white;
  font-size: 14px;
  font-family: "Tektur", sans-serif;
  transition: all 0.3s ease;
}

.enable-notifications-btn:hover {
  background: #45a049;
  transform: translateY(-2px);
}

.notifications-enabled {
  padding: 8px 16px;
  border-radius: 20px;
  background: rgba(76, 175, 80, 0.2);
  color: #4caf50;
  font-size: 14px;
  font-family: "Tektur", sans-serif;
}

@media (max-width: 768px) {
  .calendar-header {
    padding: 12px 16px;
  }
  
  .header-title {
    font-size: 20px;
  }
  
  .enable-notifications-btn,
  .notifications-enabled {
    padding: 4px 10px;
    font-size: 10px;
  }
}
</style>