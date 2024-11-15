import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import NotFound from '../components/NotFound.vue';
import Login from '../components/Auth/Login.vue';
import Register from '../components/Auth/Register.vue';
import Profile from '../components/Auth/Profile.vue';
import Chat from '../components/Chat/Chat.vue';

const routes = [
  { path: '/', component: Home },
  { path: '/login', component: Login },
  { path: '/register', component: Register },
  { path: '/get-profile', component: Profile, meta: { requiresAuth: true } },
  { path: '/chat', component: Chat, meta: { requiresAuth: true } },
  // Route 404 phải đặt ở cuối
  { path: '/:pathMatch(.*)*', component: NotFound } // Bắt mọi đường dẫn không phù hợp
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  if (to.meta.requiresAuth && !token) {
    next('/login');
  } else {
    next();
  }
});

export default router;