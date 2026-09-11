<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({
  enabled: Boolean,
  pending: Boolean,
  secret: { type: String, default: null },
  provisioningUri: { type: String, default: null },
  recoveryCodes: { type: Array, default: null },
  verified: Boolean,
})

const enable = useForm({})
const confirm = useForm({ code: '' })
const disable = useForm({ password: '' })
const regenerate = useForm({})
</script>

<template>
  <Head title="Sécurité" />
  <AppLayout>
    <div class="mx-auto max-w-md space-y-6">
      <div>
        <h1 class="text-xl font-semibold">Sécurité du compte</h1>
        <p class="text-sm text-neutral-500">
          Adresse {{ verified ? 'vérifiée' : 'non vérifiée' }}.
        </p>
      </div>

      <section v-if="!enabled && !pending" class="space-y-3">
        <h2 class="text-sm uppercase tracking-wide text-neutral-500">Double authentification</h2>
        <p class="text-sm text-neutral-400">
          Un code à usage unique s'ajoute au mot de passe. Un mot de passe volé ne suffit plus.
        </p>
        <button
          :disabled="enable.processing"
          class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500 disabled:opacity-50"
          @click="enable.post('/settings/security/two-factor')"
        >
          Activer
        </button>
      </section>

      <section v-if="pending" class="space-y-3">
        <h2 class="text-sm uppercase tracking-wide text-neutral-500">Confirmer l'activation</h2>
        <p class="text-sm text-neutral-400">
          Ajoutez ce secret dans votre application d'authentification, puis saisissez le code
          qu'elle affiche.
        </p>
        <code class="block break-all rounded-md border border-neutral-800 bg-neutral-950 p-3 text-xs text-violet-300">
          {{ secret }}
        </code>
        <code class="block break-all text-xs text-neutral-600">{{ provisioningUri }}</code>

        <form class="flex gap-2" @submit.prevent="confirm.post('/settings/security/two-factor/confirm')">
          <input
            v-model="confirm.code"
            inputmode="numeric"
            placeholder="123456"
            class="flex-1 rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm"
          />
          <button class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500">Confirmer</button>
        </form>
        <p v-if="confirm.errors.code" class="text-xs text-red-400">{{ confirm.errors.code }}</p>
      </section>

      <section v-if="enabled" class="space-y-3">
        <h2 class="text-sm uppercase tracking-wide text-neutral-500">Double authentification active</h2>

        <div v-if="recoveryCodes" class="space-y-2">
          <p class="text-xs text-neutral-500">
            Codes de secours — chacun ne sert qu'une fois. Gardez-les hors de l'appareil qui
            génère vos codes.
          </p>
          <ul class="grid grid-cols-2 gap-1 rounded-md border border-neutral-800 bg-neutral-950 p-3 text-xs text-neutral-300">
            <li v-for="code in recoveryCodes" :key="code"><code>{{ code }}</code></li>
          </ul>
          <button
            class="text-xs text-neutral-500 hover:text-neutral-200"
            @click="regenerate.post('/settings/security/two-factor/recovery-codes')"
          >
            Générer de nouveaux codes
          </button>
        </div>

        <form class="space-y-2" @submit.prevent="disable.delete('/settings/security/two-factor')">
          <label class="block text-sm">
            Désactiver — confirmez avec votre mot de passe
            <input v-model="disable.password" type="password" class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
          </label>
          <p v-if="disable.errors.password" class="text-xs text-red-400">{{ disable.errors.password }}</p>
          <button class="rounded-md border border-neutral-800 px-4 py-2 text-sm hover:border-red-500">Désactiver</button>
        </form>
      </section>
    </div>
  </AppLayout>
</template>
