<template>
  <div class="profile-container bg-white shadow-md rounded-lg p-4 flex flex-col items-center">
    <!-- Avatar -->
    <div class="relative w-20 h-20">
      <img
        :src="user.avatar"
        alt="Avatar"
        class="rounded-full w-full h-full object-cover"
      />
      <span
        class="absolute bottom-1 right-1 w-4 h-4 rounded-full"
        :class="{'bg-green-500': user.isOnline, 'bg-gray-400': !user.isOnline}"
      ></span>
    </div>

    <!-- Thông tin người dùng -->
    <div class="mt-4 text-center">
      <h3 class="font-bold text-lg text-gray-700">{{ user.name }}</h3>
      <p class="text-gray-500 text-sm">{{ user.email }}</p>
      <p class="text-sm mt-1" :class="{'text-green-500': user.isOnline, 'text-gray-400': !user.isOnline}">
        {{ user.isOnline ? 'Đang trực tuyến' : 'Ngoại tuyến' }}
      </p>
    </div>

    <!-- Nút hành động -->
    <div class="mt-4 flex gap-2">
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
  inject: ['$axios', '$socket'],
  data() {
    return {
      user: {
        avatar : 'https://st3.depositphotos.com/1767687/16607/v/450/depositphotos_166074422-stock-illustration-default-avatar-profile-icon-grey.jpg',
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
      const response = await this.$axios.get('/api/get-profile');
      this.user = { ...response.data, isOnline: true }; // Giả lập trạng thái trực tuyến
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
  width: 100%;
  max-width: 300px;
  margin: auto;
}

img {
  border: 2px solid #e5e7eb;
}

button:hover {
  transition: background-color 0.3s ease;
}
</style>
