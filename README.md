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

## Annexe A — État du projet

*Dernière mise à jour : 11 septembre 2026. 123 tests (686 assertions), 14 migrations,
15 branches de feature, 21 fusions dans `develop`. `main` n'a pas encore bougé : la mise en
production attend le cadrage RGPD.*

Le MVP et la v2 sont livrés sur `develop`, une branche par feature, fusionnée en `--no-ff`.

### Ce que couvre le MVP

| Périmètre | Où | Branche |
|---|---|---|
| Auth système (inscription, connexion, reset) | `app/Http/Controllers/Auth/`, `config/auth.php` | `feat/auth-system-alters` |
| CRUD alters + avatar + profil public par handle | `app/Http/Controllers/AlterController.php`, `AlterProfileController` | `feat/auth-system-alters` |
| Front actif en session, switch sans reconnexion | `app/Support/Front.php`, `app/Http/Middleware/EnsureActiveAlter.php` | `feat/alter-session-switch` |
| Posts solo (pivot `post_authors` prêt pour N auteurs) | `app/Models/Post.php`, `PostController` | `feat/posts-feed` |
| Feed public des alters suivis | `FeedController` | `feat/posts-feed` |
| Follow alter ↔ alter, demandes, listes masquées par défaut | `FollowController`, `Alter::showsConnections()` | `feat/follow-alter` |
| Dashboard système (feed agrégé privé) | `DashboardController` | `feat/system-dashboard` |
| Grades public / privé | `app/Enums/PrivacyLevel.php`, `Alter::isVisibleTo()` | `feat/privacy-public-private` |
| Invariant anti-corrélation | `app/Http/Resources/AlterResource.php`, `tests/Feature/AntiCorrelationTest.php` | `feat/anti-correlation` |

### Ce que couvre la v2

| Périmètre | Où | Branche |
|---|---|---|
| Co-posts / cross-posts + invitations | `PostController`, `PostInvitationController` | `feat/coposts-invitations` |
| Likes et commentaires | `ReactionController`, `CommentController`, `app/Models/Comment.php` | `feat/reactions` |
| Grades non-listé / lecture | `app/Enums/PrivacyLevel.php` | `feat/privacy-unlisted-readonly` |
| Blocage alter / compte | `app/Support/BlockList.php`, `BlockController` | `feat/blocking-alter-system` |
| Messagerie, correspondant polymorphe, mode partagé | `app/Support/Messaging.php`, `ConversationController` | `feat/messaging` |
| Migration de mode (split / merge, seuil Xh) | `app/Support/MessagingModeMigration.php` | `feat/messaging-mode-migration` |
| Notifications par alter, remontée système, délégation | `app/Support/Notifier.php`, `NotificationController` | `feat/notifications-cross` |

### Les quatre garde-fous de l'invariant

L'anti-corrélation ne tient pas à une règle écrite quelque part, mais à des mécanismes
vérifiables. Deux d'entre eux sont nés de fuites trouvées en cours d'implémentation.

1. **Un point de sortie unique.** `AlterResource` est le seul chemin par lequel un alter
   devient du JSON, et `system_id` est masqué au niveau du modèle. Un test bloquant en CI
   scanne les réponses publiques (profils, recherche, payloads Inertia).
2. **Des identifiants opaques.** Les clés auto-incrémentées trahissaient l'ordre de création :
   deux alters créés à la suite portaient des numéros voisins. Systèmes, alters et posts
   portent un uuid, seul identifiant sérialisé et seule clé de route.
3. **Des avatars réencodés.** L'image envoyée gardait son EXIF (appareil, GPS, date de prise
   de vue), de quoi rapprocher deux avatars. Tout envoi est décodé, redimensionné, réencodé.
4. **Des logs masqués.** Une exception de requête écrivait le SQL avec ses valeurs : le
   fichier de log devenait la table de corrélation que le produit s'interdit de publier.
   Voir `app/Logging/RedactSystemId.php`.

### Décisions tranchées

| Question | Décision |
|---|---|
| Chiffrement de `system_id` | Au niveau du volume, à l'hébergement. Chiffrer la clé étrangère casserait les jointures. Repoussé. |
| Suppression d'un alter | Suppression douce, restaurable. L'alter quitte toutes les surfaces ; ses posts s'affichent sous un auteur « Inconnu ». |
| Grade `lecture` | Bloque les réactions, la publication et l'invitation comme co-auteur. |
| Notification en boîte commune | Tous les alters du système destinataire sont notifiés ; le premier qui lit marque lu pour tout le monde. |
| Changement de handle | Un par 30 jours, ancien handle en quarantaine autant de temps. |
| Handle d'un alter supprimé | Libéré aussitôt ; la restauration le reprend s'il est libre, sinon en demande un autre. |
| Sosies dans les handles | Seuls les chiffres sont normalisés (`@ka1` = `@kai`, mais `@lila` ≠ `@iiia`). |
| Co-front (plusieurs alters actifs) | Reporté : la session ne porte qu'un front. |

### Politique des handles

Le handle (`@kai`) est l'identité publique d'un alter : URL de profil, signature des posts,
cible d'une invitation ou d'un message. Deux handles qui se ressemblent, ou un handle repris
juste après avoir été libéré, servent à se faire passer pour quelqu'un auprès de ses abonnés.

- **Forme** : 3 à 30 caractères, minuscules, chiffres et `_`. Mots de service réservés
  (admin, support, securite…).
- **Unicité** sur une forme canonique : casse ignorée, `_` retirés, chiffres sosies ramenés
  sur leur lettre (`0` → `o`, `1` → `i`, `3` → `e`, `4` → `a`, `5` → `s`, `7` → `t`).
  `@ka1nu1t` ne peut donc pas cohabiter avec `@kai_nuit`. Les lettres restent distinctes
  entre elles : `@lila` et `@iiia` sont deux handles différents.
- **Changement** : une fois tous les 30 jours. L'ancien handle part en quarantaine pour la
  même durée — son propriétaire peut le reprendre, personne d'autre.
- **Suppression** : le handle est libéré immédiatement. L'original est mémorisé ; à la
  restauration il est repris s'il est encore libre, sinon le système en choisit un autre.

### Durcissement de l'authentification

Forcer un compte système, c'est atteindre d'un coup tous les alters d'une personne.

- Vérification d'adresse obligatoire : un système non vérifié n'atteint que l'écran de
  vérification et ses réglages de sécurité.
- Limitation des tentatives de connexion par couple e-mail + IP (5 par minute), plus
  l'inscription, la réinitialisation, le challenge et la confirmation du second facteur.
- Double authentification TOTP (RFC 6238, implémentée dans `app/Support/TwoFactor.php`),
  confirmée avant activation, avec codes de secours à usage unique. Secret et codes chiffrés
  en base ; désactivation protégée par le mot de passe. Le QR code est rendu en SVG côté
  serveur (`bacon/bacon-qr-code`), avec saisie manuelle du secret en repli.

### Ce qui reste ouvert

- **Cadrage RGPD et hébergement** — bloquant avant toute mise en production. Statut juridique
  de la donnée de santé, consentement, export, droit à l'oubli d'un seul alter. Avec lui
  viennent le chiffrement du volume, la gestion des clés et des sauvegardes chiffrées.
- **Modération et signalement** — signalement visant l'alter, back-office minimal, sanctions,
  et journalisation des accès au lien système ↔ alter.
- **Purge définitive** — la suppression douce n'efface rien : délai de rétention à définir, et
  sort d'un co-post dont un seul auteur s'en va.
- **Capture de l'alter à la réception** — remplacerait l'heuristique d'attribution du split.
- **Corrélation par graphe social** — atténuée (listes masquées, aucune suggestion de comptes),
  jamais éliminée. À dire honnêtement aux utilisateurs.

### Écarts assumés

- **Chiffrement de `system_id` au repos** : traité au niveau du volume, à l'hébergement, et
  repoussé (décision prise). Chiffrer la clé étrangère casserait l'intégrité référentielle et
  les jointures. Côté code, l'invariant tient par le point de sortie unique, `$hidden` sur le
  modèle, le masquage des logs et le test bloquant.
- **Grade `lecture`** : le cahier des charges ne parle que des réactions. L'implémentation
  bloque aussi la publication et l'invitation comme co-auteur, au nom du libellé.
- **Migration de mode (split)** : l'attribution des messages entrants reste une heuristique.
  Les cas ambigus dupliquent, les cas insolubles conservent le message hors conversation.
- **Handles sosies** : la normalisation ne couvre que les chiffres. `@kal` et `@kai` peuvent
  coexister.

---

## Annexe B — Faire tourner le projet en local

### Prérequis

| Outil | Version | Vérifier |
|---|---|---|
| PHP | 8.3 minimum (8.4 utilisé ici) | `php -v` |
| Extensions PHP | `pdo_sqlite`, `sqlite3`, `mbstring`, `gd`, `intl`, `zip`, `xml` | `php -m` |
| Composer | 2.x | `composer -V` |
| Node | 20 minimum (22 utilisé ici) | `node -v` |

L'extension `gd` n'est pas optionnelle : elle réencode les avatars, et sans elle tout envoi
d'image échoue. Aucun serveur de base de données à installer — le développement tourne sur
SQLite, un simple fichier.

### Installation

```bash
git clone https://github.com/seishiiinsan/za.git
cd za
git checkout develop

composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed        # jeu de démo
php artisan storage:link          # sert les avatars depuis public/
```

### Jeu d'essai de démonstration

Le seeder par défaut crée un compte et trois alters, de quoi cliquer. Pour une
application peuplée — utile pour juger les écrans pleins et repérer ce qui ne tient pas
à l'échelle :

```bash
php artisan migrate:fresh
php artisan db:seed --class=DemoSeeder    # environ 6 000 enregistrements, ~15 s
```

60 systèmes, 200 alters, 780 abonnements, 930 posts (dont des co-écritures encore en
attente), 450 commentaires, 1 000 réactions, 110 conversations et leurs messages, 300
notifications, quelques blocages. Les textes sont écrits (`database/seeders/DemoContent.php`)
et composés, jamais remplis de faux latin. Les relations tiennent : on ne suit que des alters
d'autres systèmes, un alter en lecture seule ne publie pas, un commentaire arrive après son
post, un fil se lit dans l'ordre. `system@za.test` / `password` ouvre sur un feed plein.
`tests/Feature/DemoSeederTest.php` vérifie ces règles.

### Lancer

Deux terminaux, ou `npm run build` une fois si le front ne change pas :

```bash
php artisan serve                 # http://127.0.0.1:8000
npm run dev                       # Vite, rechargement à chaud
```

Le seeder crée un système de démonstration : **system@za.test** / **password**, avec deux
alters (`@kai`, `@nori`), un alter extérieur (`@sora`), des posts et un abonnement.

### Vérifier que tout est en place

```bash
php vendor/bin/phpunit                                         # 123 tests
vendor/bin/pint --test                                         # style
php vendor/bin/phpunit tests/Feature/AntiCorrelationTest.php    # invariant, bloquant en CI
```

### Les e-mails en développement

`MAIL_MAILER=log` par défaut : les liens de vérification d'adresse et de réinitialisation de
mot de passe ne partent nulle part, ils sont écrits dans `storage/logs/laravel.log`. Pour
vérifier une adresse, ouvrez le log et copiez l'URL signée. Un compte créé par le seeder est
déjà vérifié.

### Dépannage

| Symptôme | Cause | Correctif |
|---|---|---|
| `Target [Inertia\Ssr\Gateway] is not instantiable` | `composer install --no-scripts` : le manifeste des paquets n'a pas été construit | `php artisan package:discover` |
| `Vite manifest not found` | Les assets n'ont jamais été compilés | `npm run build` ou `npm run dev` |
| Avatars en 404 | Lien symbolique absent | `php artisan storage:link` |
| `database/database.sqlite does not exist` | Fichier de base non créé | `touch database/database.sqlite` |
| Envoi d'avatar en erreur | Extension `gd` manquante | Installer `php-gd`, redémarrer PHP |
| Repartir de zéro | — | `php artisan migrate:fresh --seed` |

### Conventions de travail

- Une branche par feature (`feat/…`), fusionnée dans `develop` en `--no-ff`.
- La CI fait tourner lint, build front et tests sur chaque pull request, avec
  l'anti-corrélation dans un job dédié et bloquant.
- Toute écriture sur une surface publique passe par un serializer : jamais de modèle rendu
  directement.
