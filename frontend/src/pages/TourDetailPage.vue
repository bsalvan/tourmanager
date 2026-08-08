<template>
  <div class="max-w-4xl mx-auto p-6 bg-slate-50 min-h-screen">
    <!-- Message de notification (Succès envoi Brevo) -->
    <div v-if="successMsg" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center justify-between shadow-sm">
      <span class="flex items-center gap-2 text-sm font-medium">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        {{ successMsg }}
      </span>
      <button @click="successMsg = ''" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
    </div>

    <!-- En-tête : Retour + Titre + Actions -->
    <header class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center gap-4">
        <button @click="$emit('back')" class="text-sm font-semibold text-blue-600 hover:underline flex items-center gap-1">
          ⬅️ <span>Retour aux tournées</span>
        </button>
        <h2 class="text-xl font-bold text-slate-900">{{ tour?.name || 'Détails de la tournée' }}</h2>
      </div>

      <!-- BOUTONS D'ACTION (ENVOI EMAIL + TÉLÉCHARGEMENTS PDF) -->
      <div class="flex flex-wrap gap-2 w-full sm:w-auto">
        <!-- Bouton Envoi par Email Brevo -->
        <button
          @click="showSendModal = true"
          class="w-full sm:w-auto justify-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 text-sm transition-colors shadow-sm"
        >
          <span>✉️ Envoyer par Email</span>
        </button>

        <!-- Bouton Télécharger Français -->
        <button
          @click="downloadPdf('fr')"
          :disabled="downloadingFr"
          class="w-full sm:w-auto justify-center bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 text-sm transition-colors shadow-sm disabled:opacity-50"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"></path></svg>
          {{ downloadingFr ? 'Génération...' : 'Télécharger (FR)' }}
        </button>

        <!-- Bouton Télécharger Anglais (US/EN) -->
        <button
          @click="downloadPdf('en')"
          :disabled="downloadingEn"
          class="w-full sm:w-auto justify-center bg-slate-600 hover:bg-slate-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 text-sm transition-colors shadow-sm disabled:opacity-50"
        >
          <span>🇺🇸 {{ downloadingEn ? 'Génération...' : 'Download (EN)' }}</span>
        </button>
      </div>
    </header>

    <!-- FORMULAIRE DE CRÉATION / ÉDITION D'ÉVÉNEMENT -->
    <section
      class="p-5 rounded-xl border shadow-sm mb-6 transition-all duration-200"
      :class="editingEventId ? 'border-amber-400 bg-amber-50/40 shadow-md' : 'border-slate-200 bg-white'"
    >
      <h3 class="text-sm font-bold uppercase tracking-wide mb-4" :class="editingEventId ? 'text-amber-800' : 'text-slate-500'">
        {{ editingEventId ? "✏️ Modification en cours de l'activité" : "➕ Ajouter une activité / Logistique" }}
      </h3>

      <form @submit.prevent="handleSaveEvent" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <!-- Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Date</label>
            <input
              type="date"
              v-model="eventForm.event_date"
              class="w-full border rounded-lg p-2 text-sm outline-none bg-white text-slate-900 focus:ring-2 focus:ring-amber-300"
              :class="editingEventId ? 'border-amber-300' : 'border-slate-200'"
              required
            />
          </div>

          <!-- Heure -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Heure de début</label>
            <input
              type="time"
              v-model="eventForm.event_time"
              class="w-full border rounded-lg p-2 text-sm outline-none bg-white text-slate-900 focus:ring-2 focus:ring-amber-300"
              :class="editingEventId ? 'border-amber-300' : 'border-slate-200'"
              required
            />
          </div>

          <!-- Type d'activité -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Type d'activité</label>
            <select
              v-model="eventForm.event_type"
              class="w-full border rounded-lg p-2 text-sm bg-white outline-none text-slate-900 focus:ring-2 focus:ring-amber-300"
              :class="editingEventId ? 'border-amber-300' : 'border-slate-200'"
              required
            >
              <option value="show">🎤 Concert / Show</option>
              <option value="hotel">🏨 Hôtel / Hébergement</option>
              <option value="train">🚄 Voyage Train</option>
              <option value="flight">✈️ Voyage Avion</option>
              <option value="road">🚐 Trajet Route / Bus</option>
              <option value="soundcheck">🔊 Balance / Soundcheck</option>
              <option value="promo">📻 Promo / Interview</option>
              <option value="rest">💤 Jour Off / Repos</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Titre de l'activité / Nom du lieu</label>
          <input
            type="text"
            v-model="eventForm.title"
            placeholder="ex: Hilton Center, Vol AF124, Salle Pleyel..."
            class="w-full border rounded-lg p-2 text-sm outline-none bg-white text-slate-900 focus:ring-2 focus:ring-amber-300"
            :class="editingEventId ? 'border-amber-300' : 'border-slate-200'"
            required
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Notes (Optionnel)</label>
          <textarea
            v-model="eventForm.notes"
            rows="2"
            placeholder="Numéro de chambre, détails de rendez-vous..."
            class="w-full border rounded-lg p-2 text-sm outline-none bg-white text-slate-900 focus:ring-2 focus:ring-amber-300"
            :class="editingEventId ? 'border-amber-300' : 'border-slate-200'"
          ></textarea>
        </div>

        <div class="flex items-center gap-2 pt-2">
          <button
            type="submit"
            class="font-medium px-5 py-2.5 rounded-lg text-sm transition-all shadow-sm text-white bg-blue-600 hover:bg-blue-700"
            :class="editingEventId ? '!bg-amber-500 hover:!bg-amber-600 ring-2 ring-amber-200' : ''"
          >
            {{ editingEventId ? 'Enregistrer les modifications' : "Enregistrer l'activité" }}
          </button>

          <button
            v-if="editingEventId"
            type="button"
            @click="cancelEdit"
            class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium px-4 py-2.5 rounded-lg text-sm transition-colors"
          >
            Annuler l'édition
          </button>
        </div>
      </form>
    </section>

    <!-- CHRONOLOGIE DE LA TOURNÉE -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <h3 class="text-base font-bold text-slate-900 mb-4">🗓️ Chronologie de la tournée</h3>

      <div v-if="events.length === 0" class="text-sm text-slate-400 italic text-center py-6">
        Aucune activité enregistrée.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="event in events"
          :key="event.id"
          class="flex gap-4 p-3 rounded-lg border items-center justify-between transition-all"
          :class="editingEventId === event.id ? 'bg-amber-50 border-amber-300 ring-2 ring-amber-100' : 'hover:bg-slate-50 border-slate-100'"
        >
          <div class="flex gap-3 items-center min-w-0">
            <span class="text-xl bg-slate-100 p-2 rounded-lg flex items-center justify-center w-10 h-10 flex-shrink-0">
              {{ getEventIcon(event.event_type || event.activity_type || event.type) }}
            </span>
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-bold text-blue-600 uppercase bg-blue-50 px-2 py-0.5 rounded">
                  {{ formatEventDate(event.event_date || event.date) }}
                  <template v-if="getEventTime(event)"> à {{ getEventTime(event) }}</template>
                </span>
              </div>
              <h4 class="text-sm font-bold text-slate-900 mt-0.5 truncate">
                {{ event.title || event.name || event.location || 'Activité sans titre' }}
              </h4>
              <p v-if="event.notes" class="text-xs text-slate-500 italic truncate">{{ event.notes }}</p>
            </div>
          </div>

          <div class="flex gap-2 items-center flex-shrink-0">
            <button
              @click="startEdit(event)"
              type="button"
              class="bg-slate-100 hover:bg-amber-100 text-slate-600 hover:text-amber-700 font-medium p-2 rounded-lg text-xs transition-colors flex items-center gap-1 border border-slate-200"
            >
              ✏️ <span class="hidden sm:inline">Modifier</span>
            </button>
            <button
              @click="deleteEvent(event.id)"
              type="button"
              class="bg-slate-100 hover:bg-rose-100 text-slate-400 hover:text-rose-600 p-2 rounded-lg text-xs transition-colors border border-slate-200"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODALE D'ENVOI D'EMAIL BREVO -->
    <SendTourModal
      v-if="showSendModal"
      :tour-id="props.tourId"
      :tour-name="tour?.name || 'Tournée'"
      :api-url="API_URL"
      @close="showSendModal = false"
      @sent="handleEmailSent"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import SendTourModal from '../components/SendTourModal.vue'

// URL pointant vers le serveur Freebox
const API_URL = 'https://bsalvan.freeboxos.fr:8900'

const props = defineProps<{ tourId: number }>()
defineEmits(['back'])

const tour = ref<any>(null)
const events = ref<any[]>([])
const editingEventId = ref<number | null>(null)

// Gestion modale Brevo & Notification
const showSendModal = ref(false)
const successMsg = ref('')

// États pour le téléchargement mobile
const downloadingFr = ref(false)
const downloadingEn = ref(false)

const eventForm = reactive({
  event_date: '',
  event_time: '',
  event_type: 'show',
  title: '',
  notes: ''
})

function getAuthHeaders() {
  const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
  const headers: Record<string, string> = {}
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }
  return headers
}

function handleEmailSent(msg: string) {
  successMsg.value = msg
  setTimeout(() => {
    successMsg.value = ''
  }, 5000)
}

// Fonction sécurisée pour télécharger le PDF sur mobile via Blob
async function downloadPdf(lang: 'fr' | 'en') {
  if (lang === 'fr') downloadingFr.value = true
  else downloadingEn.value = true

  try {
    const headers = getAuthHeaders()

    const response = await fetch(`${API_URL}/api/tours/${props.tourId}/download?lang=${lang}`, {
      method: 'GET',
      headers: headers
    })

    if (!response.ok) throw new Error(`Erreur HTTP: ${response.status}`)

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)

    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `roadbook-${props.tourId}-${lang}.pdf`)
    document.body.appendChild(link)
    link.click()

    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Erreur de téléchargement:', err)
    alert('Impossible de télécharger le PDF. Vérifiez votre connexion ou l’accès à bsalvan.freeboxos.fr.')
  } finally {
    if (lang === 'fr') downloadingFr.value = false
    else downloadingEn.value = false
  }
}

function getEventIcon(type: string): string {
  if (!type) return '📅'
  const cleanType = type.toLowerCase()
  const icons: Record<string, string> = {
    show: '🎤', concert: '🎤', 'concert / show': '🎤',
    hotel: '🏨', hébergement: '🏨',
    train: '🚄',
    flight: '✈️', avion: '✈️',
    road: '🚐', bus: '🚐',
    soundcheck: '🔊', balance: '🔊',
    promo: '📻', interview: '📻',
    rest: '💤', off: '💤'
  }
  return icons[cleanType] || '📅'
}

function getEventTime(event: any): string {
  const rawTime = event.event_time || event.start_time || event.time || ''
  return rawTime ? rawTime.toString().substring(0, 5) : ''
}

function formatEventDate(dateStr: string): string {
  if (!dateStr) return 'Date non spécifiée'

  try {
    const cleanDateStr = dateStr.toString().split('T')[0].split(' ')[0]
    const parts = cleanDateStr.split('-')
    if (parts.length !== 3) return dateStr

    const dateObj = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]))

    return new Intl.DateTimeFormat('fr-FR', {
      weekday: 'short',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    }).format(dateObj).replace('.', '')
  } catch (e) {
    return dateStr
  }
}

function resetForm() {
  eventForm.event_date = ''
  eventForm.event_time = ''
  eventForm.event_type = 'show'
  eventForm.title = ''
  eventForm.notes = ''
  editingEventId.value = null
}

async function loadData() {
  try {
    const headers = getAuthHeaders()
    const res = await fetch(`${API_URL}/api/tours/${props.tourId}`, {
      headers: headers
    })

    if (res.ok) {
      const data = await res.json()
      tour.value = data

      let rawList: any[] = []

      if (Array.isArray(data.events)) {
        rawList = data.events
      } else if (Array.isArray(data.dates)) {
        rawList = data.dates
      } else if (Array.isArray(data.activities)) {
        rawList = data.activities
      } else if (Array.isArray(data)) {
        rawList = data
      } else if (Array.isArray(data.days) || Array.isArray(data.tour_days)) {
        const days = data.days || data.tour_days
        days.forEach((day: any) => {
          const dayEvents = day.events || day.tour_day_events || day.activities || []
          dayEvents.forEach((evt: any) => {
            rawList.push({
              ...evt,
              event_date: evt.event_date || day.date || day.day_date
            })
          })
        })
      } else {
        const resEvents = await fetch(`${API_URL}/api/tours/${props.tourId}/events`, {
          headers: headers
        })
        if (resEvents.ok) {
          const eventsData = await resEvents.json()
          rawList = Array.isArray(eventsData) ? eventsData : (eventsData.data || [])
        }
      }

      events.value = rawList.map((item: any) => ({
        id: item.id,
        day_id: item.day_id,
        event_date: item.event_date || item.date || '',
        event_time: item.event_time || item.start_time || '',
        event_type: item.event_type || item.activity_type || 'show',
        title: item.title || item.name || item.location || 'Sans titre',
        notes: item.notes || ''
      }))
    }
  } catch (e) {
    console.error("Erreur lors du chargement de la chronologie :", e)
  }
}

function startEdit(event: any) {
  editingEventId.value = event.id

  const rawDate = event.event_date || event.date || ''
  eventForm.event_date = rawDate ? rawDate.toString().split('T')[0].split(' ')[0] : ''

  const rawTime = event.event_time || event.start_time || event.time || ''
  eventForm.event_time = rawTime ? rawTime.toString().substring(0, 5) : ''

  const rawType = event.event_type || event.activity_type || event.type || 'show'
  const validTypes = ['show', 'hotel', 'train', 'flight', 'road', 'soundcheck', 'promo', 'rest']

  let matchedType = 'show'
  for (const t of validTypes) {
    if (rawType.toLowerCase().includes(t)) {
      matchedType = t
      break
    }
  }
  eventForm.event_type = matchedType

  eventForm.title = event.title || event.name || event.location || ''
  eventForm.notes = event.notes || ''

  if ('day_id' in eventForm) {
    (eventForm as any).day_id = event.day_id || null
  }

  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function cancelEdit() {
  resetForm()
}

async function handleSaveEvent() {
  try {
    const payload = {
      tour_id: props.tourId,
      date: eventForm.event_date,
      event_time: eventForm.event_time,
      event_type: eventForm.event_type,
      title: eventForm.title,
      notes: eventForm.notes
    }

    const headers = {
      ...getAuthHeaders(),
      'Content-Type': 'application/json'
    }

    let response: Response

    if (editingEventId.value) {
      response = await fetch(`${API_URL}/api/events/${editingEventId.value}`, {
        method: 'PUT',
        headers: headers,
        body: JSON.stringify(payload)
      })

      if (!response.ok) {
        response = await fetch(`${API_URL}/api/tours/${props.tourId}/events/${editingEventId.value}`, {
          method: 'PUT',
          headers: headers,
          body: JSON.stringify(payload)
        })
      }
    } else {
      response = await fetch(`${API_URL}/api/tours/${props.tourId}/events`, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(payload)
      })

      if (!response.ok) {
        response = await fetch(`${API_URL}/api/events`, {
          method: 'POST',
          headers: headers,
          body: JSON.stringify(payload)
        })
      }
    }

    if (response.ok) {
      resetForm()
      await loadData()
    } else {
      const errData = await response.json().catch(() => ({}))
      alert(`Erreur d'enregistrement : ${errData.message || errData.error || 'Erreur serveur'}`)
    }
  } catch (e) {
    console.error("Erreur d'enregistrement :", e)
    alert("Impossible de contacter le serveur.")
  }
}

async function deleteEvent(id: number) {
  if (!confirm("Retirer cet événement du planning ?")) return
  try {
    const headers = getAuthHeaders()
    let response = await fetch(`${API_URL}/api/events/${id}`, { 
      method: 'DELETE',
      headers: headers
    })
    if (!response.ok) {
      response = await fetch(`${API_URL}/api/tours/${props.tourId}/activities/${id}`, { 
        method: 'DELETE',
        headers: headers
      })
    }

    if (response.ok) {
      if (editingEventId.value === id) resetForm()
      await loadData()
    }
  } catch (e) {
    console.error("Erreur lors de la suppression:", e)
  }
}

onMounted(() => loadData())
</script>
