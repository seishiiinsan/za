<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps({
  alter: { type: Object, default: null },
  privacyLevels: { type: Array, required: true },
})

const form = useForm({
  _method: props.alter ? 'put' : 'post',
  name: props.alter?.name ?? '',
  handle: props.alter?.handle ?? '',
  pronouns: props.alter?.pronouns ?? '',
  bio: props.alter?.bio ?? '',
  privacy_level: props.alter?.privacy_level ?? 'public',
  show_connections: props.alter?.show_connections ?? false,
  avatar: null,
})

function submit() {
  // POST + _method : nécessaire pour l'upload d'avatar en multipart.
  form.post(props.alter ? `/alters/${props.alter.id}` : '/alters')
}
</script>

<template>
  <Head :title="alter ? 'Éditer un alter' : 'Nouvel alter'" />
  <AppLayout>
    <form class="mx-auto max-w-md space-y-4" @submit.prevent="submit">
      <h1 class="text-xl font-semibold">{{ alter ? 'Éditer un alter' : 'Nouvel alter' }}</h1>

      <label class="block text-sm">
        Nom
        <input v-model="form.name" required class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
        <span v-if="form.errors.name" class="text-xs text-red-400">{{ form.errors.name }}</span>
      </label>

      <label class="block text-sm">
        Handle
        <input v-model="form.handle" required placeholder="minuscules_et_chiffres" class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
        <span v-if="form.errors.handle" class="text-xs text-red-400">{{ form.errors.handle }}</span>
      </label>

      <label class="block text-sm">
        Pronoms
        <input v-model="form.pronouns" class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
      </label>

      <label class="block text-sm">
        Bio
        <textarea v-model="form.bio" rows="3" class="mt-1 w-full resize-none rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
      </label>

      <label class="block text-sm">
        Avatar
        <input type="file" accept="image/*" class="mt-1 w-full text-sm text-neutral-400" @input="form.avatar = $event.target.files[0]" />
        <span v-if="form.errors.avatar" class="text-xs text-red-400">{{ form.errors.avatar }}</span>
      </label>

      <label class="block text-sm">
        Grade de confidentialité
        <select v-model="form.privacy_level" class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm">
          <option v-for="level in privacyLevels" :key="level.value" :value="level.value">{{ level.label }}</option>
        </select>
      </label>

      <label class="flex items-center gap-2 text-sm text-neutral-400">
        <input v-model="form.show_connections" type="checkbox" />
        Afficher mes listes followers / abonnements
      </label>
      <p class="text-xs text-neutral-600">Masquées par défaut : elles peuvent servir à corréler deux alters.</p>

      <button :disabled="form.processing" class="w-full rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500 disabled:opacity-50">
        Enregistrer
      </button>
    </form>
  </AppLayout>
</template>
