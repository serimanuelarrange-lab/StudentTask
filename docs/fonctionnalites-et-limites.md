# StudentTask : fonctionnalités réalisées et limites connues

## Fonctionnalités réalisées
| Domaine | Réalisé |
|---|---|
| Comptes | Inscription, connexion, déconnexion, mots de passe hachés |
| Réinitialisation du mot de passe | Code à 6 chiffres envoyé par e-mail (EmailJS), valable 15 minutes, 5 essais maximum, usage unique |
| E-mail de bienvenue | Envoyé après chaque inscription, avec le guide d'utilisation (EmailJS) |
| Projets | Création, liste, détail, modification, archivage |
| Membres | Ajout par e-mail, retrait, rôles chef de projet / membre |
| Tâches | Création, liste, modification, suppression, attribution, priorité, date limite |
| Statuts | À faire, En cours, Terminé : le chef ou le responsable de la tâche peut le changer, y compris en un clic depuis le tableau de bord (bouton « Terminer ») |
| Tableau de bord | Avancement global, projets actifs, tâches en retard, répartition par statut, graphique des échéances sur 14 jours, calendrier du mois, avancement de chaque projet, mes tâches à venir |
| Confort | Recherche instantanée (barre du haut et page Tâches), filtres, tri, messages d'état vide, confirmations, validation des formulaires |
| Sécurité | Requêtes préparées PDO, CSRF, XSS, contrôle d'accès côté serveur, validation serveur, clés EmailJS hors du dépôt |
| Interface | Thème sombre aux cartes pastel, palette vert/bleu-vert, logo et icône du site, responsive (ordinateur et téléphone) |
| Qualité | Plan de tests manuel de 59 scénarios (docs/plan-de-tests.md) |

## Limites connues
**Fonctionnelles**
- Pas de modification du profil ni de suppression de compte.
- Pas de suppression de projet : seul l'archivage existe.
- Un membre ne peut pas quitter un projet de lui-même.
- Le rôle de chef de projet ne peut pas être transféré.
- Le projet d'une tâche ne peut pas être changé après sa création.
- Pas de notification avant les échéances (e-mail ou à l'écran) : seuls la cloche et le tableau de bord signalent les retards, quand l'utilisateur ouvre le site.
- Pas de commentaires, de pièces jointes ni de tableau Kanban.
- L'application ne garde ni la date ni l'auteur d'un changement de statut.
- Les filtres et le tri de la page Tâches ne sont pas mémorisés d'une visite à l'autre.
- Pas de pagination : la liste des tâches s'affiche en entier.
- Les tâches des projets archivés restent comptées dans les totaux du tableau de bord.
- Le fuseau horaire est fixé à Africa/Abidjan pour tous les utilisateurs.

**E-mails et réinitialisation du mot de passe**
- L'adresse e-mail n'est **pas vérifiée** à l'inscription : quelqu'un peut inscrire l'adresse d'un tiers, qui recevra l'e-mail de bienvenue.
- L'offre gratuite d'EmailJS est limitée à 200 envois par mois et 2 modèles, partagés entre l'e-mail de bienvenue et le code de réinitialisation.
- Aucun e-mail de confirmation n'est envoyé après un changement de mot de passe.
- Après une réinitialisation, les sessions déjà ouvertes sur d'autres appareils restent actives jusqu'à leur fermeture.
- Pas de limite d'essais par adresse IP : seuls le délai de 60 secondes entre deux demandes et la limite de 5 essais par code s'appliquent.
- Un temps de réponse légèrement plus long quand l'adresse existe pourrait, en théorie, révéler qu'un compte existe ; un envoi en arrière-plan règlerait ce point.
- Le lien du bouton de l'e-mail pointe vers `SITE_URL` (`http://localhost/studenttask`), qui ne fonctionne que sur l'ordinateur où tourne XAMPP.
- Les e-mails utilisent le nom du site en texte coloré : les images SVG ne s'affichent pas dans la plupart des messageries.

**Techniques et sécurité**
- Pensée pour un usage local (XAMPP) : pas de HTTPS, donc le cookie de session n'est pas marqué « secure ».
- Pas de limitation du nombre de tentatives de connexion.
- La déconnexion se fait par un simple lien ; un site tiers pourrait déconnecter l'utilisateur.
- Lors de l'ajout d'un membre, le message indique si un compte existe pour l'adresse saisie.
- La base utilise le compte `root` sans mot de passe (réglage par défaut de XAMPP). Avant toute mise en ligne, il faudrait créer un utilisateur MySQL dédié avec un mot de passe.
- Le fichier `includes/config.php` contient les clés EmailJS : il ne doit jamais être publié (il est exclu par `.gitignore`).
- Les tests sont manuels : aucun test automatisé.

## Améliorations possibles
- Vérification de l'adresse e-mail à l'inscription
- Notifications avant les échéances et e-mail de confirmation de changement de mot de passe
- Historique des changements de statut (date et auteur)
- Commentaires et pièces jointes sur les tâches
- Vue Kanban
- Export PDF de l'avancement d'un projet
- Tests automatisés
- Mise en ligne sécurisée : HTTPS, utilisateur MySQL dédié, `APP_DEBUG` à `false`