# StudentTask : gestion des tâches et projets étudiants

**Auteur :** SERI YANN MANUEL  **Établissement :** IIPEA
**Encadrant :** SERI YANN MANUEL **Année :** 2026

---

## 1. Introduction
Les travaux de groupe posent trois difficultés récurrentes aux étudiants : répartir les
tâches, respecter les délais et suivre l'avancement. Les échanges se font par messages
dispersés, sans vue d'ensemble ni responsabilités clairement attribuées.

**StudentTask** est une application web qui centralise les projets, les membres, les
tâches et les échéances d'un groupe.

## 2. Objectifs
- Créer, modifier, consulter et archiver des projets
- Ajouter des membres et leur attribuer des tâches
- Définir priorités et dates limites, suivre trois statuts (À faire, En cours, Terminé)
- Offrir un tableau de bord chiffré, des recherches et des filtres
- Identifier automatiquement les tâches en retard
- Garantir un accès sécurisé, avec réinitialisation du mot de passe par e-mail
- Proposer une interface utilisable sur ordinateur et sur téléphone

## 3. Analyse des besoins
### 3.1 Utilisateurs
- **Chef de projet** : crée le projet, gère les membres et les tâches
- **Membre** : consulte le projet et met à jour le statut de ses tâches

### 3.2 Règles de gestion
1. L'adresse e-mail d'un compte est unique.
2. Le créateur d'un projet en est le chef de projet.
3. Seul le chef de projet gère les membres, crée, modifie, supprime et attribue des tâches.
4. Un utilisateur ne voit que les projets dont il est membre.
5. Une tâche appartient à un seul projet et n'est attribuée qu'à un membre de ce projet.
6. Une tâche est **en retard** si sa date limite est antérieure à aujourd'hui et si elle n'est
   pas terminée.
7. Progression (%) = tâches terminées ÷ nombre total de tâches × 100 (0 % sans tâche).
8. Un projet archivé reste consultable mais n'accepte plus de nouvelles tâches.
9. La date limite d'une tâche ne précède pas la date de début du projet.
10. Un code de réinitialisation est valable 15 minutes, utilisable une seule fois, avec
    5 essais maximum.
11. L'application ne révèle jamais si une adresse e-mail possède un compte lors d'une
    demande de réinitialisation.

## 4. Conception
### 4.1 Architecture
Application web en PHP sans framework, structurée en pages (projets, tâches, membres) et en
fichiers réutilisables (`includes/`) : connexion à la base, authentification, fonctions
d'accès aux données, envoi d'e-mails, en-tête et pied de page communs. Le navigateur affiche
les pages générées par PHP ; JavaScript ajoute les filtres instantanés et la validation des
formulaires. Les e-mails sont envoyés par le service EmailJS, appelé depuis PHP.

### 4.2 Modèle de données
| Table | Champs principaux | Rôle |
|-------|-------------------|------|
| utilisateurs | id_utilisateur, nom, email (unique), mot_de_passe_hash | Comptes |
| projets | id_projet, titre, description, date_debut, date_limite, archive, id_createur | Projets |
| membres_projet | id_projet, id_utilisateur, role | Appartenance (clé primaire composée) |
| taches | id_tache, id_projet, id_assigne (peut être vide), titre, statut, priorite, date_limite | Tâches |
| reinitialisations_mdp | id_reinit, id_utilisateur, code_hash, date_expiration, tentatives, utilise | Codes de réinitialisation |

Les clés étrangères empêchent les données orphelines : supprimer un projet supprime ses
tâches et ses liens de membres ; supprimer un utilisateur laisse ses tâches non attribuées.

## 5. Réalisation
### 5.1 Technologies
HTML5, CSS3, JavaScript, PHP (PDO), MySQL, EmailJS (API REST), XAMPP et Visual Studio Code.

### 5.2 Fonctionnalités réalisées
Inscription et connexion, e-mail de bienvenue, réinitialisation du mot de passe par code,
gestion des projets, des membres et des tâches, tableau de bord (avancement global, projets
actifs, retards, répartition, graphique des échéances, calendrier), recherche, filtres et tri,
bouton « Terminer » pour le responsable d'une tâche, thème sombre avec logo, interface responsive.

### 5.3 Sécurité
| Risque | Protection mise en place |
|--------|--------------------------|
| Injection SQL | Requêtes préparées (PDO) |
| Mots de passe volés | Hachage avec `password_hash()` |
| Faux formulaires (CSRF) | Jeton secret vérifié à chaque envoi |
| Injection de code dans l'affichage (XSS) | Échappement de tout texte affiché |
| Accès aux données d'autrui | Contrôle du rôle et de l'appartenance au projet côté serveur |
| Code de réinitialisation deviné | Code haché, 15 minutes, usage unique, 5 essais, délai de 60 secondes entre deux demandes |
| Découverte des comptes existants | Message identique que l'adresse existe ou non |
| Fuite des clés EmailJS | Clés côté serveur dans un fichier exclu du dépôt de code |
| Fuite d'informations | Messages d'erreur génériques pour l'utilisateur |

## 6. Tests
Un plan de **59 scénarios** (authentification, projets, membres, tâches, filtres, retards,
progression, sécurité, affichage mobile, réinitialisation du mot de passe) a été rédigé :
`docs/plan-de-tests.md`.
Résultats : 45 (à compléter avec le nombre de tests réussis et les anomalies corrigées).

## 7. Difficultés rencontrées et solutions
| Difficulté | Cause | Solution |
|------------|-------|----------|
| Erreur 404 sur une page | Fichier créé dans le mauvais dossier | Vérification de l'arborescence avec l'explorateur |
| Menu affiché deux fois | Le pied de page contenait une copie de l'en-tête | Réduction du pied de page à ses quelques lignes |
| Fonction introuvable au tableau de bord | Fichier de calculs incomplet | Remplacement complet du fichier |
| Interface en doublon après modification | Collage partiel d'un fichier | Remplacement du fichier entier plutôt que retouche ligne par ligne |

## 8. Limites et perspectives
**Limites :** pas de vérification de l'adresse e-mail à l'inscription, pas de suppression de
projet (archivage seulement), pas de notifications avant les échéances, offre gratuite d'EmailJS
limitée à 200 envois par mois, tests manuels uniquement, usage local (HTTP).
**Perspectives :** vérification des adresses e-mail, notifications avant les échéances, historique
des changements de statut, commentaires et pièces jointes, vue Kanban, export PDF, tests
automatisés, mise en ligne sécurisée (HTTPS, utilisateur MySQL dédié).

## 9. Conclusion
StudentTask répond aux trois problèmes de départ : les responsabilités sont attribuées, les
échéances sont visibles et les retards sont détectés automatiquement. Le projet m'a permis
de pratiquer la conception d'une base de données, la programmation sécurisée en PHP,
l'intégration d'un service d'e-mails et le développement d'une interface adaptable.

## Annexes
- Captures d'écran : connexion, tableau de bord, liste des tâches, détail d'un projet, version mobile
- Script SQL : `sql/studenttask.sql`
- Plan de tests : `docs/plan-de-tests.md`
- Guide d'utilisation : `docs/guide-utilisation.md`
- Modèles d'e-mails : `docs/email-bienvenue.html`, `docs/email-reinitialisation.html`