import { createRouter, createWebHashHistory, type RouteRecordRaw } from 'vue-router'
import SettingsPage from '../pages/SettingsPage.vue'
import DistributionLists from '../components/DistributionLists.vue' // Ajuste le chemin (../views/DistributionLists.vue si placé dans views)

const routes: Array<RouteRecordRaw> = [
  {
    path: '/settings',
    name: 'Settings',
    component: SettingsPage
  },
  {
    path: '/distribution-lists',
    name: 'DistributionLists',
    component: DistributionLists
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

export default router
