# StudentTask — Cahier des charges (version 1)

## Objectif
Aider les étudiants à créer des projets, répartir les tâches,
attribuer les responsabilités et suivre les délais.

## Utilisateurs
- Chef de projet : crée le projet, gère les membres et les tâches.
- Membre : consulte ses tâches et met à jour leur statut.

## Fonctions de la V1
- Inscription, connexion, déconnexion
- Projets : créer, modifier, consulter, archiver
- Membres : ajouter / retirer des membres d'un projet
- Tâches : créer, modifier, supprimer, attribuer
- Priorité (Basse, Moyenne, Haute) et date limite
- Statuts : À faire, En cours, Terminé
- Tableau de bord avec indicateurs
- Recherche, filtres, tri
- Détection des tâches en retard

## Règles de gestion
1. E-mail unique par utilisateur.
2. Le créateur d'un projet en est le chef de projet.
3. Seul le chef de projet modifie le projet, gère les membres, crée
   et attribue les tâches.
4. Un membre ne voit que les projets auxquels il appartient.
5. Une tâche appartient à un seul projet et n'est attribuée qu'à un
   membre de ce projet (ou à personne).
6. Tâche en retard = date limite < aujourd'hui ET statut != Terminé.
7. Progression (%) = tâches terminées / total x 100 ; 0 % si aucune tâche.
8. Un projet archivé reste consultable mais n'accepte plus de tâches.

## Hors périmètre V1
E-mails de notification, pièces jointes, commentaires, calendrier,
export PDF, réinitialisation du mot de passe par e-mail.