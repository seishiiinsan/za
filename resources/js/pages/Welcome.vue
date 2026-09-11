<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'

const beats = [
  {
    title: 'Le front, pas le compte',
    body: "L'alter au front signe ce qui est écrit. Changer de front prend un clic et ne déconnecte personne.",
  },
  {
    title: 'Écrire à plusieurs, avec accord',
    body: "Un post à deux reste privé tant que chaque auteur n'a pas accepté. Personne n'est publié sans l'avoir voulu.",
  },
  {
    title: 'Quatre grades de visibilité',
    body: 'Public, privé, non-listé, lecture seule. Chaque alter choisit le sien, indépendamment des autres.',
  },
]
</script>

<template>
  <Head title="Za" />
  <AppLayout>
    <div class="flex flex-col gap-20 pb-16">
      <!-- Le nom du produit veut dire « la place qu'on occupe ». La page part de là. -->
      <section class="grid items-center gap-10 pt-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="flex flex-col gap-6">
          <span class="font-display text-7xl leading-none text-accent">座</span>
          <h1 class="max-w-[16ch] font-display text-5xl leading-[1.05] text-balance sm:text-6xl">
            Un compte. Autant de voix qu'il en faut.
          </h1>
          <p class="max-w-[52ch] text-lg leading-relaxed text-muted">
            Za est un réseau social pour les systèmes pluriels. Chaque alter y a son profil, ses
            abonnements et ses messages — et rien, nulle part, ne permet de relier publiquement
            deux profils au même compte.
          </p>
          <div class="flex flex-wrap gap-3">
            <Link href="/register" class="za-btn px-6 py-3 text-base">Créer un système</Link>
            <Link href="/login" class="za-btn-ghost px-6 py-3 text-base">Se connecter</Link>
          </div>
        </div>

        <!-- La promesse, dessinée : le compte en haut, les profils en bas, rien qui traverse. -->
        <figure class="za-card za-card-lifted flex flex-col gap-4 p-6">
          <figcaption class="za-eyebrow">Ce que voit le reste du monde</figcaption>

          <div class="rounded-2xl border border-dashed border-line px-4 py-3">
            <p class="text-sm font-semibold">Ton compte</p>
            <p class="text-xs text-faint">connexion, réglages, vue d'ensemble — jamais public</p>
          </div>

          <div class="flex items-center gap-3 text-[0.7rem] text-accent">
            <span class="h-px flex-1 bg-accent/30" />
            rien ne franchit cette ligne
            <span class="h-px flex-1 bg-accent/30" />
          </div>

          <div class="grid grid-cols-3 gap-2">
            <div
              v-for="profile in [
                { name: 'Kai', hue: 'linear-gradient(140deg, #9be6d4, #4fbfa5)', ink: '#062a23' },
                { name: 'Nori', hue: 'linear-gradient(140deg, #ffd79a, #f0ad5c)', ink: '#3a2810' },
                { name: 'Sora', hue: 'linear-gradient(140deg, #f6c1d8, #e089ae)', ink: '#35121f' },
              ]"
              :key="profile.name"
              class="flex flex-col items-center gap-2 rounded-2xl bg-white/4 px-2 py-3"
            >
              <span
                class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold"
                :style="{ background: profile.hue, color: profile.ink }"
              >
                {{ profile.name.charAt(0) }}
              </span>
              <span class="text-xs text-muted">@{{ profile.name.toLowerCase() }}</span>
            </div>
          </div>

          <p class="text-xs leading-relaxed text-faint">
            Ces trois profils appartiennent au même compte. Rien sur cette rangée ne le dit — ni
            leurs identifiants, ni leur ordre de création, ni les métadonnées de leurs images.
          </p>
        </figure>
      </section>

      <section class="grid gap-4 sm:grid-cols-3">
        <div v-for="beat in beats" :key="beat.title" class="za-card flex flex-col gap-2 p-6">
          <h2 class="font-display text-2xl">{{ beat.title }}</h2>
          <p class="text-sm leading-relaxed text-muted">{{ beat.body }}</p>
        </div>
      </section>

      <section class="flex flex-col gap-4 border-l-2 border-accent/40 pl-6">
        <h2 class="font-display text-3xl">Ce que Za ne promet pas</h2>
        <p class="max-w-[58ch] leading-relaxed text-muted">
          Deux alters qui suivent les mêmes comptes de niche restent rapprochables par quelqu'un
          de patient. Za limite ce risque — listes d'abonnements masquées par défaut, aucune
          suggestion de comptes, identifiants opaques — sans prétendre l'effacer. C'est écrit ici
          plutôt que caché dans des conditions d'utilisation.
        </p>
      </section>

      <section class="za-card flex flex-col items-start gap-4 p-8">
        <h2 class="font-display text-3xl">Prendre sa place</h2>
        <p class="max-w-[50ch] leading-relaxed text-muted">
          La création d'un système prend une minute. Le premier alter suit, puis les autres, quand
          vous voudrez.
        </p>
        <Link href="/register" class="za-btn px-6 py-3 text-base">Commencer</Link>
      </section>
    </div>
  </AppLayout>
</template>
