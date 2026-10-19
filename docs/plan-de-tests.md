# StudentTask — Plan de tests

Date des tests : ____/____/2026        Testeur : __________

## Préparation
Créez 3 comptes fictifs (inscription) :
- Chef : chef@exemple.test
- Membre 1 : membre1@exemple.test
- Membre 2 : membre2@exemple.test
Mot de passe de test (fictif) : MotDePasseTest1

Utilisez 2 navigateurs : une fenêtre normale (chef) et une fenêtre InPrivate (membres).
Notez le résultat de chaque test : OK ou KO (puis décrivez l'anomalie).

## A. Authentification
| N° | Scénario | Étapes | Résultat attendu | Résultat |
|----|----------|--------|------------------|----------|
| A1 | Connexion valide | Se connecter avec un compte existant | Tableau de bord affiché, nom visible dans le menu | OK|
| A2 | Mauvais mot de passe | Bon e-mail, mauvais mot de passe | Message « E-mail ou mot de passe incorrect » |OK |
| A3 | E-mail inconnu | E-mail jamais inscrit | Même message qu'en A2 | |
| A4 | E-mail déjà utilisé | S'inscrire deux fois avec le même e-mail | Message « Un compte existe déjà » | OK|
| A5 | Mot de passe invalide | Moins de 8 caractères, puis deux mots de passe différents | Message d'erreur, aucun compte créé | OK|
| A6 | Page protégée | Sans être connecté, ouvrir /dashboard.php | Redirection vers la connexion | OK|
| A7 | Déconnexion | Cliquer sur Déconnexion, puis rouvrir /dashboard.php | Retour à la connexion | OK|

## B. Projets
| N° | Scénario | Étapes | Résultat attendu | Résultat |
|----|----------|--------|------------------|----------|
| B1 | Création | Créer un projet avec des dates valides | Message vert, créateur = « Chef de projet » |OK |
| B2 | Consultation | Ouvrir le détail du projet | Dates, membres, tâches et progression affichés |OK |
| B3 | Modification | Changer le titre | Message vert, titre mis à jour | |
| B4 | Archivage | Cocher « Archiver » | Badge Archivé, bouton « + Nouvelle tâche » absent | |
| B5 | Création de tâche sur projet archivé | Ouvrir /taches/creer.php?projet=N | Message « projet archivé » |OK |
| B6 | Dates invalides (navigateur) | Date limite avant la date de début | Message bloquant avant l'envoi |OK |
| B7 | Dates invalides (serveur) | Console F12 : `document.querySelector('main form').noValidate = true`, puis envoyer des dates incohérentes | Message d'erreur rouge du serveur | OK|
| B8 | Champ obligatoire vide | Titre vide (même procédure qu'en B7) | Message d'erreur du serveur | OK|

## C. Membres
| N° | Scénario | Étapes | Résultat attendu | Résultat |
|----|----------|--------|------------------|----------|
| C1 | Plusieurs membres | Ajouter membre1 puis membre2 | 3 membres dans la liste | OK|
| C2 | Compte inconnu | Ajouter inconnu@exemple.test | Message « Aucun compte... » |OK |
| C3 | Doublon | Ajouter un membre déjà présent | Message « fait déjà partie du projet » |OK |
| C4 | Vue d'un membre simple | Page Membres avec membre1 | Liste visible, ni formulaire ni bouton Retirer | OK|
| C5 | Retrait | Retirer membre2 | Message vert ; ses tâches deviennent « Non attribuée » ; il ne voit plus le projet |OK |
| C6 | Retrait du chef | Tenter de retirer le chef | Impossible (aucun bouton) | OK|

## D. Tâches
| N° | Scénario | Étapes | Résultat attendu | Résultat |
|----|----------|--------|------------------|----------|
| D1 | Rattachement | Créer une tâche depuis le détail du projet | Tâche visible dans CE projet seulement | OK|
| D2 | Attribution valide | Attribuer à membre1 | « Attribuée à » = membre1 | OK|
| D3 | Attribution à un non-membre | F12 : changer la `value` d'une option du menu « Attribuée à » en 99999, puis envoyer | Message « ne fait pas partie du projet » |OK |
| D4 | Trois statuts | Passer une tâche à À faire, En cours, Terminé | Statut mis à jour à chaque fois |OK |
| D5 | Statut par un membre | Avec membre1 : changer le statut d'une tâche qui lui est attribuée | Accepté |OK |
| D6 | Statut d'une tâche d'autrui | Avec membre1 : tâche attribuée à un autre | Aucun sélecteur de statut | OK|
| D7 | Modification réservée au chef | Avec membre1 : ouvrir /taches/modifier.php?id=N | Message « Seul le chef de projet... » |OK |
| D8 | Suppression | Chef : Supprimer une tâche | Confirmation, puis message vert | OK|
| D9 | Date avant le début du projet | Date limite antérieure au début du projet | Message d'erreur | OK|

## E. Recherche, filtres, retards, progression
| N° | Scénario | Étapes | Résultat attendu | Résultat |
|----|----------|--------|------------------|----------|
| E1 | Recherche | Taper quelques lettres du titre ; « tache » pour « tâche » | Liste filtrée instantanément, accents ignorés |OK |
| E2 | Filtres | Projet, membre, statut, priorité, seuls puis combinés | Résultats conformes | |
| E3 | Tri | Date limite, puis Priorité | Ordre correct |OK |
| E4 | Aucun résultat | Taper zzzz | Message vide + lien Réinitialiser |OK |
| E5 | Retard : hier | Tâche à date d'hier, statut ≠ Terminé | « En retard », ligne rouge | OK|
| E6 | Retard : aujourd'hui | Tâche à la date du jour | PAS en retard |OK |
| E7 | Retard : terminée | Passer la tâche d'hier à Terminé | Plus en retard | |
| E8 | Progression 0/0 | Projet sans tâche | 0 % sans erreur | OK|
| E9 | Progression partielle | 1 tâche terminée sur 4 | 25 % |OK |
| E10 | Progression complète | 4/4 terminées | 100 % |OK |
| E11 | Cohérence | Comparer tableau de bord et pastilles de la page Tâches | Mêmes nombres |OK |

## F. Sécurité
| N° | Scénario | Étapes | Résultat attendu | Résultat |
|----|----------|--------|------------------|----------|
| F1 | Projet d'un autre | Avec un compte non membre : detail.php, modifier.php, membres/index.php?id=N | « Projet introuvable » | |
| F2 | Tâche d'un autre | Compte non membre : /taches/modifier.php?id=N | « Tâche introuvable » |OK |
| F3 | CSRF | F12 : supprimer le champ caché `csrf_token` d'un formulaire, puis envoyer | « Votre session a expiré », rien d'enregistré |OK |
| F4 | XSS | Titre de projet : `<b>test</b>` | Affiché tel quel en texte, jamais interprété |OK |
| F5 | Injection SQL | Connexion avec l'e-mail `' OR '1'='1` et un mot de passe quelconque | Refusé, aucune erreur SQL affichée | OK|
| F6 | Mot de passe haché | phpMyAdmin > utilisateurs | Colonne commençant par `$2y$` | OK|

## G. Interface et robustesse
| N° | Scénario | Étapes | Résultat attendu | Résultat |
|----|----------|--------|------------------|----------|
| G1 | Téléphone | F12 > mode appareil (Ctrl+Maj+M), largeur 375 px | Menu ☰ fonctionnel, tableau défilant, rien ne déborde |OK |
| G2 | Erreur de base | Arrêter MySQL dans XAMPP, ouvrir une page | Message générique, sans détail technique (passer APP_DEBUG à false pour ce test) | OK|

## Anomalies relevées
| N° | Symptôme | Cause probable | Correction | Test de vérification | Statut |
|----|----------|----------------|------------|----------------------|--------|
| | | | | | |