<template>
  <div class="auth">
    <form class="card" @submit.prevent="handleLogin">
      <div class="title">
        Электронная строевая записка
      </div>

      <p class="subtitle">Вход в систему</p>

      <input v-model="form.login" type="text" placeholder="Логин" required/>
      <input v-model="form.password" type="password" placeholder="Пароль" required/>
      <button type="submit" :disabled="loading" class="btn">Войти</button>
      <p v-if="error" class="error">{{ error }}</p>

      <p class="hint">Только для авторизованного персонала</p>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();
const loading = ref(false);
const error = ref('');

const form = reactive({
  login: '',
  password: ''
});

const handleLogin = async () => {
  loading.value = true;
  error.value = '';
  try {
    await auth.login(form);
    router.push('/main');
  } catch (err) {
    error.value = 'Ошибка входа. Проверьте логин и пароль.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.auth {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;


  font-family: system-ui;
}

.card {
  width: 360px;
  padding: 28px;
  border-radius: 20px;

  background: rgba(0, 0, 0, 0.25);
  backdrop-filter: blur(12px);

  display: flex;
  flex-direction: column;
  gap: 12px;

  color: #e6f4ef;
  box-shadow: 0 20px 60px rgba(0,0,0,0.4);
}

.title {
  font-size: 18px;
  font-weight: 700;
  text-align: center;
}

.subtitle {
  font-size: 12px;
  opacity: 0.8;
  text-align: center;
  margin-bottom: 10px;
}

input {
  padding: 12px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.2);
  background: rgba(0,0,0,0.2);
  color: white;
  outline: none;
}

input::placeholder {
  color: rgba(255,255,255,0.6);
}

.btn {
  margin-top: 8px;
  padding: 12px;
  border-radius: 14px;
  border: none;
  background: #10b981;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s;
}

.btn:hover {
  background: #059669;
}

.hint {
  font-size: 11px;
  text-align: center;
  opacity: 0.6;
  margin-top: 6px;
}
form .error {
  font-size: 12px;
  color: red;
}
</style>
