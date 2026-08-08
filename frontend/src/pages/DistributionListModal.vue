<template>
  <div v-if="isOpen" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-5">
      
      <!-- Titre -->
      <h2 class="text-xl font-bold text-slate-900">
        {{ isEditing ? 'Éditer la liste' : 'Créer une liste' }}
      </h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Nom de la liste -->
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nom de la liste *</label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="ex: Feuille de route Feder"
            class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        <!-- Description -->
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Description</label>
          <textarea
            v-model="form.description"
            rows="2"
            placeholder="ex: Destinataires officiels"
            class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500"
          ></textarea>
        </div>

        <!-- Sélection des membres + Ajout rapide -->
        <div>
          <div class="flex justify-between items-center mb-2">
            <label class="block text-xs font-bold text-slate-500 uppercase">Sélectionner les membres</label>
            
            <!-- Bouton bascule -->
            <button
              type="button"
              @click="showNewContactForm = !showNewContactForm"
              class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
            >
              <span>{{ showNewContactForm ? '✖ Masquer' : '➕ Ajouter un contact' }}</span>
            </button>
          </div>

          <!-- FORMULAIRE RAPIDE : NOUVEAU CONTACT -->
          <div v-if="showNewContactForm" class="p-3.5 bg-emerald-50/60 border border-emerald-200 rounded-xl mb-3 space-y-2.5">
            <p class="text-xs font-bold text-emerald-800">Ajouter directement un nouveau contact :</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <!-- Nom -->
              <input
                v-model="newContact.name"
                type="text"
                placeholder="Nom complet *"
                class="border border-emerald-200 rounded-lg p-2 text-xs bg-white text-slate-900 outline-none focus:ring-1 focus:ring-emerald-500"
              />
              <!-- Email -->
              <input
                v-model="newContact.email"
                type="email"
                placeholder="Adresse email *"
                class="border border-emerald-200 rounded-lg p-2 text-xs bg-white text-slate-900 outline-none focus:ring-1 focus:ring-emerald-500"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <!-- Téléphone -->
              <input
                v-model="newContact.phone"
                type="tel"
                placeholder="Téléphone (ex: +33 6 12...)"
                class="border border-emerald-200 rounded-lg p-2 text-xs bg-white text-slate-900 outline-none focus:ring-1 focus:ring-emerald-500"
              />

              <!-- Rôle avec liste déroulante d'icônes -->
              <select
                v-model="newContact.role"
                class="border border-emerald-200 rounded-lg p-2 text-xs bg-white text-slate-900 outline-none focus:ring-1 focus:ring-emerald-500"
              >
                <option v-for="roleOption in ROLE_OPTIONS" :key="roleOption.value" :value="roleOption.value">
                  {{ roleOption.icon }} {{ roleOption.label }}
                </option>
              </select>
            </div>

            <div class="flex justify-end pt-1">
              <button
                type="button"
                @click="createNewContact"
                :disabled="!newContact.name || !newContact.email || isSubmittingContact"
                class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-2 rounded-lg transition-colors shadow-sm"
              >
                {{ isSubmittingContact ? 'Création...' : 'Créer & Sélectionner' }}
              </button>
            </div>
          </div>

          <!-- Liste scrollable des membres avec cases à cocher -->
          <div class="border border-slate-200 rounded-xl divide-y divide-slate-100 max-h-52 overflow-y-auto">
            <div
              v-for="member in availableMembers"
              :key="member.id"
              class="flex items-center justify-between p-3 hover:bg-slate-50 transition-colors"
            >
              <label class="flex items-center gap-3 cursor-pointer w-full min-w-0">
                <input
                  type="checkbox"
                  :value="member.id"
                  v-model="form.selectedMemberIds"
                  class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 flex-shrink-0"
                />
                <div class="truncate min-w-0">
                  <p class="text-sm font-semibold text-slate-800 truncate">{{ member.name }}</p>
                  <div class="flex items-center gap-2 text-xs text-slate-400 truncate">
                    <span class="truncate">{{ member.email }}</span>
                    <span v-if="member.phone" class="font-mono text-[11px] text-slate-500">📞 {{ member.phone }}</span>
                  </div>
                </div>
              </label>
              
              <span class="text-xs font-medium px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 ml-2 flex-shrink-0 whitespace-nowrap border border-slate-200">
                {{ getRoleIcon(member.role) }} {{ getRoleLabel(member.role) }}
              </span>
            </div>

            <div v-if="availableMembers.length === 0" class="p-4 text-center text-xs text-slate-400 italic">
              Aucun membre disponible.
            </div>
          </div>
        </div>

        <!-- Actions modale -->
        <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-medium transition-colors"
          >
            Annuler
          </button>
          <button
            type="submit"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-medium transition-colors shadow-sm"
          >
            {{ isEditing ? 'Mettre à jour' : 'Créer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'

// Liste prédéfinie des rôles avec leurs icônes
const ROLE_OPTIONS = [
  { value: 'artist', label: 'Artiste / Groupe', icon: '🎤' },
  { value: 'tm', label: 'Tour Manager', icon: '🎩' },
  { value: 'pm', label: 'Production Manager', icon: '📋' },
  { value: 'sound_tech', label: 'Ingénieur Son', icon: '🎛️' },
  { value: 'light_tech', label: 'Ingénieur Lumière', icon: '💡' },
  { value: 'backliner', label: 'Backliner', icon: '🎸' },
  { value: 'driver', label: 'Chauffeur / Bus', icon: '🚐' },
  { value: 'manager', label: 'Manager', icon: '💼' },
  { value: 'label', label: 'Maison de disque / Label', icon: '💿' },
  { value: 'guest', label: 'Invité / VIP', icon: '⭐' },
  { value: 'other', label: 'Autre contact', icon: '👤' }
]

const props = defineProps<{
  isOpen: boolean
  isEditing: boolean
  listData?: any
  availableMembers: Array<{ id: number; name: string; email: string; phone?: string; role?: string }>
  apiUrl: string
}>()

const emit = defineEmits(['close', 'saved', 'member-added'])

const showNewContactForm = ref(false)
const isSubmittingContact = ref(false)

const form = reactive({
  name: '',
  description: '',
  selectedMemberIds: [] as number[]
})

const newContact = reactive({
  name: '',
  email: '',
  phone: '',
  role: 'artist'
})

function getRoleIcon(roleValue?: string): string {
  if (!roleValue) return '👤'
  const match = ROLE_OPTIONS.find(r => r.value === roleValue.toLowerCase() || r.label.toLowerCase() === roleValue.toLowerCase())
  return match ? match.icon : '👤'
}

function getRoleLabel(roleValue?: string): string {
  if (!roleValue) return 'Contact'
  const match = ROLE_OPTIONS.find(r => r.value === roleValue.toLowerCase())
  return match ? match.label : roleValue
}

// Préremplissage lors de l'édition
watch(() => props.listData, (val) => {
  if (val) {
    form.name = val.name || ''
    form.description = val.description || ''
    form.selectedMemberIds = (val.contacts || val.members || []).map((c: any) => c.id)
  }
}, { immediate: true })

// Création du contact via l'API
async function createNewContact() {
  if (!newContact.name || !newContact.email) return

  isSubmittingContact.value = true

  try {
    const payload = {
      name: newContact.name,
      email: newContact.email,
      phone: newContact.phone,
      role: newContact.role
    }

    const res = await fetch(`${props.apiUrl}/api/contacts`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })

    if (res.ok) {
      const created = await res.json()
      
      // 1. Notifier la page parente
      emit('member-added', created)

      // 2. Cocher automatiquement le nouveau contact
      if (created.id) {
        form.selectedMemberIds.push(created.id)
      }

      // 3. Réinitialiser le formulaire rapide
      newContact.name = ''
      newContact.email = ''
      newContact.phone = ''
      newContact.role = 'artist'
      showNewContactForm.value = false
    } else {
      const err = await res.json().catch(() => ({}))
      alert(`Erreur : ${err.message || err.error || 'Impossible de créer le contact'}`)
    }
  } catch (e) {
    console.error("Erreur serveur :", e)
    alert("Erreur de communication avec le serveur.")
  } finally {
    isSubmittingContact.value = false
  }
}

function handleSubmit() {
  emit('saved', {
    name: form.name,
    description: form.description,
    contact_ids: form.selectedMemberIds
  })
}
</script>
