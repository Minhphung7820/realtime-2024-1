<template>
  <div class="auth-container">
    <h2>Login</h2>
    <form @submit.prevent="login" class="auth-form">
      <div class="form-group">
        <label>Email:</label>
        <input type="email" v-model="email" required />
      </div>
      <div class="form-group">
        <label>Password:</label>
        <input type="password" v-model="password" required />
      </div>
      <button type="submit" class="auth-button">Login</button>
    </form>
    <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
    <p class="register-link">
      Don't have an account? <router-link to="/register">Register here</router-link>
    </p>
  </div>
</template>

<script>
import { initializeSocket } from '../../plugins/socket';

export default {
  inject: ['$axios'],
  data() {
    return {
      email: '',
      password: '',
      errorMessage: '',
    };
  },
  methods: {
    async login() {
      try {
        const response = await this.$axios.post('/api/login', {
          email: this.email,
          password: this.password,
        });

        localStorage.setItem('token', response.data.token);
        window.location = '/';
      } catch (error) {
        this.errorMessage = error.response.data.message;
      }
    },
  },
};
</script>

<style scoped>
.auth-container {
  max-width: 400px;
  margin: 0 auto;
  padding: 20px;
  background-color: #f5f5f5;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

h2 {
  color: #333;
  text-align: center;
  margin-bottom: 20px;
}

.auth-form {
  display: flex;
  flex-direction: column;
}

.form-group {
  margin-bottom: 15px;
}

label {
  font-weight: bold;
  margin-bottom: 5px;
  display: block;
}

input[type="email"],
input[type="password"],
input[type="text"] {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
  box-sizing: border-box;
}

.auth-button {
  background-color: #3498db;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  font-weight: bold;
  margin-top: 10px;
  transition: background-color 0.3s;
}

.auth-button:hover {
  background-color: #2980b9;
}

.error-message {
  color: #e74c3c;
  font-size: 14px;
  margin-top: 15px;
  text-align: center;
}

.register-link {
  margin-top: 15px;
  text-align: center;
}

.register-link a {
  color: #3498db;
  text-decoration: none;
  font-weight: bold;
}

.register-link a:hover {
  text-decoration: underline;
}
</style>
