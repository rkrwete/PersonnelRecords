import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api.js'
import { useUserStore } from '../stores/user.js'

export const useUnitStore = defineStore('unit', () => {
    const structure = ref([])
    const personnel = ref(false)
    const loading = ref(false)

    const userStore = useUserStore()

    const enrich = (data = []) =>
        data.map(cat => ({
            ...cat,
            _newTitle: '',
            _newCount: null
        }))

    const fetchStructure = async () => {
        loading.value = true
        try {
            const { data } = await api.get(`/api/units/${userStore.unitId}/structure`)
            structure.value = enrich(data)
        } catch (e) {
            console.error('fetchStructure error:', e)
        } finally {
            loading.value = false
        }
    }

    const updateStructure = async (payload) => {
        loading.value = true
        try {
            const { data } = await api.post(
                `/api/units/${userStore.unitId}/structure`,
                { categories: payload }
            )
            structure.value = enrich(data)
            return data
        } catch (e) {
            console.error('updateStructure error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchPersonnel = async () => {
        loading.value = true
        try {
            const res = await api.get(`/api/units/${userStore.unitId}/personnel`)
            return res.data.data
        } catch (e) {
            console.error('fetchPersonnel error:', e)
            return []
        } finally {
            loading.value = false
        }
    }

    const updatePersonnel = async (payload) => {
        loading.value = true
        try {
            const { data } = await api.post(
                `/api/units/${userStore.unitId}/personnel`,
                payload
            )
            return data
        } catch (e) {
            console.error('updatePersonnel error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    return {
        structure,
        loading,
        fetchStructure,
        updateStructure,
        updatePersonnel,
        fetchPersonnel
    }
})