<script setup>
defineProps({ name: { type: String, required: true }, size: { type: Number, default: 22 } })

// Un seul jeu de traits : 1.6px, bouts arrondis, grille 24.
const paths = {
  feed: 'M3 10.5 12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
  search: 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-3.5-3.5',
  bell: 'M18 15v-4a6 6 0 1 0-12 0v4l-2 3h16zM10 21a2 2 0 0 0 4 0',
  message: 'M21 14a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z',
  people: 'M16 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M21 20v-1a4 4 0 0 0-3-3.9M16.5 4.1a4 4 0 0 1 0 7.8',
  invite: 'M12 5v14M5 12h14',
  shield: 'M12 3l7 3v6c0 4.2-2.9 7.8-7 9-4.1-1.2-7-4.8-7-9V6z',
  key: 'M15.5 8.5a4 4 0 1 0-3.9 4L10 14l-2 .5L7.5 17 5 17.5 4.5 20 3 20.5 2.5 19l9.1-9.1',
  home: 'M4 20V9l8-6 8 6v11a1 1 0 0 1-1 1h-4v-7h-6v7H5a1 1 0 0 1-1-1z',
  logout: 'M15 16l4-4-4-4M19 12H9M12 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h6',
}
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.6"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path :d="paths[name]" />
  </svg>
</template>
