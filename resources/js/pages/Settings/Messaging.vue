<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps({
  settings: { type: Object, required: true },
  modes: { type: Array, required: true },
})

const form = useForm({ ...props.settings })
</script>

<template>
  <Head title="Réglages de messagerie" />
  <AppLayout>
    <form class="mx-auto max-w-md space-y-4" @submit.prevent="form.put('/settings/messaging')">
      <h1 class="text-xl font-semibold">Messagerie</h1>
      <p class="text-sm text-neutral-500">
        Réglage au niveau du système. La surface publique se limite à un nom et une description :
        vos alters n'y apparaissent jamais.
      </p>

      <label class="block text-sm">
        Mode
        <select v-model="form.mode" class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm">
          <option v-for="mode in modes" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
        </select>
      </label>

      <template v-if="form.mode === 'shared'">
        <label class="block text-sm">
          Nom public du compte
          <input v-model="form.display_name" class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
        </label>

        <label class="block text-sm">
          Description
          <textarea v-model="form.description" rows="3" class="mt-1 w-full resize-none rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
        </label>

        <label class="flex items-center gap-2 text-sm text-neutral-400">
          <input v-model="form.show_message_author" type="checkbox" />
          Afficher l'auteur·e de chaque message
        </label>
        <p class="text-xs text-neutral-600">
          L'avatar affiché devient celui de l'alter qui a écrit. Le titre de la conversation reste
          le nom du compte.
        </p>
      </template>

      <button :disabled="form.processing" class="w-full rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500 disabled:opacity-50">
        Enregistrer
      </button>
    </form>
  </AppLayout>
</template>
