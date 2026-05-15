import { createRouter, createWebHistory } from 'vue-router'
import Auth from "../components/Auth.vue"
import MainCommander from "../components/MainCommander.vue"
import First from "../components/First.vue"
import SettingsCommander from "../components/SettingsCommander.vue"
import AdminPanel from "../components/AdminPanel.vue"
import { useAuthStore } from "../stores/auth.js";

const routes = [
    {
        path: '/',
        component: First
    },
    {
        path: '/main',
        component: MainCommander,
        meta: { requiresAuth: true }
    },
    {
        path: '/auth',
        component: Auth
    },
    {
        path: '/settings',
        component: SettingsCommander,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin',
        component: AdminPanel,
        meta: { requiresAuth: true }
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, from, next) => {
    const auth = useAuthStore()

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        next('/auth')
    } else {
        next()
    }
})

export default router