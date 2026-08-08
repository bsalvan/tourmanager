<template>
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[85vh] flex flex-col overflow-hidden">
      
      <!-- En-tête -->
      <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
        <div>
          <h2 class="text-lg font-bold text-slate-800">Annuaire Global des Membres & Contacts</h2>
          <p class="text-xs text-slate-500">Liste unifiée des utilisateurs et contacts externes triés par ordre alphabétique</p>
        </div>
        <button 
          @click="$emit('close')" 
          class="text-slate-400 hover:text-slate-600 text-2xl font-bold leading-none px-2"
        >
          &times;
        </button>
      </div>

      <!-- Recherche & Table -->
      <div class="p-4 flex-1 overflow-y-auto">
        <div class="mb-4">
          <input 
            v-model="search" 
            type="text" 
            placeholder="Rechercher par nom ou e-mail..."
            class="w-full px-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        <div v-if="loading" class="text-center py-12 text-slate-500">
          Chargement de l'annuaire...
        </div>

        <div v-else-if="filteredMembers.length > 0" class="overflow-x-auto border border-slate-200 rounded-lg">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-xs text-slate-500 uppercase bg-slate-50">
                <th class="p-3">Nom</th>
                <th class="p-3">E-mail</th>
                <th class="p-3">Téléphone</th>
                <th class="p-3">Rôle</th>
                <th class="p-3">Statut</th>
                <th class="p-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <tr 
                v-for="member in filteredMembers" 
                :key="`${member.source}-${member.id}`" 
                class="hover:bg-slate-50/80 transition-colors"
              >
                <td class="p-3 font-semibold text-slate-800">
                  {{ member.name || `${member.first_name || ''} ${member.last_name || ''}`.trim() || 'Sans nom' }}
                </td>
                <td class="p-3 text-slate-600 font-mono text-xs">{{ member.email || '-' }}</td>
                <td class="p-3 text-slate-500 text-xs">{{ member.phone || '-' }}</td>
                <td class="p-3 text-slate-500 text-xs">
                  <span v-if="member.role" class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded border border-slate-200">
                    {{ member.role }}
                  </span>
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="p-3">
                  <span 
                    :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                      member.source === 'user' 
                        ? 'bg-blue-100 text-blue-700 border border-blue-200' 
                        : 'bg-purple-100 text-purple-700 border border-purple-200'
                    ]"
                  >
                    {{ member.source === 'user' ? 'User' : 'Contact Externe' }}
                  </span>
                </td>
                <td class="p-3 text-right">
                  <button 
                    @click="$emit('edit-member', member)"
                    class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-md font-medium transition-colors"
                  >
                    ✏️ Modifier
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="text-center py-8 text-slate-400 text-sm">
          Aucun résultat correspondant à votre recherche.
        </div>
      </div>

      <!-- Pied de page -->
      <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end">
        <button 
          @click="$emit('close')"
          class="bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm px-4 py-2 rounded-lg font-medium transition"
        >
          Fermer
        </button>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

const props = defineProps<{
  apiUrl: string
}>()

const emit = defineEmits(['close', 'edit-member'])

const members = ref<any[]>([])
const search = ref('')
const loading = ref(false)

// Tri alphabétique + Filtrage dynamique
const filteredMembers = computed(() => {
  return members.value
    .filter(m => {
      const fullName = (m.name || `${m.first_name || ''} ${m.last_name || ''}`).toLowerCase()
      const email = (m.email || '').toLowerCase()
      const q = search.value.toLowerCase()
      return fullName.includes(q) || email.includes(q)
    })
    .sort((a, b) => {
      const nameA = (a.name || `${a.first_name || ''} ${a.last_name || ''}`).toLowerCase()
      const nameB = (b.name || `${b.first_name || ''} ${b.last_name || ''}`).toLowerCase()
      return nameA.localeCompare(nameB)
    })
})

async function fetchMembers() {
  loading.value = true
  try {
    const res = await fetch(`${props.apiUrl}/api/contacts`)
    if (res.ok) {
      const data = await res.json()
      members.value = Array.isArray(data) ? data : []
    }
  } catch (err) {
    console.error('Erreur chargement membres:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchMembers()
})
</script>
