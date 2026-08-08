<template>
  <div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- En-tête -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Paramètres & Configuration</h1>
        <p class="text-sm text-slate-500">Gérez les informations de votre structure et les accès utilisateurs.</p>
      </div>
    </div>

    <!-- Onglets de navigation -->
    <div class="w-full overflow-x-auto pb-2 border-b border-slate-200">
      <nav class="-mb-px flex min-w-max space-x-6 md:space-x-8">
        <button
          @click="activeTab = 'company'"
          :class="[
            activeTab === 'company'
              ? 'border-emerald-500 text-emerald-600 font-semibold'
              : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
          ]"
        >
          Informations Agence / Société
        </button>
        <button
          @click="activeTab = 'users'"
          :class="[
            activeTab === 'users'
              ? 'border-emerald-500 text-emerald-600 font-semibold'
              : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
          ]"
        >
          Gestion de l'Équipe & Utilisateurs
        </button>
      </nav>
    </div>

    <!-- Feedback Message -->
    <div v-if="notification.message" :class="[
      'p-4 rounded-lg text-sm flex items-center justify-between',
      notification.type === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'
    ]">
      <span>{{ notification.message }}</span>
      <button @click="notification.message = ''" class="font-bold">&times;</button>
    </div>

    <!-- TAB 1 : PARAMÈTRES SOCIÉTÉ -->
    <div v-if="activeTab === 'company'" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-6">
      <h2 class="text-lg font-semibold text-slate-800">Profil de l'Agence</h2>

      <form @submit.prevent="saveCompanySettings" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nom de la société / agence *</label>
            <input
              v-model="company.company_name"
              type="text"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email de contact</label>
            <input
              v-model="company.email"
              type="email"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Téléphone</label>
            <input
              v-model="company.phone"
              type="text"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Adresse</label>
            <input
              v-model="company.address"
              type="text"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Pied de page personnalisé pour les PDF</label>
          <input
            v-model="company.footer_text"
            type="text"
            placeholder="Ex: Document confidentiel produit par Roadline MGT — Confidentialité réservée"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
          />
          <p class="text-xs text-slate-400 mt-1">Ce texte remplacera la mention confidentielle en bas de vos Roadbooks PDF.</p>
        </div>

        <div class="flex justify-end pt-4">
          <button
            type="submit"
            :disabled="loading"
            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm disabled:opacity-50"
          >
            Enregistrer les modifications
          </button>
        </div>
      </form>
    </div>

    <!-- TAB 2 : UTILISATEURS -->
    <div v-if="activeTab === 'users'" class="space-y-6">
      <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold text-slate-800">Membres de l'équipe</h2>
        <button
          @click="openUserModal()"
          class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm"
        >
          + Ajouter un membre
        </button>
      </div>

      <!-- Table des utilisateurs -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[650px]">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <th class="py-3 px-4">Nom</th>
                <th class="py-3 px-4">Email</th>
                <th class="py-3 px-4">Rôle / Profil</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/50">
                <td class="py-3 px-4 font-medium text-slate-800">{{ user.name }}</td>
                <td class="py-3 px-4 text-slate-600">{{ user.email }}</td>
                <td class="py-3 px-4">
                  <span :class="getRoleBadgeClass(user.role)" class="px-2.5 py-1 text-xs font-semibold rounded-full">
                    {{ getRoleLabel(user.role) }}
                  </span>
                </td>
                <td class="py-3 px-4 text-right space-x-2">
                  <button
                    @click="openUserModal(user)"
                    class="text-slate-600 hover:text-emerald-600 text-xs font-semibold"
                  >
                    Modifier
                  </button>
                  <button
                    @click="deleteUser(user.id)"
                    class="text-red-500 hover:text-red-700 text-xs font-semibold"
                  >
                    Supprimer
                  </button>
                </td>
              </tr>
              <tr v-if="users.length === 0">
                <td colspan="4" class="py-6 text-center text-slate-400">Aucun utilisateur trouvé.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODALE CRÉATION / ÉDITION UTILISATEUR -->
    <div v-if="showUserModal" class="fixed inset-0 bg-slate-900/50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
        <h3 class="text-lg font-bold text-slate-800">
          {{ editingUser ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' }}
        </h3>

        <form @submit.prevent="saveUser" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nom complet *</label>
            <input
              v-model="userForm.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
            <input
              v-model="userForm.email"
              type="email"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Mot de passe {{ editingUser ? '(Laissez vide pour ne pas modifier)' : '*' }}
            </label>
            <input
              v-model="userForm.password"
              type="password"
              :required="!editingUser"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Rôle / Profil *</label>
            <select
              v-model="userForm.role"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            >
              <option value="admin">Administrateur / Agence</option>
              <option value="tm">Tour Manager (TM)</option>
              <option value="artist">Artiste / DJ</option>
              <option value="crew">Technicien / Crew</option>
            </select>
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showUserModal = false"
              class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm font-medium"
            >
              Annuler
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm"
            >
              Enregistrer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const API_BASE = '/api'

const activeTab = ref('company')
const loading = ref(false)
const notification = ref({ message: '', type: 'success' })

const company = ref({
  company_name: '',
  email: '',
  phone: '',
  address: '',
  footer_text: ''
})

const users = ref([])
const showUserModal = ref(false)
const editingUser = ref(null)

const userForm = ref({
  name: '',
  email: '',
  password: '',
  role: 'tm'
})

const notify = (message, type = 'success') => {
  notification.value = { message, type }
  setTimeout(() => {
    notification.value.message = ''
  }, 4000)
}

const fetchCompanySettings = async () => {
  try {
    const res = await fetch(`${API_BASE}/settings`)
    if (res.ok) {
      company.value = await res.json()
    }
  } catch (err) {
    console.error('Erreur chargement paramètres:', err)
  }
}

const saveCompanySettings = async () => {
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/settings`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(company.value)
    })
    if (res.ok) {
      notify('Paramètres de l\'agence mis à jour avec succès.')
    } else {
      notify('Erreur lors de la mise à jour.', 'error')
    }
  } catch (err) {
    notify('Erreur réseau.', 'error')
  } finally {
    loading.value = false
  }
}

const fetchUsers = async () => {
  try {
    const res = await fetch(`${API_BASE}/users`)
    if (res.ok) {
      users.value = await res.json()
    }
  } catch (err) {
    console.error('Erreur chargement utilisateurs:', err)
  }
}

const openUserModal = (user = null) => {
  editingUser.value = user
  if (user) {
    userForm.value = { name: user.name, email: user.email, password: '', role: user.role }
  } else {
    userForm.value = { name: '', email: '', password: '', role: 'tm' }
  }
  showUserModal.value = true
}

const saveUser = async () => {
  const url = editingUser.value
    ? `${API_BASE}/users/${editingUser.value.id}`
    : `${API_BASE}/users`

  const method = editingUser.value ? 'PUT' : 'POST'

  try {
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(userForm.value)
    })

    if (res.ok) {
      notify(editingUser.value ? 'Utilisateur mis à jour.' : 'Utilisateur créé.')
      showUserModal.value = false
      fetchUsers()
    } else {
      notify('Une erreur est survenue.', 'error')
    }
  } catch (err) {
    notify('Erreur réseau.', 'error')
  }
}

const deleteUser = async (id) => {
  if (!confirm('Voulez-vous vraiment supprimer cet utilisateur ?')) return

  try {
    const res = await fetch(`${API_BASE}/users/${id}`, { method: 'DELETE' })
    if (res.ok) {
      notify('Utilisateur supprimé.')
      fetchUsers()
    }
  } catch (err) {
    notify('Erreur lors de la suppression.', 'error')
  }
}

const getRoleLabel = (role) => {
  const roles = {
    admin: 'Administrateur',
    tm: 'Tour Manager',
    artist: 'Artiste / DJ',
    crew: 'Technicien / Crew'
  }
  return roles[role] || role
}

const getRoleBadgeClass = (role) => {
  const classes = {
    admin: 'bg-purple-100 text-purple-700',
    tm: 'bg-emerald-100 text-emerald-700',
    artist: 'bg-amber-100 text-amber-700',
    crew: 'bg-blue-100 text-blue-700'
  }
  return classes[role] || 'bg-slate-100 text-slate-700'
}

onMounted(() => {
  fetchCompanySettings()
  fetchUsers()
})
</script>
