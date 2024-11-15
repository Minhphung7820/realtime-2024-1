import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import './bootstrap';
import { CkeditorPlugin } from '@ckeditor/ckeditor5-vue';

// Sử dụng window.baseURL đã thiết lập từ file Blade
const baseURL = window.baseURL;

// Cấu hình axios với baseURL lấy từ window.baseURL
const axiosInstance = axios.create({
  baseURL: baseURL,
});

// Thêm interceptor để đính kèm Bearer token vào mỗi request
axiosInstance.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
}, error => {
  return Promise.reject(error);
});

// Interceptor để kiểm tra mã trạng thái của phản hồi
axiosInstance.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      // Xóa token nếu có
      localStorage.removeItem('token');
      // Chuyển hướng về trang đăng nhập
      router.push('/login');
    }
    return Promise.reject(error);
  }
);

const app = createApp(App);
app.provide('$axios', axiosInstance);
app.use(router);
app.use(CkeditorPlugin);
app.mount('#app');