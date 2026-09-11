<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({
  enabled: Boolean,
  pending: Boolean,
  secret: { type: String, default: null },
  provisioningUri: { type: String, default: null },
  qrCode: { type: String, default: null },
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
    <div class="flex flex-col gap-6">
      <header class="flex flex-col gap-2">
        <h1 class="font-display text-3xl">Sécurité du compte</h1>
        <p class="text-sm text-muted">
          Adresse
          <span :class="verified ? 'text-mint' : 'text-accent'">{{ verified ? 'vérifiée' : 'non vérifiée' }}</span>.
          Forcer ce compte, c'est atteindre tous tes alters d'un coup.
        </p>
      </header>

      <section v-if="!enabled && !pending" class="za-card flex flex-col gap-4 p-6">
        <h2 class="font-semibold">Double authentification</h2>
        <p class="text-sm leading-relaxed text-muted">
          Un code à usage unique s'ajoute au mot de passe. Un mot de passe volé ne suffit plus.
        </p>
        <button class="za-btn self-start" :disabled="enable.processing" @click="enable.post('/settings/security/two-factor')">
          Activer
        </button>
      </section>

      <section v-if="pending" class="za-card flex flex-col gap-4 p-6">
        <h2 class="font-semibold">Confirmer l'activation</h2>
        <p class="text-sm leading-relaxed text-muted">
          Scanne ce code dans ton application d'authentification, puis saisis le code affiché.
        </p>

        <!-- eslint-disable-next-line vue/no-v-html -- SVG produit par l'application -->
        <div v-if="qrCode" class="w-fit rounded-2xl bg-white p-3" v-html="qrCode" />

        <details class="text-xs text-faint">
          <summary class="cursor-pointer transition hover:text-muted">Saisir le code à la main</summary>
          <code class="mt-2 block break-all rounded-xl border border-line bg-black/30 p-3 text-accent-soft">
            {{ secret }}
          </code>
          <code class="mt-1 block break-all text-faint">{{ provisioningUri }}</code>
        </details>

        <form class="flex gap-2" @submit.prevent="confirm.post('/settings/security/two-factor/confirm')">
          <input
            v-model="confirm.code"
            inputmode="numeric"
            placeholder="123456"
            class="za-input text-center tracking-[0.3em]"
          />
          <button class="za-btn">Confirmer</button>
        </form>
        <p v-if="confirm.errors.code" class="za-error">{{ confirm.errors.code }}</p>
      </section>

      <section v-if="enabled" class="za-card flex flex-col gap-5 p-6">
        <h2 class="font-semibold text-mint">Double authentification active</h2>

        <div v-if="recoveryCodes" class="flex flex-col gap-2">
          <p class="za-eyebrow">
            Codes de secours — chacun ne sert qu'une fois. Garde-les ailleurs que sur l'appareil qui
            génère tes codes.
          </p>
          <ul class="grid grid-cols-2 gap-1.5 rounded-2xl border border-line bg-black/25 p-4 text-xs text-muted">
            <li v-for="code in recoveryCodes" :key="code"><code>{{ code }}</code></li>
          </ul>
          <button
            class="za-btn-quiet self-start"
            @click="regenerate.post('/settings/security/two-factor/recovery-codes')"
          >
            Générer de nouveaux codes
          </button>
        </div>

        <form class="flex flex-col gap-3" @submit.prevent="disable.delete('/settings/security/two-factor')">
          <label class="block">
            <span class="za-label">Désactiver — confirme avec ton mot de passe</span>
            <input v-model="disable.password" type="password" class="za-input" />
          </label>
          <p v-if="disable.errors.password" class="za-error">{{ disable.errors.password }}</p>
          <button class="za-btn-ghost self-start">Désactiver</button>
        </form>
      </section>
    </div>
  </AppLayout>
</template>
