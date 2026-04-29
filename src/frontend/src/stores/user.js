import { defineStore } from 'pinia';
import api from '../services/api.js';

export const useUserStore = defineStore('user', {
    state: () => ({
        userId: null,
        userName: '',
        unitId: null,
        unitName: '',
        roleId: '',
        isLoaded: false,
    }),

    actions: {
        async fetchUserData() {
            try {
                const response = await api.get('/api/user/profile');
                const { user_id, user_name, unit_id, unit_name, role_id } = response.data;

                this.userId = user_id;
                this.userName = user_name;
                this.unitId = unit_id;
                this.unitName = unit_name;
                this.roleId = role_id;

                this.isLoaded = true;
            } catch (error) {
                console.error('Ошибка при загрузке профиля:', error);
                throw error;
            }
        }
    }
});