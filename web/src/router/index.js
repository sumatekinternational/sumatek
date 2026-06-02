import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuth } from '../stores/auth'

const routes = [
  { path: '/login', name: 'login', component: () => import('../pages/Login.vue'), meta: { public: true } },
  {
    path: '/',
    component: () => import('../layouts/AppLayout.vue'),
    children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard', name: 'dashboard', component: () => import('../pages/Dashboard.vue') },
      { path: 'eligibility', name: 'eligibility', component: () => import('../pages/Eligibility.vue') },
      { path: 'sponsors', name: 'sponsors', component: () => import('../pages/Sponsors.vue') },
      { path: 'workers', name: 'workers', component: () => import('../pages/Workers.vue') },
    ],
  },
]

export const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuth()
  if (!to.meta.public && !auth.isAuthenticated) return { name: 'login' }
  if (to.name === 'login' && auth.isAuthenticated) return { name: 'dashboard' }
})
