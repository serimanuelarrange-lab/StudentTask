1. Parcours utilisateur

Parcours A : le chef de projet

Il s’inscrit puis se connecte, et arrive sur le tableau de bord (vide au début).
Il clique sur « Nouveau projet », remplit le formulaire et valide.
Dans le détail du projet, il ajoute des membres (par e-mail, ils doivent déjà avoir un compte).
Il crée des tâches, les attribue, fixe priorité et date limite.
Il suit la barre de progression et les tâches en retard.
À la fin du travail, il archive le projet.

Parcours B : le membre

Il se connecte et voit ses projets et ses tâches attribuées.
Il ouvre une tâche, la passe de « À faire » à « En cours », puis à « Terminé ».
Il utilise la recherche et les filtres pour retrouver une tâche.
2. Maquettes textuelles

Connexion (login.php)

+----------------------------------+
|        StudentTask (logo)        |
|  E-mail        [______________]  |
|  Mot de passe  [______________]  |
|  [ Se connecter ]                |
|  Pas de compte ? S'inscrire      |
+----------------------------------+

Tableau de bord (dashboard.php)

[Barre latérale : Tableau de bord | Projets | Tâches | Membres | Déconnexion]

Bonjour, <nom>
+---------+---------+---------+---------+---------+
| Projets | À faire | En cours| Terminé | En      |
| actifs  |    n    |    n    |    n    | retard  |
|    n    |         |         |         |    n    |
+---------+---------+---------+---------+---------+
Mes projets                       Tâches en retard
- Projet A  [=====-----] 50 %     - Tâche X (échéance 12/10)
- Projet B  [==--------] 20 %     - Tâche Y (échéance 14/10)

Liste des tâches (taches/index.php)

Recherche [__________]  Projet [v]  Membre [v]  Statut [v]  Priorité [v]
Trier par [Date limite v]                      [ + Nouvelle tâche ]

| Titre | Projet | Assigné à | Priorité | Date limite | Statut |
| ...   | ...    | ...       | Haute    | 20/10/2026  | En cours |
(état vide : « Aucune tâche ne correspond à votre recherche. »)

Détail d’un projet (projets/detail.php) : titre et dates en haut, barre de progression, bloc « Membres » (avec bouton Ajouter) et bloc « Tâches du projet ».

Formulaires : le projet demande titre, description, date de début et date limite. La tâche demande titre, description, projet, assigné à, priorité, statut et date limite. Chaque champ obligatoire est marqué d’un astérisque.

3. Modèle de données

Je garde les 4 tables prévues, avec deux ajouts justifiés :

PROJETS.archive (0 ou 1) : indispensable pour la règle d’archivage.
MEMBRES_PROJET.role : valeurs chef_projet ou membre, pour appliquer les droits.
UTILISATEURS (id_utilisateur PK, nom, email UNIQUE, mot_de_passe_hash, date_creation)

PROJETS (id_projet PK, titre, description, date_debut, date_limite,
         archive, id_createur FK -> UTILISATEURS, date_creation)

MEMBRES_PROJET (id_projet FK -> PROJETS, id_utilisateur FK -> UTILISATEURS,
                role, date_ajout, PK (id_projet, id_utilisateur))

TACHES (id_tache PK, id_projet FK -> PROJETS,
        id_assigne FK -> UTILISATEURS (peut être vide),
        titre, description, statut, priorite, date_limite, date_creation)

Relations

Un utilisateur crée plusieurs projets (1,N).
Un projet a plusieurs membres et un utilisateur peut être dans plusieurs projets (N,N, via MEMBRES_PROJET).
Un projet contient plusieurs tâches (1,N).
Une tâche est attribuée à au plus un utilisateur (0,1).

Comportements à la suppression (prévus pour éviter les orphelins)

Supprimer un projet supprime ses tâches et ses liaisons de membres (ON DELETE CASCADE).
Retirer un membre d’un projet remet à vide id_assigne de ses tâches. C’est fait dans le PHP, car MySQL ne sait pas exprimer seul « assigné à un membre de ce projet ».
4. Vérification de cohérence
Règle	Où elle est appliquée
E-mail unique	Contrainte UNIQUE en base + test PHP avec message clair
Assigné = membre du projet	Vérification PHP avant l’enregistrement
Statuts et priorités valides	ENUM en base + liste fermée côté PHP
Tâche en retard	Calculée à l’affichage : date_limite < aujourd'hui et statut != Terminé. Pas de colonne stockée, donc jamais périmée
Progression	Calculée par requête, 0 % si aucune tâche
Droits	Fonction PHP appelée au début de chaque page sensible

Aucune contradiction détectée entre les règles et le modèle.