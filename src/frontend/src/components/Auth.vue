<template>
  <div class="auth">
    <form class="card" @submit.prevent="handleLogin">
      <div class="title">
        СИСТЕМА УЧЁТА ЛИЧНОГО СОСТАВА ВОЕННОЙ АКАДЕМИИ СВЯЗИ
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
  }

  .card {
    width: 360px;
    padding: 28px;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.4);
  }

  .title {
    font-size: 18px;
    font-weight: 600;
    text-align: center;
  }

  .subtitle {
    font-size: 12px;
    opacity: 0.9;
    text-align: center;
    margin: 5px 0;
  }

  input {
    padding: 12px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(0,0,0,0.2);
    outline: none;
    font-family: "Tektur", sans-serif;
    color: rgba(255,255,255,0.9);
  }

  input::placeholder {
    color: rgba(255,255,255,0.6);
  }

  .btn {
    margin-top: 8px;
    padding: 12px;
    border-radius: 10px;
    border: none;
    background: var(--btn);
    color: var(--text);
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.5s ease, color 0.5s ease, transform 0.5s ease;
  }

  .btn:hover {
    background: var(--btn-hover);
    color: var(--text-hover);
    transform: translateY(-2px);
  }

  .hint {
    font-size: 10px;
    text-align: center;
    opacity: 0.9;
  }
  form .error {
    font-size: 12px;
    color: red;
  }
</style>
