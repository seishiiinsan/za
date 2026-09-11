<script setup>
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
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
    <form class="flex flex-col gap-5" @submit.prevent="form.put('/settings/messaging')">
      <header class="flex flex-col gap-2">
        <h1 class="font-display text-3xl">Messagerie</h1>
        <p class="text-sm leading-relaxed text-muted">
          Réglage au niveau du système. Sa surface publique se limite à un nom et une description :
          tes alters n'y apparaissent jamais.
        </p>
      </header>

      <div class="za-card flex flex-col gap-4 p-6">
        <label class="block">
          <span class="za-label">Mode</span>
          <select v-model="form.mode" class="za-input">
            <option v-for="mode in modes" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
          </select>
        </label>

        <template v-if="form.mode === 'shared'">
          <label class="block">
            <span class="za-label">Nom public du compte</span>
            <input v-model="form.display_name" maxlength="60" class="za-input" />
          </label>

          <label class="block">
            <span class="za-label">Description</span>
            <textarea v-model="form.description" rows="3" maxlength="500" class="za-input resize-none" />
          </label>

          <label class="flex items-start gap-3 text-sm text-muted">
            <input v-model="form.show_message_author" type="checkbox" class="mt-1 accent-[#ff9f78]" />
            <span>
              Afficher l'auteur·e de chaque message
              <span class="za-eyebrow mt-0.5 block">
                L'avatar devient celui de l'alter qui écrit. Le titre de la conversation reste le
                nom du compte.
              </span>
            </span>
          </label>
        </template>

        <label class="block">
          <span class="za-label">Seuil de switch (heures)</span>
          <input v-model.number="form.switch_threshold_hours" type="number" min="1" max="72" class="za-input" />
          <span class="za-eyebrow mt-1 block">
            Sert à répartir les messages reçus quand tu repasses en mode perso. Au-delà de ce délai,
            un message n'est plus attribué : il est conservé et revient si tu repasses en partagé.
          </span>
        </label>
      </div>

      <button class="za-btn w-full py-3" :disabled="form.processing">Enregistrer</button>
    </form>
  </AppLayout>
</template>
