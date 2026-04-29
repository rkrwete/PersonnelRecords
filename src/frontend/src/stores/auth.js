import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from "../services/api.js";
import { useRouter } from 'vue-router'



export const useAuthStore = defineStore('auth', () => {
    const router = useRouter()
    const user = ref(JSON.parse(localStorage.getItem('user')) || null)
    const token = ref(localStorage.getItem('token') || null)

    const isAuthenticated = computed(() => !!token.value)
    const role = computed(() => user.value?.role?.slug)

    const login = async (credentials) => {
        try {
            const response = await api.post('/api/login', credentials)
            token.value = response.data.access_token
            user.value = response.data.user

            localStorage.setItem('token', token.value)
            localStorage.setItem('user', JSON.stringify(user.value))

            api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
        } catch (error) {
            throw error.response?.data?.message || 'Login error'
        }
    }

    const logout = () => {
        token.value = null
        user.value = null

        localStorage.removeItem('token')
        localStorage.removeItem('user')

        delete api.defaults.headers.common['Authorization']
        router.push('/')
    }

    return {
        user,
        token,
        isAuthenticated,
        role,
        login,
        logout,
    }
})