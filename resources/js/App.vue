<template>
    <div>
        <!-- Thanh điều hướng -->
        <nav>
            <router-link to="/">Home</router-link>
        </nav>

        <!-- Đây là nơi Vue Router sẽ hiển thị các component theo route -->
        <router-view></router-view>
    </div>
</template>

<script>
export default {
    name: 'App',
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
