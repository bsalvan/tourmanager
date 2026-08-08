<template>
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden">

      <!-- En-tête -->
      <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
        <div>
          <h3 class="text-lg font-bold text-slate-800">Envoyer le planning par Email</h3>
          <p class="text-xs text-slate-500">Tournée : <span class="font-semibold text-slate-700">{{ tourName }}</span></p>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 text-2xl font-bold leading-none">&times;</button>
      </div>

      <div class="p-5 space-y-4">
        <!-- Message de statut / erreur -->
        <div v-if="errorMsg" class="p-3 bg-red-50 text-red-700 text-xs rounded-lg border border-red-200">
          {{ errorMsg }}
        </div>

        <!-- Mode de sélection (Onglets) -->
        <div class="flex border-b border-slate-200 text-xs font-semibold">
          <button
            type="button"
            @click="targetType = 'list'"
            :class="['flex-1 py-2 text-center border-b-2 transition-colors', targetType === 'list' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-700']"
          >
            📋 Liste de Distribution
          </button>
          <button
            type="button"
            @click="targetType = 'individual'"
            :class="['flex-1 py-2 text-center border-b-2 transition-colors', targetType === 'individual' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-700']"
          >
            👤 Contacts Individuels
          </button>
        </div>

        <!-- OPTION A : Sélection de Liste -->
        <div v-if="targetType === 'list'" class="space-y-2">
          <label class="block text-xs font-semibold text-slate-600 uppercase">Choisir une liste Brevo / Distribution</label>
          <select v-model="selectedListId" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-emerald-500">
            <option :value="null" disabled>-- Sélectionner une liste --</option>
            <option v-for="list in distributionLists" :key="list.id" :value="list.id">
              {{ list.name }} ({{ list.contacts_count || list.contacts?.length || 0 }} membres)
            </option>
          </select>
        </div>

        <!-- OPTION B : Sélection Individuelle -->
        <div v-else class="space-y-2">
          <label class="block text-xs font-semibold text-slate-600 uppercase">Sélectionner un ou plusieurs destinataires</label>
          <input
            v-model="contactSearch"
            type="text"
            placeholder="Filtrer un nom ou email..."
            class="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-md mb-2"
          />
          <div class="max-h-48 overflow-y-auto border border-slate-200 rounded-lg divide-y divide-slate-100 p-1">
            <label
              v-for="c in filteredContacts"
              :key="c.id || c.email"
              class="flex items-center justify-between p-2 hover:bg-slate-50 rounded cursor-pointer text-xs"
            >
              <div class="flex items-center space-x-2">
                <input type="checkbox" :value="c.email" v-model="selectedEmails" class="rounded text-emerald-600 focus:ring-emerald-500" />
                <span class="font-medium text-slate-800">{{ c.name || `${c.first_name || ''} ${c.last_name || ''}`.trim() }}</span>
              </div>
              <span class="text-slate-400 font-mono text-[11px]">{{ c.email }}</span>
            </label>
          </div>
        </div>

        <!-- Message optionnel -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Message personnalisé (Optionnel)</label>
          <textarea
            v-model="customNote"
            rows="2"
            placeholder="Ex: Voici les dernières mises à jour concernant la feuille de route..."
            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500"
          ></textarea>
        </div>
      </div>

      <!-- Actions -->
      <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end space-x-2">
        <button
          @click="$emit('close')"
          class="px-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-100 transition"
        >
          Annuler
        </button>
        <button
          @click="sendEmail"
          :disabled="sending || !isFormValid"
          class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition flex items-center gap-2"
        >
          <span v-if="sending">Envoi en cours via Brevo...</span>
          <span v-else>🚀 Envoyer via Brevo</span>
        </button>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

const props = defineProps<{
  tourId: number | string
  tourName: string
  apiUrl: string
}>()

const emit = defineEmits(['close', 'sent'])

const targetType = ref<'list' | 'individual'>('list')
const distributionLists = ref<any[]>([])
const allContacts = ref<any[]>([])
const contactSearch = ref('')

const selectedListId = ref<number | null>(null)
const selectedEmails = ref<string[]>([])
const customNote = ref('')

const sending = ref(false)
const errorMsg = ref('')

// Normalisation de l'URL pour éviter les doubles slashes
const baseUrl = computed(() => props.apiUrl.replace(/\/+$/, ''))

const filteredContacts = computed(() => {
  if (!contactSearch.value) return allContacts.value
  const q = contactSearch.value.toLowerCase()
  return allContacts.value.filter(c =>
    (c.name || `${c.first_name || ''} ${c.last_name || ''}`).toLowerCase().includes(q) ||
    (c.email || '').toLowerCase().includes(q)
  )
})

const isFormValid = computed(() => {
  if (targetType.value === 'list') {
    return selectedListId.value !== null
  }
  return selectedEmails.value.length > 0
})

async function fetchData() {
  try {
    // Charger les listes
    const resLists = await fetch(`${baseUrl.value}/api/distribution-lists`)
    if (resLists.ok) {
      const data = await resLists.json()
      distributionLists.value = Array.isArray(data) ? data : (data.data || [])
    }

    // Charger les contacts individuels
    const resContacts = await fetch(`${baseUrl.value}/api/contacts`)
    if (resContacts.ok) {
      const data = await resContacts.json()
      allContacts.value = Array.isArray(data) ? data : (data.data || [])
    }
  } catch (err) {
    console.error('Erreur chargement données destinataires:', err)
  }
}

async function sendEmail() {
  sending.value = true
  errorMsg.value = ''

  const payload = {
    tour_id: props.tourId,
    note: customNote.value,
    target_type: targetType.value,
    list_id: targetType.value === 'list' ? selectedListId.value : null,
    recipients: targetType.value === 'individual' ? selectedEmails.value : []
  }

  try {
    const res = await fetch(`${baseUrl.value}/api/tours/${props.tourId}/send-email`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })

    const data = await res.json()

    if (res.ok && data.success) {
      emit('sent', data.message || 'Email envoyé avec succès !')
      emit('close')
    } else {
      errorMsg.value = data.error || data.message || 'Erreur lors de l\'envoi Brevo'
    }
  } catch (err) {
    console.error('Erreur réseau envoi Brevo:', err)
    errorMsg.value = 'Erreur de connexion au serveur.'
  } finally {
    sending.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>
