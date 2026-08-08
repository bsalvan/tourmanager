<template>
  <!-- 1. SI NON CONNECTÉ : ÉCRAN DE LOGIN -->
  <LoginPage v-if="!currentUser" @login-success="onLoginSuccess" />

  <!-- 2. SI CONNECTÉ : APPLICATION PRINCIPALE -->
  <div v-else class="min-h-screen bg-slate-50">

    <header class="bg-slate-900 text-white border-b border-slate-800 px-4 py-4">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        
        <!-- Ligne supérieure sur mobile / Logo à gauche sur PC -->
        <div class="w-full md:w-auto flex items-center justify-between cursor-pointer" @click="goBackToList">
          <div class="flex items-center space-x-3">
            <span class="text-xl font-bold tracking-wide text-emerald-400">Roadline MGT</span>
            <span class="text-xs bg-slate-800 text-slate-400 px-2 py-1 rounded">TourManager</span>
          </div>
          
          <!-- Optionnel : On peut déplacer le profil utilisateur ici sur mobile si besoin, ou le laisser dans la nav -->
        </div>

        <!-- Navigation principale -->
        <nav class="w-full md:w-auto flex flex-wrap items-center justify-center gap-2 md:space-x-4">
          <!-- Onglet Tournées -->
          <button
            @click="goBackToList"
            :class="[
              currentPage === 'list' || currentPage === 'detail' ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800',
              'px-3 py-2 rounded-lg text-sm font-medium transition-colors'
            ]"
          >
            📋 Tournées
          </button>

          <!-- Onglet Listes de diffusion (Seuls Admin et TM) -->
          <button
            v-if="canAccessSettings"
            @click="currentPage = 'distribution'"
            :class="[
              currentPage === 'distribution' ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800',
              'px-3 py-2 rounded-lg text-sm font-medium transition-colors'
            ]"
          >
            📧 Listes de diffusion
          </button>

          <!-- Onglet Paramètres (Seuls Admin et TM) -->
          <button
            v-if="canAccessSettings"
            @click="currentPage = 'settings'"
            :class="[
              currentPage === 'settings' ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800',
              'px-3 py-2 rounded-lg text-sm font-medium transition-colors'
            ]"
          >
            ⚙️ Paramètres
          </button>
        </nav>

        <!-- Infos utilisateur connecté & bouton Déconnexion -->
        <div class="w-full md:w-auto flex items-center justify-between md:justify-end space-x-3 border-t md:border-t-0 md:border-l border-slate-800 pt-3 md:pt-0 md:pl-4">
          <div class="text-left md:text-right">
            <p class="text-xs font-semibold text-slate-200">{{ currentUser.name }}</p>
            <p class="text-[10px] text-emerald-400 uppercase tracking-wider">{{ currentUser.role }}</p>
          </div>
          <button @click="logout" class="text-xs text-slate-400 hover:text-red-400 transition-colors">
            Déconnexion
          </button>
        </div>

      </div>
    </header>

    <main class="py-6">
      <ToursPage
        v-if="currentPage === 'list'"
        :user="currentUser"
        @navigate="goToDetail"
      />

      <!-- v-if="selectedTourId !== null" garantit que TourDetailPage reçoit bien un number strict -->
      <TourDetailPage
        v-if="currentPage === 'detail' && selectedTourId !== null"
        :tourId="selectedTourId"
        :user="currentUser"
        @back="goBackToList"
      />

      <DistributionPage
        v-if="currentPage === 'distribution' && canAccessSettings"
        :user="currentUser"
      />

      <SettingsPage
        v-if="currentPage === 'settings' && canAccessSettings"
        :user="currentUser"
      />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import ToursPage from './pages/ToursPage.vue'
import TourDetailPage from './pages/TourDetailPage.vue'
import SettingsPage from './pages/SettingsPage.vue'
import DistributionPage from './pages/DistributionPage.vue'
import LoginPage from './pages/LoginPage.vue'

const currentUser = ref<any>(null)
const currentPage = ref('list')
const selectedTourId = ref<number | null>(null)

// Seuls les rôles Admin et TM ont accès aux sections avancées
const canAccessSettings = computed(() => {
  if (!currentUser.value) return false
  return ['admin', 'tm'].includes(currentUser.value.role)
})

const onLoginSuccess = (user: any) => {
  currentUser.value = user
  currentPage.value = 'list'
}

const logout = () => {
  localStorage.removeItem('user')
  currentUser.value = null
}

function goToDetail(id: number) {
  selectedTourId.value = id
  currentPage.value = 'detail'
}

function goBackToList() {
  currentPage.value = 'list'
  selectedTourId.value = null
}

onMounted(() => {
  const savedUser = localStorage.getItem('user')
  if (savedUser) {
    currentUser.value = JSON.parse(savedUser)
  }
})
</script>
