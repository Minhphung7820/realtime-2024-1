<template>
    <div class="app-container">
        <!-- Thanh điều hướng -->
        <nav class="header">
            <router-link to="/">Home</router-link>
            <router-link v-if="isAuthenticated" to="/get-profile">Get Profile</router-link>
            <router-link v-if="isAuthenticated" to="/chat">Chat</router-link>
            <router-link v-else to="/login">Login</router-link>
        </nav>

        <!-- Đây là nơi Vue Router sẽ hiển thị các component theo route -->
        <router-view class="content"></router-view>
    </div>
</template>

<script>
export default {
    name: 'App',
    data() {
        return {
            isAuthenticated: false
        };
    },
    created() {
        // Kiểm tra token khi component được khởi tạo
        this.checkAuthentication();
    },
    watch: {
        '$route'() {
            this.checkAuthentication();
        }
    },
    methods: {
        checkAuthentication() {
            // Kiểm tra xem token có tồn tại trong localStorage không
            this.isAuthenticated = !!localStorage.getItem('token');
        }
    }
};
</script>

<style scoped>
/* Đặt cấu trúc flex cho App.vue */
.app-container {
    display: flex;
    flex-direction: column;
    height: 100vh; /* Chiếm toàn bộ chiều cao màn hình */
}

.header {
    padding: 10px;
    background-color: #333;
    color: white;
}

.header a {
    color: white;
    margin-right: 10px;
    text-decoration: none;
}

.header a.router-link-active {
    font-weight: bold;
}

.content {
    flex: 1; /* Chiếm toàn bộ chiều cao còn lại */
    overflow-y: auto; /* Cho phép cuộn nếu nội dung vượt quá chiều cao */
}
</style>
