<template>
  <div class="profile-container">
    <h2>Profile</h2>

    <!-- Hiển thị vòng xoay loading trong khi chờ API -->
    <div v-if="isLoading" class="loading-container">
      <div class="spinner"></div>
    </div>

    <!-- Hiển thị thông tin hồ sơ khi API hoàn tất -->
    <div v-else>
      <p><strong>Name:</strong> {{ user.name }}</p>
      <p><strong>Email:</strong> {{ user.email }}</p>
      <button @click="logout" class="logout-button">Logout</button>
    </div>
  </div>
</template>

<script>
export default {
  inject: ['$axios','$socket'],
  data() {
    return {
      user: {},
      isLoading: false // Thêm biến isLoading để kiểm tra trạng thái loading
    };
  },
  async created() {
    this.isLoading = true; // Bật trạng thái loading khi bắt đầu tải dữ liệu
    try {
      const response = await this.$axios.get('/api/get-profile');
      this.user = response.data;
    } catch (error) {
      console.error("Failed to load profile:", error);
    } finally {
      this.isLoading = false; // Tắt trạng thái loading sau khi API hoàn tất
    }
  },
  methods: {
    async logout() {
      try {
        await this.$axios.post('/api/logout');
        this.$socket.disconnect();
        localStorage.removeItem('token');
        this.$router.push('/login');
      } catch (error) {
        console.error("Logout failed:", error);
      }
    }
  }
};
</script>

<style scoped>
.profile-container {
  max-width: 400px;
  margin: 0 auto;
  padding: 20px;
  background-color: #f5f5f5;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
}

h2 {
  color: #333;
  margin-bottom: 20px;
}

.logout-button {
  background-color: #e74c3c;
  color: white;
  padding: 10px 15px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: bold;
  transition: background-color 0.3s;
}

.logout-button:hover {
  background-color: #c0392b;
}
</style>
