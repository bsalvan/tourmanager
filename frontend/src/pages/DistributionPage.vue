<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Listes de Distribution Email</h1>
        <p class="text-slate-500 text-sm">Gérez les destinataires automatisés pour vos envois Brevo</p>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3">
        <!-- Bouton Annuaire Global -->
        <button
          @click="showGlobalMembersModal = true"
          class="bg-slate-700 hover:bg-slate-800 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition shadow-sm"
        >
          <span>👥 Voir tous les membres & contacts</span>
        </button>

        <!-- Bouton Nouvelle Liste -->
        <button
          @click="openCreateModal"
          class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition shadow-sm"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          Nouvelle liste
        </button>
      </div>
    </div>

    <!-- Message de confirmation -->
    <div v-if="successMsg" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-center justify-between shadow-sm">
      <span class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        {{ successMsg }}
      </span>
      <button @click="successMsg = ''" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
    </div>

    <!-- Chargement -->
    <div v-if="loading" class="text-center py-12 text-slate-500">
      Chargement des listes de distribution...
    </div>

    <!-- Grille des listes -->
    <div v-else-if="lists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="list in lists"
        :key="list.id"
        class="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition p-6 flex flex-col justify-between"
      >
        <div>
          <div class="flex justify-between items-start mb-2 gap-2">
            <h2 class="text-lg font-bold text-slate-800 leading-tight">{{ list.name }}</h2>

            <div class="flex items-center gap-1.5">
              <span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-emerald-200 whitespace-nowrap">
                {{ getContactCount(list) }} contacts
              </span>

              <!-- Bouton Éditer la liste -->
              <button
                @click="openEditModal(list)"
                title="Éditer la liste"
                class="p-1 text-slate-400 hover:text-blue-600 rounded transition"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>

              <!-- Bouton Supprimer la liste -->
              <button
                @click="confirmDelete(list)"
                title="Supprimer la liste"
                class="p-1 text-slate-400 hover:text-red-600 rounded transition"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>

          <p class="text-slate-500 text-sm mb-2">{{ list.description || 'Aucune description' }}</p>

          <!-- BLOC DES DATES -->
          <div v-if="list.created_at || list.updated_at" class="text-[11px] text-slate-400 mb-3 space-y-0.5">
            <p v-if="list.created_at">Créée le : <span class="font-medium text-slate-500">{{ list.created_at }}</span></p>
            <p v-if="list.updated_at">Modifiée le : <span class="font-medium text-slate-500">{{ list.updated_at }}</span></p>
          </div>

          <hr class="border-slate-100 my-3" />

          <!-- Membres rattachés -->
          <div>
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-2">Membres rattachés</span>

            <div v-if="getContactsList(list).length > 0" class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
              <div
                v-for="contact in getContactsList(list)"
                :key="`${contact.source || 'c'}-${contact.id || contact.email}`"
                class="text-xs text-slate-700 bg-slate-50 p-2 rounded flex justify-between items-center group"
              >
                <div class="flex items-center space-x-2 min-w-0">
                  <span class="text-slate-400 flex-shrink-0">👤</span>
                  <span class="font-medium text-slate-800 truncate">
                    {{ contact.name }}
                  </span>
                  <span
                    v-if="contact.role"
                    class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 font-semibold border border-blue-100 flex-shrink-0"
                  >
                    {{ contact.role }}
                  </span>
                </div>

                <div class="flex items-center space-x-2 flex-shrink-0">
                  <span class="text-slate-400 text-[10px] font-mono truncate hidden sm:inline">{{ contact.email }}</span>
                  <!-- Éditer directement le membre -->
                  <button
                    @click="openMemberEditModal(contact)"
                    title="Modifier ce membre"
                    class="opacity-60 group-hover:opacity-100 text-slate-400 hover:text-emerald-600 text-xs transition"
                  >
                    ✏️
                  </button>
                </div>
              </div>
            </div>
            <p v-else class="text-xs text-slate-400 italic py-1">Aucun membre sélectionné</p>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 bg-white rounded-xl border border-slate-200 p-8">
      <p class="text-slate-500 mb-4">Aucune liste de distribution configurée.</p>
      <button @click="openCreateModal" class="text-emerald-600 hover:underline font-medium text-sm">Créer la première liste</button>
    </div>

    <!-- MODALE DISTRIBUTION LIST -->
    <DistributionListModal
      :is-open="showModal"
      :is-editing="isEditing"
      :list-data="editingList"
      :available-members="availableUsers"
      :api-url="API_BASE_URL"
      @close="closeModal"
      @saved="saveList"
      @member-added="handleMemberAdded"
    />

    <!-- MODALE ANNUAIRE GLOBAL DES MEMBRES -->
    <GlobalMembersModal
      v-if="showGlobalMembersModal"
      :api-url="API_BASE_URL"
      @close="showGlobalMembersModal = false"
      @edit-member="openMemberEditModal"
    />

    <!-- MODALE ÉDITION D'UN MEMBRE / CONTACT -->
    <EditMemberModal
      v-if="selectedMemberToEdit"
      :member="selectedMemberToEdit"
      :api-url="API_BASE_URL"
      @close="selectedMemberToEdit = null"
      @updated="onMemberUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DistributionListModal from './DistributionListModal.vue'
import GlobalMembersModal from '../components/GlobalMembersModal.vue'
import EditMemberModal from '../components/EditMemberModal.vue'

//const API_BASE_URL = `${window.location.protocol}//${window.location.hostname}:8000`
const API_BASE_URL = ''

interface Contact {
  id: number
  name: string
  first_name?: string
  last_name?: string
  email: string
  phone?: string
  role?: string
  source?: string
}

interface DistributionList {
  id: number
  name: string
  description?: string
  contacts_count?: number
  contacts?: Contact[]
  created_at?: string
  updated_at?: string
}

const lists = ref<DistributionList[]>([])
const availableUsers = ref<Contact[]>([])
const loading = ref(true)

// Gestion modale Liste de distribution
const showModal = ref(false)
const isEditing = ref(false)
const editingListId = ref<number | null>(null)
const editingList = ref<DistributionList | null>(null)

// Gestion modales Membres / Annuaire
const showGlobalMembersModal = ref(false)
const selectedMemberToEdit = ref<Contact | null>(null)

const successMsg = ref('')

function formatContact(c: any): Contact {
  return {
    id: c.id,
    name: c.name || `${c.first_name || ''} ${c.last_name || ''}`.trim() || 'Contact sans nom',
    first_name: c.first_name,
    last_name: c.last_name,
    email: c.email || 'Pas d\'email',
    phone: c.phone,
    role: c.role || '',
    source: c.source || 'contact'
  }
}

function getContactsList(list: DistributionList): Contact[] {
  if (!list.contacts) return []
  return list.contacts.map(formatContact)
}

function getContactCount(list: DistributionList): number {
  if (typeof list.contacts_count === 'number') return list.contacts_count
  return list.contacts ? list.contacts.length : 0
}

async function fetchData() {
  loading.value = true
  try {
    const res = await fetch(`${API_BASE_URL}/api/distribution-lists`)
    if (res.ok) {
      lists.value = await res.json()
    }
  } catch (err) {
    console.error('Erreur chargement des listes', err)
  } finally {
    loading.value = false
  }
}

async function loadUsers() {
  try {
    const res = await fetch(`${API_BASE_URL}/api/contacts`)
    if (res.ok) {
      const data = await res.json()
      if (Array.isArray(data)) {
        availableUsers.value = data.map(formatContact)
      }
    }
  } catch (err) {
    console.error('Erreur chargement contacts', err)
  }
}

function openCreateModal() {
  isEditing.value = false
  editingListId.value = null
  editingList.value = null
  showModal.value = true
  loadUsers()
}

function openEditModal(list: DistributionList) {
  isEditing.value = true
  editingListId.value = list.id
  editingList.value = list
  showModal.value = true
  loadUsers()
}

function openMemberEditModal(member: Contact) {
  selectedMemberToEdit.value = member
}

function onMemberUpdated() {
  selectedMemberToEdit.value = null
  successMsg.value = "Membre mis à jour avec succès !"
  setTimeout(() => { successMsg.value = '' }, 4000)
  fetchData()
}

function closeModal() {
  showModal.value = false
  editingList.value = null
}

function handleMemberAdded(newMember: Contact) {
  availableUsers.value.push(formatContact(newMember))
}

async function saveList(payload: { name: string; description: string; contact_ids: number[] }) {
  const url = isEditing.value && editingListId.value
    ? `${API_BASE_URL}/api/distribution-lists/${editingListId.value}`
    : `${API_BASE_URL}/api/distribution-lists`

  const method = isEditing.value ? 'PUT' : 'POST'

  try {
    const res = await fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })

    const data = await res.json()

    if (res.ok && data.success !== false) {
      successMsg.value = isEditing.value
        ? `La liste "${payload.name}" a été mise à jour avec succès.`
        : `La liste "${payload.name}" a été créée avec succès !`

      setTimeout(() => { successMsg.value = '' }, 4000)

      closeModal()
      await fetchData()
    } else {
      const errorDetail = data.error || data.message || `Code HTTP ${res.status}`
      alert(`Erreur lors de la sauvegarde : ${errorDetail}`)
    }
  } catch (err) {
    console.error('Erreur sauvegarde:', err)
    alert('Erreur de connexion au serveur lors de l\'enregistrement.')
  }
}

async function confirmDelete(list: DistributionList) {
  if (!confirm(`Voulez-vous vraiment supprimer la liste "${list.name}" ?`)) return

  try {
    let res = await fetch(`${API_BASE_URL}/api/distribution-lists?id=${list.id}`, {
      method: 'DELETE'
    })

    if (res.status === 404) {
      res = await fetch(`${API_BASE_URL}/api/distribution-lists/${list.id}`, {
        method: 'DELETE'
      })
    }

    if (res.ok) {
      successMsg.value = `La liste "${list.name}" a été supprimée.`
      setTimeout(() => { successMsg.value = '' }, 4000)
      fetchData()
    } else {
      alert('Erreur lors de la suppression (Code HTTP: ' + res.status + ').')
    }
  } catch (err) {
    console.error('Erreur suppression', err)
    alert('Erreur de connexion lors de la suppression.')
  }
}

onMounted(() => {
  fetchData()
})
</script>
