<script setup>
  import { useAuthStore } from '../stores/auth';
  import { useUserStore } from '../stores/user.js';
  const auth = useAuthStore();
  const user = useUserStore();
</script>

<template>
  <header class="header">
    <div class="header-left">
      {{ user.unitName }}
    </div>

    <nav class="header-center">
      <router-link to="/main">Главная</router-link>
      <router-link to="/settings">Настройки</router-link>
    </nav>

    <div class="header-right">
      <button class="logout" @click="auth.logout()">
        Выход
      </button>
    </div>
  </header>
</template>

<style scoped>
.header {
  padding: 14px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(0,0,0,0.35);
  backdrop-filter: blur(14px);
  color: #e6f4ef;
  box-shadow: 0 10px 40px rgba(0,0,0,0.4);
  border-radius: 0 0 16px 16px;
  margin-bottom: 20px;
}

.header-left {
  font-weight: 700;
  font-size: 14px;
}

.header-center {
  display: flex;
  gap: 18px;
}

.header-center a {
  color: #e6f4ef;
  text-decoration: none;
  opacity: 0.8;
  font-size: 14px;
}

.header-center a:hover {
  opacity: 1;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logout {
  padding: 6px 12px;
  border-radius: 10px;
  border: none;
  background: #ef4444;
  color: white;
  cursor: pointer;
  font-size: 13px;
}
</style>