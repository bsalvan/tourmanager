<template>
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
      
      <!-- En-tête -->
      <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
        <h3 class="text-md font-bold text-slate-800">
          Modifier : {{ member.name || `${form.first_name} ${form.last_name}` }}
        </h3>
        <button 
          @click="$emit('close')" 
          class="text-slate-400 hover:text-slate-600 text-2xl font-bold leading-none"
        >
          &times;
        </button>
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="saveMember" class="p-4 space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Prénom</label>
          <input 
            v-model="form.first_name" 
            type="text" 
            required
            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nom</label>
          <input 
            v-model="form.last_name" 
            type="text" 
            required
            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">E-mail</label>
          <input 
            v-model="form.email" 
            type="email" 
            required
            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Téléphone</label>
          <input 
            v-model="form.phone" 
            type="text" 
            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Rôle / Fonction</label>
          <input 
            v-model="form.role" 
            type="text" 
            placeholder="Ex: Technicien, Régisseur..."
            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        <!-- Pied de page avec actions -->
        <div class="pt-4 border-t border-slate-100 flex justify-end space-x-2">
          <button 
            type="button" 
            @click="$emit('close')"
            class="px-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition"
          >
            Annuler
          </button>
          <button 
            type="submit" 
            :disabled="saving"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition flex items-center"
          >
            <span v-if="saving">Enregistrement...</span>
            <span v-else>Enregistrer</span>
          </button>
        </div>
      </form>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

const props = defineProps<{
  member: any
  apiUrl: string
}>()

const emit = defineEmits(['close', 'updated'])

const saving = ref(false)

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: ''
})

onMounted(() => {
  if (props.member) {
    // Séparation du nom s'il est au format complet
    const nameParts = (props.member.name || '').split(' ')
    form.first_name = props.member.first_name || nameParts[0] || ''
    form.last_name = props.member.last_name || nameParts.slice(1).join(' ') || ''
    form.email = props.member.email || ''
    form.phone = props.member.phone || ''
    form.role = props.member.role || ''
  }
})

async function saveMember() {
  saving.value = true
  try {
    const endpoint = props.member.source === 'user'
      ? `${props.apiUrl}/api/users/${props.member.id}`
      : `${props.apiUrl}/api/contacts/${props.member.id}`

    const res = await fetch(endpoint, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ...form,
        name: `${form.first_name} ${form.last_name}`.trim()
      })
    })

    if (res.ok) {
      emit('updated')
    } else {
      const err = await res.json()
      alert(`Erreur : ${err.message || 'Mise à jour impossible'}`)
    }
  } catch (e) {
    console.error('Erreur sauvegarde membre:', e)
    alert('Erreur lors de la connexion au serveur.')
  } finally {
    saving.value = false
  }
}
</script>
