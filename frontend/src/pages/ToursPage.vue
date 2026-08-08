<template>
  <div class="max-w-4xl mx-auto p-6 bg-slate-50 min-h-screen">
    <header class="border-b border-slate-200 pb-5 mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold text-slate-950 tracking-tight">TourManager 🎵</h1>
        <p class="text-sm text-slate-500 mt-1">L'ERP léger dédié à la gestion de tournées.</p>
      </div>
      <button 
        @click="openCreateMode" 
        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm"
      >
        {{ showForm ? 'Fermer' : '➕ Nouvelle tournée' }}
      </button>
    </header>

    <div v-if="successMessage" class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm font-medium">
      ✅ {{ successMessage }}
    </div>

    <!-- FORMULAIRE UNIQUE : CREER / MODIFIER -->
    <section v-if="showForm" class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm mb-8">
      <h3 class="text-base font-bold text-slate-900 mb-4">
        {{ isEditMode ? '📝 Modifier la tournée' : '🌍 Créer une nouvelle tournée' }}
      </h3>
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nom de la tournée</label>
            <input type="text" v-model="tourForm.name" placeholder="ex: World Tour 2027" class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Destination</label>
            <input type="text" v-model="tourForm.destination" placeholder="ex: Europe, USA..." class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none" required />
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Date de début</label>
            <input type="date" v-model="tourForm.start_date" class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Date de fin</label>
            <input type="date" v-model="tourForm.end_date" class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none" required />
          </div>
        </div>

        <div v-if="isDateInvalid" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-xs font-medium">
          ⚠️ La date de fin ne peut pas être antérieure à la date de début de la tournée.
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="showForm = false" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">
            Annuler
          </button>
          <button 
            type="submit" 
            :disabled="isDateInvalid"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isEditMode ? 'Mettre à jour' : 'Enregistrer la tournée' }}
          </button>
        </div>
      </form>
    </section>

    <!-- LISTE DES TOURNÉES -->
    <div>
      <h2 class="text-lg font-bold text-slate-900 mb-4">Vos Tournées actives</h2>
      
      <div v-if="tours.length === 0" class="text-sm text-slate-400 italic bg-white border border-dashed border-slate-200 rounded-xl p-6 text-center">
        Aucune tournée active en base de données.
      </div>

      <div v-else class="grid grid-cols-1 gap-4">
        <div 
          v-for="tour in tours" 
          :key="tour.id" 
          @click="$emit('navigate', tour.id)"
          class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:border-blue-300 hover:shadow-md transition-all flex justify-between items-center group cursor-pointer"
        >
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                {{ tour.name || 'Tournée sans nom' }}
              </h3>
            </div>
            <p class="text-xs text-slate-500 mt-2 flex flex-wrap items-center gap-3">
              <span>🌍 {{ tour.destination || 'Non spécifiée' }}</span>
              <span>📅 {{ formatDate(tour.start_date) }} → {{ formatDate(tour.end_date) }}</span>
            </p>
          </div>

          <div class="flex items-center gap-1">
            <button @click.stop="openEditMode(tour)" class="text-xs text-slate-500 hover:text-blue-600 p-2 transition-colors">
              ⚙️ Éditer
            </button>
            <button @click.stop="handleDeleteTour(tour.id)" class="text-xs text-slate-300 hover:text-rose-600 p-2 transition-colors">
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'

defineEmits(['navigate'])

const tours = ref<any[]>([])
const showForm = ref(false)
const isEditMode = ref(false)
const currentTourId = ref<number | null>(null)
const successMessage = ref('')

const tourForm = reactive({
  name: '',
  destination: '',
  start_date: '',
  end_date: ''
})

const API_URL = ''

const isDateInvalid = computed(() => {
  if (!tourForm.start_date || !tourForm.end_date) return false
  return new Date(tourForm.end_date) < new Date(tourForm.start_date)
})

function formatDate(dateString: string): string {
  if (!dateString) return '...'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }).format(date)
}

async function loadTours() {
  try {
    const response = await fetch(`${API_URL}/api/tours`)
    if (response.ok) tours.value = await response.json()
  } catch (error) {
    console.error(error)
  }
}

function openCreateMode() {
  isEditMode.value = false
  currentTourId.value = null
  tourForm.name = ''
  tourForm.destination = ''
  tourForm.start_date = ''
  tourForm.end_date = ''
  showForm.value = !showForm.value
}

function openEditMode(tour: any) {
  isEditMode.value = true
  currentTourId.value = tour.id
  tourForm.name = tour.name
  tourForm.destination = tour.destination
  tourForm.start_date = tour.start_date ? tour.start_date.split('T')[0] : ''
  tourForm.end_date = tour.end_date ? tour.end_date.split('T')[0] : ''
  showForm.value = true
}

async function handleSubmit() {
  if (isDateInvalid.value) return

  if (isEditMode.value && currentTourId.value) {
    try {
      const response = await fetch(`${API_URL}/api/tours/${currentTourId.value}/events`)
      if (response.ok) {
        const events = await response.json()
        const targetStart = new Date(tourForm.start_date)
        const targetEnd = new Date(tourForm.end_date)
        
        const threatenedEvents = events.filter((e: any) => {
          const evDate = new Date(e.event_date)
          return evDate < targetStart || evDate > targetEnd
        })

        if (threatenedEvents.length > 0) {
          let msg = `⚠️ Voulez-vous vraiment raccourcir cette tournée ?\n`
          msg += `Les événements programmés les jours supprimés seront définitivement effacés :\n\n`
          threatenedEvents.forEach((e: any) => {
            msg += `- [${e.event_date.split('T')[0]}] ${e.event_time.substring(0,5)} : ${e.title}\n`
          })
          if (!confirm(msg)) return
        }
      }
    } catch (e) {
      console.error(e)
    }
    await handleUpdateTour()
  } else {
    await handleCreateTour()
  }
}

async function handleCreateTour() {
  try {
    const response = await fetch(`${API_URL}/api/tours`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(tourForm)
    })
    if (response.ok) {
      await loadTours()
      successMessage.value = "Tournée créée !"
      showForm.value = false
      setTimeout(() => successMessage.value = '', 3000)
    }
  } catch (error) { /**/ }
}

async function handleUpdateTour() {
  try {
    const response = await fetch(`${API_URL}/api/tours/${currentTourId.value}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(tourForm)
    })
    if (response.ok) {
      await loadTours()
      successMessage.value = "Tournée mise à jour !"
      showForm.value = false
      setTimeout(() => successMessage.value = '', 3000)
    }
  } catch (error) { /**/ }
}

async function handleDeleteTour(id: number) {
  if (!confirm("🚨 Supprimer cette tournée effacera l'ensemble de ses plannings. Continuer ?")) return
  try {
    const response = await fetch(`${API_URL}/api/tours/${id}`, { method: 'DELETE' })
    if (response.ok) {
      await loadTours()
      successMessage.value = "Tournée supprimée."
      setTimeout(() => successMessage.value = '', 2000)
    }
  } catch (error) { /**/ }
}

onMounted(() => loadTours())
</script>
