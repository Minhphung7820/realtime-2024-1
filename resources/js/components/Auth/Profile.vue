<template>
  <div class="profile-container bg-gray-50 shadow-md rounded-lg p-4 flex items-center gap-4">
    <!-- Avatar -->
    <div class="relative flex-shrink-0">
      <img
        :src="user.avatar"
        alt="Avatar"
        class="rounded-full w-16 h-16 sm:w-20 sm:h-20 object-cover border-2 border-gray-300"
      />
      <span
        class="absolute bottom-1 right-1 w-4 h-4 rounded-full"
        :class="{'bg-green-500': user.isOnline, 'bg-gray-400': !user.isOnline}"
      ></span>
    </div>

    <!-- Thông tin người dùng -->
    <div class="flex-1 min-w-0">
      <h3 class="font-bold text-lg text-gray-700 truncate">{{ user.name }}</h3>
      <p class="text-gray-500 text-sm truncate">{{ user.email }}</p>
      <p class="text-sm mt-1" :class="{'text-green-500': user.isOnline, 'text-gray-400': !user.isOnline}">
        {{ user.isOnline ? 'Online' : 'Offline' }}
      </p>
    </div>

    <!-- Nút hành động -->
    <div class="flex gap-2 flex-shrink-0">
      <button
        @click="editProfile"
        class="w-10 h-10 bg-blue-500 text-white rounded-full flex items-center justify-center hover:bg-blue-700"
        title="Chỉnh sửa"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path d="M17.414 2.586a2 2 0 010 2.828l-10 10a1 1 0 01-.39.243l-5 2a1 1 0 01-1.264-1.264l2-5a1 1 0 01.243-.39l10-10a2 2 0 012.828 0zM6 11l9-9m-5.5 4.5L4 13m1 4h4m0-1v-1a2 2 0 00-2-2H4m5 0H6m0-1h1" />
        </svg>
      </button>
      <button
        @click="logout"
        class="w-10 h-10 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-700"
        title="Đăng xuất"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path
            fill-rule="evenodd"
            d="M3 4a1 1 0 011-1h7a1 1 0 110 2H5v10h6a1 1 0 110 2H4a1 1 0 01-1-1V4zm11.707 5.707a1 1 0 00-1.414-1.414L10 11.586 8.707 10.293a1 1 0 10-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  inject: ['$axios', '$socket','$userProfile'],
  data() {
    return {
      user: {
        avatar: 'https://st3.depositphotos.com/1767687/16607/v/450/depositphotos_166074422-stock-illustration-default-avatar-profile-icon-grey.jpg',
        name: '',
        email: '',
        isOnline: false,
      },
      isLoading: false,
    };
  },
  async created() {
    this.isLoading = true;
    try {
      const response = this.$userProfile;
      this.user = { ...response, isOnline: true }; // Giả lập trạng thái trực tuyến
    } catch (error) {
      console.error('Failed to load profile:', error);
    } finally {
      this.isLoading = false;
    }
  },
  methods: {
    async logout() {
      try {
        await this.$axios.post('/api/logout');
        this.$socket.disconnect();
        localStorage.removeItem('token');
        window.location = '/login';
      } catch (error) {
        console.error('Logout failed:', error);
      }
    },
    editProfile() {
      console.log('Edit profile clicked!');
    },
  },
};
</script>

<style scoped>
.profile-container {
  display: flex;
  align-items: center;
  justify-content: space-between; /* Giữ khoảng cách giữa các phần tử */
  background-color: #f9fafb; /* Đồng bộ với nền trang */
  border-radius: 0;
  padding: 16px;
  width: 100%;
  max-width: 100%; /* Tránh tràn layout */
  gap: 16px;
}
.profile-container, .container, .left-pane, .right-pane {
  box-shadow: none !important;
}

img {
  border: 2px solid #e5e7eb;
}

button {
  transition: background-color 0.3s ease;
}

button:hover {
  background-color: #2563eb; /* Màu hover xanh đậm hơn */
}

button.bg-red-500:hover {
  background-color: #dc2626; /* Màu hover đỏ đậm hơn */
}

.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.flex-shrink-0 {
  flex-shrink: 0; /* Không thu nhỏ các nút hoặc avatar */
}

.min-w-0 {
  min-width: 0; /* Giúp xử lý tốt hơn với flex khi văn bản dài */
}
</style>
