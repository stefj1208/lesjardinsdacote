# Les Jardins d'à Côté — plateforme d'achat groupé & vrac

Plateforme complète (Phase 1 du cahier des charges JDC-2026-V3 : semences & graines vrac), prête à installer sur votre hébergement **Hostinger (PHP 8 + MySQL)**, sans Composer ni ligne de commande.

---

## 1. Fonctionnement (version 2)

- **Groupes locaux** : « Les Jardins d'à Côté de Pins-Justaret », « … de Pinsaguel ». Mairies, entreprises, associations, jardins partagés ou habitants les proposent via le formulaire « Lancer un groupe » ; l'administrateur les valide.
- **3 statuts publics** : *À la recherche de bénévoles* → *Pré-ouverture* (dès 2 bénévoles, vote parmi 3 packs) → *Ventes ouvertes*.
- **Packs** : 3 packs par vente (graines, plants potagers, fruitiers, aromatiques), chacun avec ses variétés, quantités, photos et un prix total (≈ 30 €).
- **Vote** : à la date de fin du vote, le pack en tête est mis en vente et déposé automatiquement dans le panier de chaque votant.
- **Lots de 10** : rien n'est débité tant que le lot n'a pas 10 achats ; un lot complet est commandé et débité, puis un nouveau lot s'ouvre. À la fin de la vente, le lot incomplet est annulé (0 € débité).
- **Bénévoles** : préparation (quantités totales par variété, composition d'un pack, étiquettes A4), puis remise des packs par scan du QR code.
- **Photos** : référence Wikimedia Commons (`commons:Fichier.jpg`), adresse web ou envoi depuis le back-office. Les photos distantes sont copiées sur le serveur par la tâche planifiée, avec leurs crédits (page « Crédits photos »).
- **Mise à jour de la base** : automatique au premier chargement après un déploiement.

## 2. Mise en ligne sur Hostinger (≈ 20 minutes)

1. **Base de données** — hPanel → *Bases de données* → *Gestion* : créez une base MySQL et un utilisateur. Notez le nom de la base, l'utilisateur et le mot de passe.
2. **Envoi des fichiers** — hPanel → *Fichiers* → *Gestionnaire de fichiers*, dossier `domains/lesjardinsdacote.fr/` :
   - envoyez le dossier **`app`** à côté de `public_html` (pas dedans) ;
   - envoyez le **contenu** du dossier `public_html` dans `public_html`.
   - (Si vous ne pouvez pas placer `app` à côté, mettez-le dans `public_html` : il est protégé par `.htaccess`.)
3. **SSL** — hPanel → *Sécurité* → *SSL* : activez le certificat gratuit.
4. **Installation** — ouvrez `https://lesjardinsdacote.fr` : l'assistant s'affiche. Renseignez la base, votre compte administrateur et cochez (ou non) les données de démonstration. Il crée `app/config.php` puis se verrouille.
5. **Tâche planifiée (obligatoire)** — hPanel → *Avancé* → *Tâches Cron* → toutes les 5 minutes :
   ```
   /usr/bin/php /home/VOTRE_UTILISATEUR/domains/lesjardinsdacote.fr/app/cron/run.php
   ```
   Le chemin exact est affiché dans *Back-office → Paramètres*. Elle clôture les campagnes, déclenche les paiements, envoie les emails et les rappels.
6. **Emails** — créez une boîte `contact@lesjardinsdacote.fr` (hPanel → *Emails*), puis dans `app/config.php`, section `mail` : `smtp_host` = `smtp.hostinger.com`, port `465`, `ssl`, utilisateur et mot de passe de la boîte. Testez avec *Paramètres → M'envoyer un email de test*.

PHP 8.1 minimum (8.2 ou 8.3 conseillé), extensions standard Hostinger : PDO MySQL, cURL, GD, intl, mbstring.

---

## 3. Activer le paiement en ligne (Stripe)

1. Créez le compte Stripe **au nom de l'association** (SIRET, RIB, statuts).
2. Copiez les clés de **test** (`pk_test_…`, `sk_test_…`) dans `app/config.php`, section `stripe`.
3. Stripe → *Développeurs → Webhooks* → ajoutez l'URL affichée dans *Paramètres* (`/stripe/webhook`) avec les événements `payment_intent.succeeded` et `payment_intent.payment_failed`, puis copiez la clé `whsec_…` dans `webhook_secret`.
4. Testez une campagne de bout en bout avec les cartes de test Stripe : `4242 4242 4242 4242` (sans authentification), `4000 0027 6000 3184` (3-D Secure demandé).
5. Remplacez par les clés `pk_live_` / `sk_live_` et recréez le webhook en mode production.

Tant que les clés sont vides, tout fonctionne en **paiement au retrait** (montant final à encaisser lors de la distribution).

---

## 4. Premiers pas avant l'ouverture

1. *Paramètres* : nom, SIRET/RNA, adresse, directeur·rice de publication, contribution asso, freinte, coût sachet, TVA (0 % si l'association n'est pas assujettie).
2. *Paramètres* → **Supprimer les données de démo** (les tarifs de démo sont fictifs).
3. *Fournisseurs* : créez Agrosemens (franco, port, délai).
4. *Import CSV* : téléchargez le modèle, collez-y la grille **pro** Agrosemens 2027 (colonne `conditionnements` : `Sac 250 g=250=18,40|Sac 1 kg=1000=52`). Les campagnes brouillons sont générées avec leurs paliers ; ajustez-les avec le simulateur puis publiez.
   - Le catalogue 2027 en ligne est un catalogue feuilletable (images) : il ne peut pas être lu automatiquement. Le script `outils/import_agrosemens.py` sait lire des fiches produits de la boutique en ligne et produire ce CSV, mais la grille tarifaire pro reste la source à privilégier.
5. *Distributions* : créez la date de retrait ; *Créneaux bénévoles* : ateliers et permanences.
6. *Membres* : passez vos bénévoles au rôle « Bénévole ».
7. Faites relire les **CGV et le mandat d'achat groupé** (page `/cgv`), les mentions légales et la page données personnelles.

## 5. Cycle d'une campagne

| Étape | Qui | Où |
|---|---|---|
| Import / création + paliers | Admin | Back-office → Campagnes |
| Engagements, la plante pousse | Acheteurs | Site |
| Clôture, prix final, prélèvement | Automatique (cron) | — |
| Commande au fournisseur | Admin | Bon de commande → « Commande passée » |
| Réception (n° de lot), pesée, étiquettes | Bénévoles | Espace bénévoles → Atelier |
| « Tout est ensaché » → Pass Retrait envoyé | Bénévoles | Atelier |
| Scan du QR, remise, encaissement | Bénévoles | Distribution |

---

## 6. Points à valider de votre côté

- **Réglementation semences** : vente au sein d'un groupement d'achat (mandat accepté dans les CGV, case obligatoire à l'engagement) ou déclaration de reconditionneur auprès du SEMAE — à confirmer avec le SEMAE / la DRAAF.
- **TVA et fiscalité** de l'association (franchise ou non), **assurance** des ateliers.
- Les textes juridiques fournis sont des modèles : une relecture par un juriste est recommandée.

## 7. Choix techniques et limites

- **PHP/MySQL sans dépendance** pour fonctionner sur l'hébergement mutualisé (le cahier des charges suggérait Next.js/PostgreSQL, non disponibles sur cette offre). Requêtes préparées, verrous `SELECT … FOR UPDATE` sur les réservations, protection CSRF, mots de passe hachés, limitation des tentatives de connexion, polices et scanner QR hébergés localement (aucun appel à Google Fonts : conforme RGPD).
- **Temps réel** : actualisation toutes les 20 secondes (les WebSockets ne sont pas possibles en mutualisé).
- **Étiquettes et factures** : pages A4 à imprimer ou « Enregistrer en PDF » depuis le navigateur.
- **Non inclus** : notifications SMS/WhatsApp automatiques (fournisseur payant à choisir, ex. Brevo), maquettes Figma, et les écrans spécifiques des phases 2 à 4 (plantes, terreau au litre, seconde main). Le modèle de données gère déjà les 6 unités et les 3 modes de paliers ; ces phases demanderont surtout des adaptations d'interface.
- **Sauvegardes** : activez les sauvegardes quotidiennes Hostinger (base + fichiers). Export manuel : hPanel → phpMyAdmin → Exporter.

## 8. Organisation des fichiers

```
app/                 code (hors du web)
  bootstrap.php      démarrage
  config.php         généré à l'installation (identifiants, clés Stripe, SMTP)
  controllers/       pages : front, compte, panier, bénévoles, admin, installation
  lib/               moteur de prix, campagnes, commandes, Stripe, emails, QR codes…
  views/             gabarits HTML
  sql/schema.sql     structure de la base
  cron/run.php       tâche planifiée
  storage/           journaux et cache (doit être accessible en écriture)
public_html/         racine web : index.php, .htaccess, assets (CSS, JS, polices, images)
outils/              script d'extraction des fiches Agrosemens (à lancer sur votre ordinateur)
```
