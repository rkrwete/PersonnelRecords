import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import router from './router'
import {useUserStore} from "./stores/user.js";

async function initApp() {
    const app = createApp(App);
    const pinia = createPinia();
    app.use(pinia);
    app.use(router);

    const userStore = useUserStore();

    const token = localStorage.getItem('token');
    if (token) {
        try {
            await userStore.fetchUserData();
        } catch (error) {
            if (error.response?.status === 401) {
                console.warn("Пользователь не авторизован");
            } else {
                console.error("Критическая ошибка сети:", error);
            }
        }
    }

    app.mount('#app');
}

initApp()