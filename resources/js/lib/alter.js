/**
 * Couleur d'un alter, dérivée de son identifiant public.
 *
 * Stable d'une page à l'autre et d'une session à l'autre, sans rien stocker.
 * La couleur n'est qu'un repère visuel : rien ne se déduit de la teinte, et
 * deux alters d'un même système n'ont aucune raison de tomber sur la même.
 */
const GRADIENTS = [
  ['#9be6d4', '#4fbfa5', '#062a23'],
  ['#ffd79a', '#f0ad5c', '#3a2810'],
  ['#f6c1d8', '#e089ae', '#35121f'],
  ['#c5c9f7', '#8f96e6', '#131742'],
  ['#ffcdae', '#ff9f78', '#3a1d0f'],
  ['#bfe3a0', '#8cc46a', '#17280c'],
]

function hash(value) {
  let total = 0
  for (const character of String(value ?? '')) {
    total = (total * 31 + character.charCodeAt(0)) % 100000
  }
  return total
}

export function alterGradient(id) {
  const [from, to, ink] = GRADIENTS[hash(id) % GRADIENTS.length]

  return { background: `linear-gradient(140deg, ${from}, ${to})`, color: ink, from, to }
}

export function initials(name) {
  return String(name ?? '?').trim().charAt(0).toUpperCase() || '?'
}
