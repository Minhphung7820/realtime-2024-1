<template>
    <div>
        <!-- Thanh điều hướng -->
        <nav>
            <router-link to="/">Home</router-link>
            <!-- <router-link v-if="isAuthenticated" to="/get-profile">Get Profile</router-link> -->
            <router-link v-if="isAuthenticated" to="/get-profile">Get Profile</router-link>
            <router-link v-if="isAuthenticated" to="/chat">Chat</router-link>
            <router-link v-else to="/login">Login</router-link>
        </nav>

        <!-- Đây là nơi Vue Router sẽ hiển thị các component theo route -->
        <router-view></router-view>
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
        // Không cần `isLoading` khi route thay đổi
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
/* Phong cách CSS cho App.vue */
nav {
    padding: 10px;
    background-color: #333;
    color: white;
}

nav a {
    color: white;
    margin-right: 10px;
    text-decoration: none;
}

nav a.router-link-active {
    font-weight: bold;
}
</style>
