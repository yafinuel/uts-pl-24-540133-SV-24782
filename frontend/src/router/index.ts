import { createRouter, createWebHistory } from 'vue-router'
import { getToken } from '../lib/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      component: () => import('../views/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/',
      redirect: '/products',
    },
    {
      path: '/',
      component: () => import('../components/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: 'products',
          component: () => import('../views/ProductsView.vue'),
        },
        {
          path: 'categories',
          component: () => import('../views/CategoriesView.vue'),
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/products',
    },
  ],
})

router.beforeEach((to) => {
  const token = getToken()
  if (to.meta.requiresAuth && !token) return '/login'
  if (to.meta.guest && token) return '/products'
})

export default router
