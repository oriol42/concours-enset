# Concours ENSET Ebolowa : suivi

Plateforme d'inscription au concours d'entrée de l'ENSET d'Ebolowa. Thème WordPress classique, sans plugin, **projet et base séparés** de `inscription.ueb`. Repris de ce dernier : langage visuel, polices, GSAP, TCPDF.

## Installation (site neuf)

1. Créer une base MySQL dédiée, installer WordPress dessus (PHP 8.1+, extensions gd, exif, fileinfo).
2. Copier ce dossier dans `wp-content/themes/concours-enset` et activer le thème. Les tables `*_ceb_dossiers` et `*_ceb_journal`, le rôle « Scolarité concours » et les permaliens se créent seuls.
3. Se connecter puis ouvrir `/gestion/` > Réglages : année, dates, frais, compte, limites d'âge, contacts, centres, options.
4. Ajouter les personnes dans `/gestion/` > Équipe : chacun a son compte et son rôle.
5. PHP : `upload_max_filesize` et `post_max_size` à 8M au moins.
6. Serveur Nginx : interdire `/wp-content/uploads/ceb-concours/` (le `.htaccess` ne vaut que pour Apache). Les noms de fichiers sont aléatoires, mais la règle reste nécessaire.

## Adresses

`/` accueil, `/candidature/1` à `/6` formulaire, `/mon-dossier`, `/reprendre`, `/verifier/{jeton}` (QR), `/gestion` scolarité.

## Gestion : droits et files

| Rôle | Peut |
| --- | --- |
| Direction | Consulter dossiers et chiffres |
| Agent de scolarité | + vérifier les paiements, pointer les pièces |
| Responsable | + valider, renvoyer, rouvrir, exporter, lire le journal |
| Administrateur | + réglages et équipe |

Un dossier avance : paiements à vérifier, pièces attendues, prêts à valider, validés. Les droits sont revérifiés côté serveur à chaque action, et chaque décision porte le nom de son auteur.

## Règles métier

- Le dossier naît à l'étape identité, jamais à la visite. L'ancienne plateforme avait 2 668 inscriptions vides sur 4 524.
- Un seul dossier par CNI et par numéro de transaction, pour une session (clés uniques en base).
- Diplôme, filière et option suivent `CEB_LIAISONS` (Liaisions.xlsx), revérifiés côté serveur.
- Pas de mot de passe : reprise par numéro candidat ou téléphone, avec la date de naissance (6 essais, puis 15 minutes d'attente).
- Limite d'âge : avertit et signale à la scolarité, ne bloque pas. Fonctionnaires exemptés.
- Numéro : `UEB-{dernière année de la session}-{id sur 6 chiffres}`.
- Validation du dossier impossible sans paiement vérifié et sans toutes les pièces pointées. Toutes les décisions vont au journal.
- Dossier validé ou session close : plus de modification par le candidat. La fiche reste disponible.
- TCPDF supprime une image donnée par chemin : toujours la passer en mémoire avec `'@'`.

## À confirmer avec l'ENSET

- Arrêté de la session : dates, année, centres (l'arrêté 2026/2027 cité par les sites tiers donne Bafoussam, Bambili, Buea, Ngaoundéré, Yaoundé, Ebolowa ; le fichier reçu donne Bafoussam, Bertoua, Douala, Ebolowa, Garoua, Yaoundé), limites d'âge et date de référence.
- Compte et banque : valeurs reprises de l'arrêté 2022 et d'un site tiers pour 2026/2027.
- Paiement : le fichier Inscriptions contient un numéro de transaction, sans préciser le canal.
- Libellés déduits des codes : options EF, MA, IF, MK ; diplômes BAC_ITH, BAC_I, BT_*, LIC_*, BACH_ECO (Diplomes.xlsx n'a que 3 lignes et il n'y a pas de table Options).
- Option ITH rattachée à Génie Civil dans Liaisions.xlsx : à vérifier.
- Bornes des places par option : non fournies, donc non gérées.

## Tests

`tests/parcours.mjs` : parcours complet dans Chrome avec des candidats fictifs (39 vérifications, voir l'en-tête du fichier). Dernier passage le 2026-11-01 : tout est passé.

`tests/demo-donnees.php` : remplit une base de test avec des candidats fictifs (jamais en production).

Non testé : envoi réel du courriel (`wp_mail`), lecture du QR au téléphone, téléphone réel (mobile émulé seulement), serveur Apache ou Nginx (tests sous `php -S`).

## Reste à faire

- Photos propres à l'ENSET : celles de l'accueil sont le campus et des étudiants de l'Université d'Ebolowa, reprises de nos sites. Le site officiel de l'ENSET n'était pas joignable pour en récupérer.
- Déploiement sur le domaine choisi par l'ENSET.

Les fichiers Inscriptions et Utilisateurs contiennent de vraies données personnelles : ils ne doivent jamais entrer dans ce dépôt.
