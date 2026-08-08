<template>
  <div v-if="isOpen" class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5">
      <div class="flex justify-between items-center border-b pb-3">
        <h3 class="text-lg font-bold text-slate-800">Envoyer le PDF par Email (Brevo)</h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
      </div>

      <form @submit.prevent="handleSend" class="space-y-4">
        <!-- Sélection de la liste -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Choisir la liste de diffusion</label>
          <select 
            v-model="selectedListId" 
            required 
            class="w-full px-3 py-2 border rounded-lg text-sm bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
          >
            <option value="" disabled>-- Sélectionner une liste --</option>
            <option v-for="list in distributionLists" :key="list.id" :value="list.id">
              {{ list.name }} ({{ list.contacts_count || 0 }} destinataires)
            </option>
          </select>
        </div>

        <!-- Objet -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Objet de l'email</label>
          <input 
            v-model="subject" 
            type="text" 
            required 
            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
          />
        </div>

        <!-- Message -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Message personnalisé</label>
          <textarea 
            v-model="message" 
            rows="3" 
            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
          ></textarea>
        </div>

        <div v-if="statusMessage" :class="['p-3 text-xs rounded-lg', isError ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600']">
          {{ statusMessage }}
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-2">
          <button 
            type="button" 
            @click="$emit('close')" 
            class="px-4 py-2 border text-slate-600 rounded-lg text-sm hover:bg-slate-50"
          >
            Annuler
          </button>
          <button 
            type="submit" 
            :disabled="sending"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <span v-if="sending" class="animate-spin">🌀</span>
            <span>{{ sending ? 'Envoi en cours...' : 'Envoyer via Brevo' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

const props = defineProps<{
  isOpen: boolean
  pdfPath: string
}>()

const emit = defineEmits(['close', 'sent'])

const distributionLists = ref<any[]>([])
const selectedListId = ref('')
const subject = ref('Roadline MGT - Feuille de Route / Documentation')
const message = ref('Bonjour,\n\nVeuillez trouver en pièce jointe le document au format PDF.')
const sending = ref(false)
const statusMessage = ref('')
const isError = ref(false)

onMounted(async () => {
  try {
    const res = await fetch('/api/distribution-lists')
    if (res.ok) {
      distributionLists.value = await res.json()
    }
  } catch (err) {
    console.error('Erreur de récupération des listes', err)
  }
})

// 2. Dans handleSend :
const handleSend = async () => {
  sending.value = true
  statusMessage.value = ''
  isError.value = false

  try {
    const res = await fetch('/api/send-pdf', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        list_id: selectedListId.value,
        subject: subject.value,
        message: message.value,
        pdf_path: props.pdfPath
      })
    })

    const data = await res.json()

    if (res.ok && data.success) {
      statusMessage.value = 'Email envoyé avec succès à la liste sélectionnée !'
      setTimeout(() => {
        emit('sent')
        emit('close')
      }, 1500)
    } else {
      isError.value = true
      statusMessage.value = data.error || 'Une erreur est survenue lors de l\'envoi Brevo.'
    }
  } catch (err) {
    isError.value = true
    statusMessage.value = 'Impossible de contacter le serveur backend.'
  } finally {
    sending.value = false
  }
}

</script>
