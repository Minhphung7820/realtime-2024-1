import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import './bootstrap';
import { CkeditorPlugin } from '@ckeditor/ckeditor5-vue';
import { initializeSocket } from './plugins/socket';

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


const app = createApp(App);
// Kiểm tra xem người dùng đã đăng nhập chưa
const token = localStorage.getItem('token');
let socket;

if (token) {
  try {
    const getProfile = await axiosInstance.get('/api/get-profile');
    const userID = getProfile.data.id;

    // Khởi tạo socket khi user đã đăng nhập
    socket = initializeSocket(userID);
    app.provide('$socket', socket);
    app.provide('$profile', getProfile.data);
  } catch (error) {
    console.error("Failed to load profile:", error);
  }
}
// Interceptor để kiểm tra mã trạng thái của phản hồi
axiosInstance.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      // Xóa token nếu có
      localStorage.removeItem('token');
      // Ngắt kết nối socket nếu đang kết nối
      if (socket) {
        socket.disconnect();
        socket = null;
        console.log('Socket connection terminated due to 401 response.');
      }

      // Chuyển hướng về trang đăng nhập
      router.push('/login');
    }
    return Promise.reject(error);
  }
);

app.provide('$axios', axiosInstance);
app.use(router);
app.use(CkeditorPlugin);
app.mount('#app');