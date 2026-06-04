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
        
        <button 
          v-if="isDevMode && notificationsEnabled" 
          class="test-notifications-btn"
          @click="startTestNotifications"
          title="Тестовые уведомления каждые 5 секунд"
        >
          🧪 Тест уведомлений
        </button>
        <button 
          v-if="isTestRunning" 
          class="stop-test-btn"
          @click="stopTestNotifications"
          title="Остановить тест"
        >
          ⏹️ Остановить тест
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import notificationService from '../services/notificationService.js';

const notificationsEnabled = ref(false);
const isDevMode = ref(import.meta.env.DEV);
const isTestRunning = ref(false);
let testInterval = null;

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
    alert('Не удалось включить уведомления.\n\nПожалуйста, разрешите уведомления в настройках браузера:\n\n1. Нажмите на значок замка 🔒 слева от адресной строки\n2. Найдите "Уведомления" в списке\n3. Выберите "Разрешить"\n4. Обновите страницу');
  }
}

function startTestNotifications() {
  if (!notificationsEnabled.value) {
    alert('Сначала включите уведомления!');
    return;
  }
  
  isTestRunning.value = true;
  
  notificationService.sendNotification('🧪 Тест уведомлений запущен', {
    body: 'Уведомления будут приходить каждые 5 секунд',
    icon: '/favicon.ico'
  });
  
  let counter = 1;
  testInterval = setInterval(() => {
    const now = new Date();
    const timeStr = now.toLocaleTimeString();
    
    notificationService.sendNotification(`🧪 Тестовое уведомление #${counter}`, {
      body: `Время: ${timeStr}\nУведомления работают корректно!`,
      icon: '/favicon.ico',
      tag: `test-${counter}`,
      onClick: () => {
        console.log(`Тестовое уведомление #${counter} было нажато`);
      }
    });
    counter++;
  }, 5000);
}

function stopTestNotifications() {
  if (testInterval) {
    clearInterval(testInterval);
    testInterval = null;
  }
  isTestRunning.value = false;
  
  notificationService.sendNotification('🧪 Тест уведомлений остановлен', {
    body: 'Уведомления больше не будут приходить',
    icon: '/favicon.ico'
  });
}

onUnmounted(() => {
  if (testInterval) {
    clearInterval(testInterval);
  }
});
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

.test-notifications-btn {
  padding: 8px 16px;
  border-radius: 20px;
  border: none;
  cursor: pointer;
  background: #ff9800;
  color: white;
  font-size: 14px;
  font-family: "Tektur", sans-serif;
  transition: all 0.3s ease;
}

.test-notifications-btn:hover {
  background: #f57c00;
  transform: translateY(-2px);
}

.stop-test-btn {
  padding: 8px 16px;
  border-radius: 20px;
  border: none;
  cursor: pointer;
  background: #f44336;
  color: white;
  font-size: 14px;
  font-family: "Tektur", sans-serif;
  transition: all 0.3s ease;
}

.stop-test-btn:hover {
  background: #d32f2f;
  transform: translateY(-2px);
}

@media (max-width: 768px) {
  .calendar-header {
    padding: 12px 16px;
  }
  
  .header-title {
    font-size: 20px;
  }
  
  .enable-notifications-btn,
  .notifications-enabled,
  .test-notifications-btn,
  .stop-test-btn {
    padding: 4px 10px;
    font-size: 10px;
  }
}
</style>