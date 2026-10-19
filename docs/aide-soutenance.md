# StudentTask : aide à la soutenance

## Structure de l'exposé oral (10 minutes + questions)
| Durée | Contenu |
|-------|---------|
| 1 min | Problème et objectif |
| 2 min | Fonctionnement et modèle de données (un schéma, pas de code) |
| 5 min | Démonstration en direct (voir plan-de-demonstration.md) |
| 1 min | Sécurité et tests |
| 1 min | Limites, perspectives, conclusion |

**Phrase d'ouverture possible :** « Dans un travail de groupe, personne ne sait vraiment
qui fait quoi ni qui est en retard. StudentTask rend les responsabilités et les délais visibles. »

## Questions probables et réponses

**Pourquoi PHP et MySQL, sans framework ?**
Pour comprendre les mécanismes de base : sessions, sécurité, requêtes. XAMPP les installe
simplement, et la structure reste lisible pour un projet scolaire.

**Comment protégez-vous les mots de passe ?**
Ils sont hachés avec `password_hash()` et vérifiés avec `password_verify()`. La base ne
contient jamais le mot de passe en clair.

**Comment évitez-vous les injections SQL ?**
Toutes les requêtes utilisent des requêtes préparées PDO : les valeurs saisies ne sont jamais
collées dans le texte SQL.

**Qu'est-ce que le CSRF et que faites-vous ?**
C'est l'envoi d'un formulaire à votre insu depuis un autre site. Chaque formulaire contient un
jeton secret lié à la session, vérifié côté serveur.

**Et le XSS ?**
Tout texte affiché passe par une fonction d'échappement : une balise saisie dans un titre
s'affiche comme du texte et n'est jamais exécutée.

**Comment gérez-vous les droits d'accès ?**
Chaque requête vérifie que l'utilisateur est membre du projet, et le rôle de chef de projet
est contrôlé côté serveur avant toute modification. Masquer un bouton ne suffit pas : le
serveur refuse l'action de toute façon.

**Pourquoi une transaction à la création d'un projet ?**
Le projet et son chef sont créés ensemble : si l'un échoue, rien n'est enregistré. On évite
un projet sans responsable.

**Comment calculez-vous les retards et la progression ?**
Retard : date limite antérieure à aujourd'hui et tâche non terminée (une tâche due aujourd'hui
n'est pas en retard). Progression : terminées ÷ total × 100, avec 0 % si le projet n'a aucune
tâche. La même règle est utilisée partout dans l'application.

**Que devient une tâche quand on retire un membre ?**
Elle reste dans le projet et devient « non attribuée ». L'opération est faite dans une
transaction, donc aucune tâche ne reste attribuée à un non-membre.

**Pourquoi la validation JavaScript ET PHP ?**
JavaScript améliore le confort (message immédiat). PHP est la vraie protection, car le
navigateur peut être contourné.

**Pourquoi un graphique en SVG généré par PHP ?**
Aucune bibliothèque externe : l'application fonctionne hors connexion et reste légère.

**Comment avez-vous testé ?**
Avec un plan de 49 scénarios manuels : authentification, droits, règles de gestion, filtres,
retards, sécurité et affichage mobile. Il n'y a pas de tests automatisés, c'est une limite.

**Quelles sont les limites de votre projet ?**
Pas de réinitialisation de mot de passe, pas de notifications, usage local sans HTTPS, tests
manuels. Je les connais et je sais comment les traiter (voir les perspectives du rapport).

**Si vous deviez le mettre en ligne ?**
Hébergement avec HTTPS, utilisateur MySQL dédié avec mot de passe, `APP_DEBUG` à `false`,
limitation des tentatives de connexion et sauvegardes de la base.

**Qu'avez-vous appris ?**
À concevoir une base reliée par des clés étrangères, à sécuriser une application web et à
déboguer méthodiquement (lire le message d'erreur, localiser le fichier, corriger).

## Conseils pratiques
- Répétez la démonstration deux fois à voix haute avec un chronomètre.
- Si vous ne connaissez pas la réponse : « Je ne l'ai pas implémenté, voici comment je
  m'y prendrais » vaut mieux qu'une réponse inventée.
- Connaissez vos fichiers clés : `auth.php` (sessions, CSRF), `projets.php` (accès aux données),
  `taches.php` (règles des tâches), `statistiques.php` (calculs du tableau de bord).
- Parlez de vos erreurs corrigées : elles prouvent que vous comprenez votre code.