<template>
  <div class="p-6 max-w-6xl mx-auto">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Listes de Distribution Email</h1>
        <p class="text-sm text-gray-600">Gérez les destinataires automatisés pour vos envois Brevo</p>
      </div>
      <button
        @click="openModal()"
        class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition font-medium"
      >
        + Nouvelle Liste
      </button>
    </div>

    <!-- État de chargement / Erreur -->
    <div v-if="loading" class="p-4 text-center text-gray-500">
      Chargement des listes de distribution...
    </div>

    <div v-else-if="error" class="p-4 text-red-600">
      Erreur : {{ error }}
    </div>

    <!-- Grille des Listes -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- DIAGNOSTIC GLOBAL SI VIDE -->
      <div v-if="lists.length === 0" class="col-span-3 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
        <p class="font-bold">Attention : Le tableau "lists" est vide !</p>
        <p class="text-xs mt-1">Données reçues de l'API : {{ lists }}</p>
      </div>

      <div
        v-for="list in lists"
        :key="list.id"
        class="border border-gray-200 rounded-xl p-5 shadow-sm bg-white flex flex-col justify-between"
      >
        <div>
          <!-- DIAGNOSTIC PAR CARTE -->
          <div class="text-[10px] bg-yellow-100 text-yellow-900 p-1 mb-2 rounded font-mono">
            KEYS: {{ Object.keys(list) }}
          </div>

          <div class="flex justify-between items-start mb-2">
            <h2 class="font-bold text-lg text-gray-900">{{ list.name }}</h2>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-medium px-2.5 py-1 rounded-full">
              {{ list.contacts_count || 0 }} contacts
            </span>
          </div>
          <p class="text-sm text-gray-600 mb-2">{{ list.description || 'Aucune description' }}</p>

          <!-- BLOC DES DATES -->
          <div class="text-[11px] text-gray-400 mb-4 space-y-0.5">
            <p>Créée le : <span class="font-medium text-gray-500">{{ list.created_at }}</span></p>
            <p v-if="list.updated_at">
              Modifiée le : <span class="font-medium text-gray-500">{{ list.updated_at }}</span>
            </p>
          </div>

          <!-- Aperçu des membres -->
          <div class="text-xs text-gray-500 max-h-28 overflow-y-auto pt-1 space-y-1">
            <p class="font-semibold text-gray-400 uppercase tracking-wider text-[10px] mb-1">Membres rattachés</p>
            <template v-if="list.contacts && list.contacts.length > 0">
              <div v-for="(c, index) in list.contacts" :key="index" class="flex justify-between items-center">
                <span>{{ c.name }} ({{ c.email }})</span>
                <span class="text-[10px] text-gray-400 font-mono uppercase">[{{ c.source || c.type }}]</span>
              </div>
            </template>
            <span v-else class="italic text-gray-400">Aucun membre sélectionné</span>
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-4 pt-3 border-t">
          <button
            @click="openModal(list)"
            class="text-sm px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition"
          >
            Éditer
          </button>
          <button
            @click="deleteList(list.id)"
            class="text-sm px-3 py-1 bg-red-50 text-red-600 rounded hover:bg-red-100 transition"
          >
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de Création / Modification -->
    <div v-if="isModalOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="p-5 border-b flex justify-between items-center">
          <h2 class="text-lg font-bold text-gray-800">
            {{ editingListId ? 'Modifier la liste' : 'Créer une liste' }}
          </h2>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 font-bold">✕</button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-5 overflow-y-auto space-y-4 flex-1">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la liste *</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
              placeholder="ex: Équipe Technique"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              v-model="form.description"
              rows="2"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
              placeholder="Optionnel"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Sélectionner les membres ({{ selectedMembers.length }} sélectionné(s))
            </label>

            <div class="border rounded-lg max-h-60 overflow-y-auto divide-y divide-gray-100">
              <p v-if="availableMembers.length === 0" class="p-3 text-sm text-gray-500 italic">
                Aucun contact ou utilisateur disponible.
              </p>

              <label
                v-else
                v-for="m in availableMembers"
                :key="`${getMemberType(m)}-${m.id}`"
                class="flex items-center justify-between p-3 hover:bg-gray-50 cursor-pointer text-sm"
              >
                <div class="flex items-center space-x-3">
                  <input
                    type="checkbox"
                    :checked="isMemberSelected(m)"
                    @change="toggleMember(m)"
                    class="rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4"
                  />
                  <div>
                    <p class="font-medium text-gray-800">{{ m.name }}</p>
                    <p class="text-xs text-gray-500">{{ m.email }}</p>
                  </div>
                </div>
                <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600 uppercase font-mono">
                  {{ getMemberType(m) }}
                </span>
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 border text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition"
            >
              Annuler
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700 transition font-medium"
            >
              {{ editingListId ? 'Mettre à jour' : 'Créer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

const lists = ref([])
const availableMembers = ref([])
const loading = ref(true)
const error = ref(null)

const isModalOpen = ref(false)
const editingListId = ref(null)

const form = reactive({
  name: '',
  description: ''
})

const selectedMembers = ref([])

const getMemberType = (member) => {
  const src = member.source || member.type || 'contact'
  return src === 'user' ? 'user' : 'contact'
}

const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const [resLists, resMembers] = await Promise.all([
      fetch('/api/distribution-lists'),
      fetch('/api/members')
    ])

    if (!resLists.ok || !resMembers.ok) {
      throw new Error('Erreur lors du chargement des données.')
    }

    lists.value = await resLists.json()
    availableMembers.value = await resMembers.json()
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})

const openModal = (listToEdit = null) => {
  if (listToEdit) {
    editingListId.value = listToEdit.id
    form.name = listToEdit.name || ''
    form.description = listToEdit.description || ''

    selectedMembers.value = (listToEdit.contacts || []).map((c) => ({
      id: Number(c.id),
      type: getMemberType(c)
    }))
  } else {
    editingListId.value = null
    form.name = ''
    form.description = ''
    selectedMembers.value = []
  }
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  editingListId.value = null
}

const isMemberSelected = (member) => {
  const type = getMemberType(member)
  const memberId = Number(member.id)
  return selectedMembers.value.some((sm) => Number(sm.id) === memberId && sm.type === type)
}

const toggleMember = (member) => {
  const type = getMemberType(member)
  const memberId = Number(member.id)
  const exists = isMemberSelected(member)

  if (exists) {
    selectedMembers.value = selectedMembers.value.filter(
      (sm) => !(Number(sm.id) === memberId && sm.type === type)
    )
  } else {
    selectedMembers.value.push({ id: memberId, type })
  }
}

const handleSubmit = async () => {
  if (!form.name.trim()) {
    alert('Le nom de la liste est obligatoire.')
    return
  }

  const payload = {
    name: form.name,
    description: form.description,
    contacts: selectedMembers.value
  }

  try {
    const url = editingListId.value
      ? `/api/distribution-lists/${editingListId.value}`
      : '/api/distribution-lists'
    const method = editingListId.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })

    const result = await response.json()

    if (response.ok && result.success) {
      closeModal()
      fetchData()
    } else {
      alert(result.message || result.error || 'Une erreur est survenue.')
    }
  } catch (err) {
    alert('Erreur réseau lors de l\'enregistrement.')
  }
}

const deleteList = async (id) => {
  if (!confirm('Voulez-vous vraiment supprimer cette liste de distribution ?')) {
    return
  }

  try {
    const response = await fetch(`/api/distribution-lists/${id}`, {
      method: 'DELETE'
    })
    const result = await response.json()

    if (response.ok && result.success) {
        fetchData()
    } else {
      alert(result.message || result.error || 'Erreur lors de la suppression.')
    }
  } catch (err) {
    alert('Erreur réseau lors de la suppression.')
  }
}
</script>
