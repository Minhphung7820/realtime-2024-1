// stores/useSharedData.js
import { defineStore } from 'pinia';
import axios from 'axios';

export const onlineStore = defineStore('sharedData', {
    state: () => ({
        data: null, // Dữ liệu API
        isLoading: false, // Trạng thái đang tải
        error: null, // Thông báo lỗi nếu có
    }),

    actions: {
        async fetchData(important = false) {
            if (this.data && !important) return; // Nếu đã có dữ liệu, không gọi lại API

            this.isLoading = true;
            this.error = null;

            try {
                const token = localStorage.getItem('token'); // Lấy token từ localStorage
                const response = await axios.get(`http://localhost:6060/api/online-users`, {
                    headers: {
                        Authorization: `Bearer ${token}`, // Truyền token vào header
                    },
                });
                this.data = response.data; // Lưu dữ liệu vào state
            } catch (err) {
                this.error = err.message || 'Failed to fetch data';
            } finally {
                this.isLoading = false;
            }
        },
    },
});