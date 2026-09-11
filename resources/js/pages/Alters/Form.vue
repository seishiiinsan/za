<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import { alterGradient } from '../../lib/alter'

const props = defineProps({
  alter: { type: Object, default: null },
  privacyLevels: { type: Array, required: true },
  siblings: { type: Object, required: true },
})

const form = useForm({
  _method: props.alter ? 'put' : 'post',
  name: props.alter?.name ?? '',
  handle: props.alter?.handle ?? '',
  pronouns: props.alter?.pronouns ?? '',
  bio: props.alter?.bio ?? '',
  privacy_level: props.alter?.privacy_level ?? 'public',
  show_connections: props.alter?.show_connections ?? false,
  notify_system: props.alter?.notify_system ?? false,
  delegate_to: props.alter?.delegate_to ?? null,
  color: props.alter?.color ?? null,
  avatar: null,
})

// Les six teintes du produit, plus « automatique » : dérivée de l'identifiant.
const swatches = [0, 1, 2, 3, 4, 5].map((index) => ({ index, style: alterGradient(String(index)) }))

const currentLevel = computed(() =>
  props.privacyLevels.find((level) => level.value === form.privacy_level)
)

function submit() {
  // POST + _method : nécessaire pour l'envoi de l'avatar en multipart.
  form.post(props.alter ? `/alters/${props.alter.id}` : '/alters')
}
</script>

<template>
  <Head :title="alter ? 'Éditer un alter' : 'Nouvel alter'" />
  <AppLayout>
    <form class="flex flex-col gap-5" @submit.prevent="submit">
      <h1 class="font-display text-3xl">{{ alter ? 'Éditer un alter' : 'Nouvel alter' }}</h1>

      <div class="za-card flex flex-col gap-4 p-6">
        <p class="flex items-center gap-2 text-sm font-semibold text-muted">
          <Icon name="people" :size="17" /> Identité publique
        </p>
        <label class="block">
          <span class="za-label">Nom</span>
          <input v-model="form.name" required maxlength="60" class="za-input" />
          <span v-if="form.errors.name" class="za-error">{{ form.errors.name }}</span>
        </label>

        <label class="block">
          <span class="za-label">Handle</span>
          <input v-model="form.handle" required placeholder="minuscules_et_chiffres" class="za-input" />
          <span class="za-eyebrow mt-1 block">
            3 à 30 caractères. Modifiable une fois tous les 30 jours.
          </span>
          <span v-if="form.errors.handle" class="za-error">{{ form.errors.handle }}</span>
        </label>

        <label class="block">
          <span class="za-label">Pronoms</span>
          <input v-model="form.pronouns" maxlength="40" class="za-input" />
        </label>

        <label class="block">
          <span class="za-label">Bio</span>
          <textarea v-model="form.bio" rows="3" maxlength="500" class="za-input resize-none" />
        </label>

        <label class="block">
          <span class="za-label">Avatar</span>
          <input
            type="file"
            accept="image/*"
            class="w-full text-sm text-muted file:mr-3 file:rounded-xl file:border-0 file:bg-white/8 file:px-4 file:py-2 file:text-sm file:text-ink"
            @input="form.avatar = $event.target.files[0]"
          />
          <span class="za-eyebrow mt-1 block">
            L'image est réencodée : ses métadonnées (appareil, lieu, date) ne sont pas conservées.
          </span>
          <span v-if="form.errors.avatar" class="za-error">{{ form.errors.avatar }}</span>
        </label>
      </div>

      <div class="za-card flex flex-col gap-4 p-6">
        <p class="flex items-center gap-2 text-sm font-semibold text-muted">
          <Icon name="shield" :size="17" /> Visibilité et notifications
        </p>

        <div>
          <span class="za-label">Couleur</span>
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="flex h-9 w-9 items-center justify-center rounded-full border text-xs transition"
              :class="form.color === null ? 'border-accent text-accent' : 'border-line text-faint hover:text-muted'"
              title="Automatique"
              @click="form.color = null"
            >
              auto
            </button>
            <button
              v-for="swatch in swatches"
              :key="swatch.index"
              type="button"
              class="h-9 w-9 rounded-full transition"
              :style="{ background: swatch.style.background }"
              :class="form.color === swatch.index ? 'ring-2 ring-accent ring-offset-2 ring-offset-ground' : 'opacity-80 hover:opacity-100'"
              :aria-label="`Couleur ${swatch.index + 1}`"
              @click="form.color = swatch.index"
            />
          </div>
          <span class="za-eyebrow mt-1.5 block">
            Sert de repère dans le fil et la messagerie. Elle ne dit rien de l'alter.
          </span>
        </div>

        <label class="block">
          <span class="za-label">Grade de confidentialité</span>
          <select v-model="form.privacy_level" class="za-input">
            <option v-for="level in privacyLevels" :key="level.value" :value="level.value">
              {{ level.label }}
            </option>
          </select>
          <span class="za-eyebrow mt-1 block">{{ currentLevel?.description }}</span>
        </label>

        <label class="flex items-start gap-3 text-sm text-muted">
          <input v-model="form.show_connections" type="checkbox" class="mt-1 accent-[#ff9f78]" />
          <span>
            Afficher mes listes d'abonnés et d'abonnements
            <span class="za-eyebrow mt-0.5 block">
              Masquées par défaut : elles peuvent servir à rapprocher deux alters.
            </span>
          </span>
        </label>

        <label class="flex items-start gap-3 text-sm text-muted">
          <input v-model="form.notify_system" type="checkbox" class="mt-1 accent-[#ff9f78]" />
          <span>Remonter mes notifications à « Chez toi »</span>
        </label>

        <label v-if="siblings.data.length" class="block">
          <span class="za-label">Déléguer mes notifications</span>
          <select v-model="form.delegate_to" class="za-input">
            <option :value="null">Personne</option>
            <option v-for="sibling in siblings.data" :key="sibling.id" :value="sibling.id">
              {{ sibling.name }}
            </option>
          </select>
          <span class="za-eyebrow mt-1 block">
            Un autre alter du système peut traiter tes demandes. Ce lien reste privé.
          </span>
        </label>
      </div>

      <button class="za-btn w-full py-3" :disabled="form.processing">Enregistrer</button>
    </form>
  </AppLayout>
</template>
