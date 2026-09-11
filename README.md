# Za (座) — Cahier des charges

> **Za** (座) : le siège, la place qu'on occupe. Le fronting — prendre place devant — est le mécanisme central du réseau. Sens dérivé : *constellation*, une figure faite de plusieurs points.

**Version** : 0.1 (conception)
**Type** : réseau social destiné aux systèmes (trouble dissociatif de l'identité / pluralité)
**Stack cible** : Laravel · Vue.js · Inertia.js · PHP

---

## 1. Vision

Za est un réseau social où **un compte représente un système** (une personne plurielle) et où **chaque identité interne (alter) dispose de son propre profil public**. Le corps physique et le système restent privés ; toute la vie sociale se joue au niveau des alters.

Le produit doit garantir un principe absolu : **il ne doit jamais être possible de relier publiquement deux alters à un même système** (invariant d'anti-corrélation). Ce contrat de confiance conditionne toute l'architecture.

---

## 2. Glossaire

| Terme | Définition |
|---|---|
| **Système** | Le compte. Une personne plurielle. Détient l'authentification. Jamais exposé publiquement (sauf surface optionnelle en messagerie partagée). |
| **Alter** | Une identité interne du système. Unité publique : profil, posts, follows, messages. N alters par système. |
| **Fronting** | Le fait, pour un alter, de contrôler le corps à un instant donné. |
| **Front actif** | L'alter (ou les alters) actuellement sélectionné(s) dans la session. Toute action d'écriture lui est attribuée. |
| **Co-front** | Plusieurs alters actifs simultanément. |
| **Dashboard système** | Vue privée agrégeant l'activité de tous les alters. Centre de contrôle. |
| **Invariant d'anti-corrélation** | Règle transversale : `system_id` n'est jamais exposé côté public, et rien ne doit permettre de déduire que deux alters partagent un système. |

---

## 3. Concepts fondamentaux

### 3.1 Séparation système / alter

- **Système = privé.** Sert de porte d'authentification et de centre de contrôle. Non cherchable, sans profil public par défaut.
- **Alter = public.** Porte l'intégralité de la vie sociale (profil, posts, follows, messages, réactions).

### 3.2 Invariant d'anti-corrélation (transversal)

Règle prioritaire, à vérifier sur **chaque** endpoint et **chaque** vue publique :

1. `system_id` n'apparaît jamais dans une réponse publique (API, HTML, métadonnées).
2. Aucune fonctionnalité ne doit rapprocher deux alters d'un même système (recherche, suggestions, graphe).
3. Les alters non-listés/cachés restent invisibles aux surfaces publiques selon leur grade (§7).
4. Le feed agrégé système est strictement réservé au dashboard privé.

> Limite assumée : la **corrélation par graphe social** (deux alters suivant les mêmes comptes de niche) ne peut être éliminée à 100 %. Atténuée, jamais annulée. Voir §11.

---

## 4. Modèle de données

### 4.1 Entités principales

```
System (privé)
  id
  email, password           -- auth
  display_name              -- nom public SI messagerie partagée activée
  description               -- idem
  settings (JSON)           -- co-front, seuil switch (Xh), etc.

Alter (public)
  id
  system_id (FK)            -- JAMAIS exposé publiquement
  name, pronouns, avatar, bio
  privacy_level             -- public | privé | non-listé | lecture (§7)
  settings (JSON)           -- visibilité followers/abonnements, notifs, etc.

Post
  id
  content, created_at
  status                    -- pending | published (§5.3)
  -- pas d'alter_id ici : auteurs dans le pivot

PostAuthor (pivot)
  post_id (FK)
  alter_id (FK)
  accepted (bool)           -- consentement co/cross-post

Follow
  follower_alter_id (FK)
  followed_alter_id (FK)

Block
  blocker_alter_id (FK)
  target_type               -- alter | system
  target_ref_id             -- alter_id OU system_id (jamais révélé au bloqueur)
```

### 4.2 Messagerie (correspondant polymorphe)

```
Correspondent
  id
  type                      -- alter | system
  ref_id                    -- alter_id OU system_id

Conversation
  id
  effective_mode            -- perso | partagé (négocié entre les 2 côtés)

ConvParticipant
  conversation_id (FK)
  correspondent_id (FK)

Message
  id
  conversation_id (FK)
  author_correspondent_id (FK)   -- l'entité adressable (alter ou système)
  author_alter_id (FK, nullable) -- alter réel qui frontait (usage interne + option "afficher l'auteur")
  content, created_at
```

---

## 5. Fonctionnalités — publication

### 5.1 Compte & session

- Authentification unique au niveau **système**.
- Session = `system_id` + `active_alter_id`.
- **Switch d'alter sans reconnexion** : changer `active_alter_id`. Toute écriture prend le front actif.
- Co-front : plusieurs alters actifs → traités comme co-auteurs (§5.3).

### 5.2 Profil alter

- Réglages **par alter**, sauf réglages propres au système (co-front, seuil de switch…) qui restent au niveau système.
- Grade de confidentialité par alter (§7).
- Option d'affichage/masquage de la liste des followers et des abonnements.

### 5.3 Posts, co-posts, cross-posts

- Tout post a **≥ 1 auteur** via `PostAuthor`.
  - Solo = 1 auteur.
  - **Co-front** (2 alters du même système) et **cross-post** (2 alters de systèmes différents) = N auteurs. **Traitement identique**, aucune règle spéciale même-système.
- **Consentement obligatoire** : un co/cross-post reste `pending` tant que tous les auteurs invités n'ont pas `accepted = true`. Publication uniquement quand tous ont accepté (évite l'exposition non consentie).

### 5.4 Feed

- **Feed public** : posts des alters suivis.
- **Feed dashboard (privé)** : posts où `alter_id IN (mes alters)` via `PostAuthor`. Chemin unique pour solo / co-front / cross-post.

### 5.5 Follow

- Relation **alter ↔ alter** uniquement.
- Recherche et découverte se font sur des **profils d'alter**.
- **Aucune recommandation de comptes** (vecteur de corrélation système). Choix de conception ferme.

---

## 6. Fonctionnalités — messagerie

### 6.1 Modes (réglable par système)

- **Messagerie perso** : le système s'expose comme N correspondants (1 par alter). Conversations alter ↔ alter.
- **Messagerie partagée** : le système s'expose comme 1 correspondant. Conversations système ↔ système.
- Le mode de l'interlocuteur peut différer → 4 combinaisons (alter-alter, alter-système, système-alter, système-système), toutes gérées par le **correspondant polymorphe** sans logique par cas.

### 6.2 Surface publique système (si messagerie partagée)

- Le système expose alors **nom + description uniquement**. Jamais ses alters.
- Dans la liste des conversations : le **nom du système** s'affiche.
- Option **« afficher l'auteur·e du message »** : si activée, l'avatar affiché change selon l'alter qui a écrit (`author_alter_id`), tout en gardant le nom système comme titre de conversation.

### 6.3 Mode effectif par conversation

- Le mode réel d'une conversation est **négocié entre les deux côtés** et stocké sur `Conversation.effective_mode`. Il ne découle pas seulement du réglage global d'un système.

### 6.4 Migration de mode (v2 — voir périmètre §9)

Feature avancée, hors MVP. Règles définies :

**Partagé → perso (split)** — 1 conversation système éclatée en N conversations (1 par alter) :

- **Messages sortants** : déplacés vers la conversation de leur `author_alter_id`.
- **Messages sans auteur** (`author_alter_id = null`, ex. avant l'option « afficher l'auteur » ou si elle était OFF) : **conservés en base**, insérés dans aucune conversation d'alter (récupérables si retour en mode partagé).
- **Messages entrants** (écrits « au système ») — heuristique d'attribution :

```
Xh = seuil de switch, paramétrable par système (défaut : 5h)

avant  = dernier alter ayant répondu AVANT, si écart < Xh
après  = premier alter ayant répondu APRÈS, si écart < Xh

Ordre d'évaluation :
1. si avant == après (même alter) ................ cette conv   [ignore Xh]
2. si avant && après && avant != après ........... dupliqué dans les 2 convs
3. si avant seul ................................. conv de "avant"
4. si après seul ................................. conv de "après"
5. sinon ......................................... conservé en base, aucune conv
```

**Perso → partagé (merge)** — N conversations fusionnées en 1, tri par `created_at` :

- Fusion **possible uniquement** si l'interlocuteur d'en face est **1 seul correspondant** (mode partagé de son côté).
- Si l'autre côté est en perso, les N conversations parlent à N alters distincts → **pas de fusion** (ce sont des personnes différentes).

> Réserves assumées (§11) : trous de contexte possibles (cas 2 et 5), seuil `Xh` imparfait par nature. Alternative recommandée à terme : **capturer l'alter à la réception** plutôt que le deviner au split.

---

## 7. Confidentialité — grades par alter

| Grade | Cherchable | Profil visible | Réactions (like/commentaire) | Messagerie / feed |
|---|---|---|---|---|
| **public** | oui | oui | oui | oui |
| **privé** | oui | masqué tant que la demande n'est pas acceptée | oui (après acceptation) | oui |
| **non-listé** | non | via trace publique uniquement | oui | oui |
| **lecture** | non | non | **non** | feed + MP uniquement |

> **non-listé** ne cache pas l'existence : un like/commentaire laisse une trace publique (« X a réagi »). Le grade masque la *recherche*, pas toute trace. Nom choisi pour ne pas promettre une garantie qu'il ne tient pas.

---

## 8. Blocage

Au moment de bloquer, choix entre deux cibles :

- **Bloquer l'alter** : masque réciproque **avec cet alter seulement**. Les autres alters du même système continuent de voir le contenu (chaque alter = personne distincte). En conversation partagée, le bloqueur n'apparaît pas quand cet alter est le front sélectionné.
- **Bloquer le système** (déclenché depuis le profil d'un alter, **sans révéler quel système**) : comportement d'un blocage de compte classique. **Seul vecteur** qui protège contre un harceleur revenant via un autre alter du même système.

> Limite assumée : le blocage-alter **ne protège pas** d'un système malveillant multi-alters. Comportement voulu, à documenter côté utilisateur.

---

## 9. Notifications

- Notifications **par alter**.
- Réglage par alter pour les **remonter aussi au système** (ex. déléguer à un autre alter la gestion de ses demandes d'abonnement).
- Le dashboard système peut agréger selon ces réglages.

---

## 10. Modération

- Le signalement vise **l'alter**.
- Accès au lien `système ↔ alter` en back-office : **développeur unique** (phase actuelle).

> Risque : le développeur est le point unique capable de corréler tous les systèmes → cible juridique et éthique. Voir §11.

---

## 11. Contraintes non fonctionnelles & risques

### 11.1 Sécurité / vie privée (priorité maximale)

- `system_id` **chiffré au repos** ; jamais renvoyé par une API publique.
- Fuite DB = outing de masse de personnes plurielles → donnée ultra-sensible.
- **Défaut = masqué** pour les listes de followers/abonnements (limiter la corrélation par graphe).
- **Corrélation par graphe social** : atténuée, non éliminable. À documenter honnêtement auprès des utilisateurs.

### 11.2 Légal — RGPD / donnée de santé

- L'identité dissociative peut relever de la **donnée de santé** (RGPD). À trancher avec cadrage juridique **avant mise en production réelle**.
- À prévoir : hébergement adapté, consentement, export, **droit à l'oubli d'un seul alter**.

### 11.3 Suppression d'alter

- Règle à définir pour posts, co-posts (autres auteurs présents → pas d'effacement simple), conversations. Options : anonymiser / conserver / transférer.

### 11.4 Dette d'accès

- Politique d'accès staff formalisée nécessaire si l'équipe grandit.

---

## 12. Périmètre

### MVP (prouver le concept)

- Système (auth) + alters (profils)
- Switch d'alter en session
- Posts **solo** + feed
- Follow alter ↔ alter
- Dashboard système (feed agrégé privé)
- 2 grades de confidentialité : **public / privé**
- Invariant d'anti-corrélation (`system_id` jamais exposé)

### v2 (après validation)

- Co-post / cross-post (pivot auteurs + invitations)
- Messagerie (tous modes)
- Migration de mode messagerie (split / merge)
- Grades **non-listé** / **lecture**
- Blocage alter / système
- Notifications paramétrables croisées

### Ultérieur / à cadrer

- Conformité RGPD-santé complète
- Suppression d'alter (règles de rétention)
- Capture de l'alter à la réception (remplace l'heuristique de migration)

---

## 13. Risques ouverts (synthèse)

| # | Risque | Statut |
|---|---|---|
| 1 | Corrélation par graphe social | Atténué, non éliminable — assumé |
| 2 | Harcèlement multi-alters d'un même système | Mitigé par blocage-système — à documenter |
| 3 | Fuite DB = outing de masse | Chiffrement `system_id` — critique |
| 4 | Statut RGPD donnée de santé | À cadrer avant prod |
| 5 | Trous de contexte à la migration messagerie | Assumé (v2) / résolu par capture-à-la-réception |
| 6 | Développeur = point unique de corrélation | Dette d'accès notée |

---

## Annexe — Démarrage (MVP implémenté)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # compte de démo : system@za.test / password
php artisan storage:link
npm run build                   # ou npm run dev
php artisan serve
```

Qualité :

```bash
vendor/bin/pint --test          # style
php vendor/bin/phpunit          # suite complète
php vendor/bin/phpunit tests/Feature/AntiCorrelationTest.php   # invariant, bloquant en CI
```

### Ce que couvre le MVP

| Périmètre | Où |
|---|---|
| Auth système (inscription, connexion, reset) | `app/Http/Controllers/Auth/`, `config/auth.php` |
| CRUD alters + avatar | `app/Http/Controllers/AlterController.php` |
| Front actif en session | `app/Support/Front.php`, `app/Http/Middleware/EnsureActiveAlter.php` |
| Posts solo (pivot `post_authors` prêt pour N auteurs) | `app/Models/Post.php`, `PostController` |
| Feed public + dashboard système agrégé | `FeedController`, `DashboardController` |
| Follow alter ↔ alter, demandes, listes masquées par défaut | `FollowController`, `Alter::showsConnections()` |
| Grades public / privé | `app/Enums/PrivacyLevel.php`, `Alter::isVisibleTo()` |
| Invariant anti-corrélation | `app/Http/Resources/AlterResource.php`, `tests/Feature/AntiCorrelationTest.php` |

### Écarts assumés

- **Chiffrement de `system_id` au repos** : non fait. Chiffrer la clé étrangère casserait
  l'intégrité référentielle et les jointures. L'invariant repose ici sur un point de sortie
  unique (`AlterResource`), `$hidden` sur le modèle, et un test bloquant qui scanne les
  réponses publiques. Le chiffrement au repos reste à traiter au niveau du stockage
  (volume/colonne chiffrée) dans l'issue Sécurité.
- **Suppression d'alter** : cascade dure (posts et follows partent avec). La politique de
  rétention fine fait l'objet d'une issue dédiée.
- **Grades non-listé / lecture, messagerie, co-posts, blocage, notifications** : v2.
