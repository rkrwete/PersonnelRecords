// services/notificationService.js

class NotificationService {
  constructor() {
    this.permissionGranted = false;
    this.isSupported = 'Notification' in window;
  }

  // Запросить разрешение на отправку уведомлений
  async requestPermission() {
    if (!this.isSupported) {
      console.warn('Браузер не поддерживает уведомления');
      return false;
    }

    if (Notification.permission === 'granted') {
      this.permissionGranted = true;
      return true;
    }

    if (Notification.permission !== 'denied') {
      const permission = await Notification.requestPermission();
      this.permissionGranted = permission === 'granted';
      return this.permissionGranted;
    }

    return false;
  }

  // Отправить уведомление
  sendNotification(title, options = {}) {
    if (!this.permissionGranted || !this.isSupported) {
      console.warn('Нет разрешения на отправку уведомлений');
      return null;
    }

    const defaultOptions = {
      body: '',
      icon: '/favicon.ico',
      badge: '/favicon.ico',
      silent: false,
      vibrate: [200, 100, 200],
      tag: new Date().getTime().toString(),
      requireInteraction: false,
      data: {}
    };

    const notificationOptions = { ...defaultOptions, ...options };
    
    const notification = new Notification(title, notificationOptions);
    
    // Обработчик клика по уведомлению
    notification.onclick = (event) => {
      event.preventDefault();
      window.focus();
      if (options.onClick) {
        options.onClick();
      }
      notification.close();
    };

    // Автоматическое закрытие через 10 секунд
    setTimeout(() => notification.close(), 10000);

    return notification;
  }

  // Проверить, есть ли разрешение
  hasPermission() {
    return this.permissionGranted;
  }

  // Получить статус разрешения
  getPermissionStatus() {
    if (!this.isSupported) return 'unsupported';
    return Notification.permission;
  }
}

export default new NotificationService();