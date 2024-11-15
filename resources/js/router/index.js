import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import NotFound from '../components/NotFound.vue';

const routes = [
  { path: '/', component: Home },
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