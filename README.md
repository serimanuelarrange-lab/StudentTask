# StudentTask

Application web de gestion des tâches et projets étudiants.

**Problème résolu :** les étudiants ont du mal à répartir les tâches, à respecter
les délais et à suivre l'avancement des travaux de groupe.

## Fonctionnalités
- Inscription, connexion et déconnexion sécurisées
- Réinitialisation du mot de passe par un code à 6 chiffres envoyé par e-mail (EmailJS)
- E-mail de bienvenue avec guide d'utilisation après chaque inscription (EmailJS)
- Création, modification, consultation et archivage de projets
- Ajout et retrait de membres dans un projet
- Création, modification, suppression et attribution de tâches
- Priorité (basse, moyenne, haute) et date limite
- Statuts : À faire, En cours, Terminé (le responsable d'une tâche la termine en un clic)
- Tableau de bord : avancement global, indicateurs, progression de chaque projet,
  graphique des échéances et calendrier
- Recherche instantanée, filtres et tri des tâches
- Détection des tâches en retard
- Interface en français au thème sombre, avec logo, utilisable sur ordinateur et sur téléphone

La liste détaillée, avec les limites connues, se trouve dans
[docs/fonctionnalites-et-limites.md](docs/fonctionnalites-et-limites.md).

## Technologies
HTML5, CSS3, JavaScript, PHP (PDO) et MySQL. Les e-mails passent par l'API REST
d'EmailJS, appelée depuis PHP (cURL). Environnement local : XAMPP.

## Prérequis
- XAMPP avec Apache, PHP 7.4 ou plus (extension cURL activée) et MySQL/MariaDB
- Un navigateur récent
- (Facultatif) un compte gratuit EmailJS pour les e-mails
- (Conseillé) Visual Studio Code

Développé et testé avec XAMPP sous Windows, PHP 8.0.30 et MariaDB 10.4.32.

## Installation en local (Windows)
1. **Installer XAMPP**, puis ouvrir le *XAMPP Control Panel*.
2. **Copier le dossier du projet** : il doit se nommer `studenttask` et se trouver dans
   `C:\xampp\htdocs\`. Le fichier `index.php` doit donc être dans `C:\xampp\htdocs\studenttask\`.
3. **Démarrer Apache et MySQL** avec les boutons *Start* du XAMPP Control Panel
   (les deux lignes doivent passer au vert).
4. **Créer la base de données** :
   - ouvrir `http://localhost/phpmyadmin` ;
   - cliquer sur l'onglet **Importer** (sans sélectionner de base à gauche) ;
   - choisir le fichier `sql/studenttask.sql` et cliquer sur **Importer**.
   Une base `studenttask` avec 5 tables est créée. Le script ne supprime rien.
5. **Configurer l'application** :
   - copier le fichier `includes/config.example.php` et nommer la copie `includes/config.php` ;
   - ouvrir `includes/config.php` et vérifier `BASE_URL` (`/studenttask`) et les réglages
     de base de données. Avec XAMPP par défaut, rien à changer : utilisateur `root`, mot de passe vide.
6. **Ouvrir l'application** : `http://localhost/studenttask/`

## E-mails avec EmailJS (facultatif)
Sans configuration, l'application fonctionne normalement : aucun e-mail n'est envoyé et
aucune erreur n'est affichée à l'utilisateur. Pour activer l'e-mail de bienvenue et la
réinitialisation du mot de passe :
1. Créer un compte gratuit sur [emailjs.com](https://www.emailjs.com), connecter un service
   d'envoi (Gmail par exemple) et noter le **Service ID**.
2. Créer **deux modèles** à partir des fichiers `docs/email-bienvenue.html` et
   `docs/email-reinitialisation.html`, puis noter leurs **Template ID**.
3. Dans *Account > General*, noter la **Public Key**. Dans *Account > Security*, activer la
   clé privée et l'API pour les applications hors navigateur, puis noter la **Private Key**.
4. Renseigner dans `includes/config.php` : `EMAILJS_SERVICE_ID`, `EMAILJS_TEMPLATE_ID`
   (bienvenue), `EMAILJS_TEMPLATE_RESET_ID` (réinitialisation), `EMAILJS_PUBLIC_KEY`,
   `EMAILJS_PRIVATE_KEY` et `SITE_URL`.
5. **Ne jamais publier `includes/config.php`** : il contient les clés. Il est exclu par `.gitignore`.

Vérifiez les limites de l'offre gratuite sur le site d'EmailJS (200 envois par mois et
2 modèles au moment de la rédaction).

## Premiers pas
1. Cliquer sur **Créer un compte** et s'inscrire avec un nom, un e-mail et un mot de passe de 8 caractères minimum.
2. Se connecter, puis créer un premier projet.
3. Pour tester le travail d'équipe, créer un second compte (dans une fenêtre de navigation privée)
   et l'ajouter au projet depuis la page **Membres**.

Le mode d'emploi complet est dans [docs/guide-utilisation.md](docs/guide-utilisation.md).

## Structure du projet
```
studenttask/
├── index.php, login.php, inscription.php, logout.php, dashboard.php
├── mot-de-passe-oublie.php, reinitialiser.php
├── projets/    index, creer, detail, modifier
├── taches/     index, creer, modifier, statut, supprimer
├── membres/    index
├── includes/   config, database, auth, fonctions, projets, taches, statistiques,
│               email, reinitialisation, formulaire_tache, header, footer,
│               header_auth, footer_auth
├── assets/     css/style.css, js/app.js, images/ (logo)
├── sql/        studenttask.sql
└── docs/       cahier des charges, conception, plan de tests, guides, modèles d'e-mails
```

## Sécurité mise en place
- Requêtes SQL préparées (PDO) contre les injections SQL
- Mots de passe hachés avec `password_hash()`, jamais stockés en clair
- Sessions protégées (cookie `HttpOnly`, identifiant renouvelé à la connexion)
- Jeton CSRF sur tous les formulaires qui modifient des données
- Échappement de tout texte affiché (protection contre le XSS)
- Validation des données côté serveur, en plus de la validation du navigateur
- Contrôle d'accès côté serveur : un utilisateur ne voit que les projets dont il est membre,
  et seul le chef de projet peut les gérer
- Code de réinitialisation : stocké haché, valable 15 minutes, à usage unique, 5 essais
  maximum, délai de 60 secondes entre deux demandes, message identique que le compte existe ou non
- Clés EmailJS conservées côté serveur, hors du dépôt de code
- Messages d'erreur génériques pour l'utilisateur (détails techniques réservés au mode développement)

## Tests
Le plan de tests manuel (59 scénarios) se trouve dans [docs/plan-de-tests.md](docs/plan-de-tests.md).

## Dépannage rapide
| Problème | Solution |
|---|---|
| Erreur 404 | Vérifier que le dossier s'appelle `studenttask` et qu'il est dans `htdocs` |
| Page sans couleurs | Vérifier `BASE_URL` dans `includes/config.php` |
| « Le service est momentanément indisponible » | Démarrer MySQL dans XAMPP et vérifier que la base `studenttask` existe |
| Erreur « Access denied » | Vérifier `DB_USER` et `DB_PASS` dans `includes/config.php` |
| Apache ne démarre pas (port 80 occupé) | Fermer l'application qui utilise le port (Skype, IIS...) ou changer le port d'Apache |
| Aucun e-mail reçu | Vérifier les 5 valeurs EmailJS de `config.php`, le dossier des courriers indésirables et l'historique du tableau de bord EmailJS |
| Erreur de certificat SSL à l'envoi d'e-mails | Renseigner `curl.cainfo` dans `C:\xampp\php\php.ini` avec le fichier `curl-ca-bundle.crt` d'Apache, puis redémarrer Apache |
| Table `reinitialisations_mdp` introuvable | Exécuter le bloc `CREATE TABLE` correspondant de `sql/studenttask.sql` dans phpMyAdmin |

## Auteur
Projet réalisé dans un cadre scolaire par : _(votre nom)_