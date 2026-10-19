<?php
// Fonctions d'accès aux données des tâches.
// Ce fichier utilise dateValide() de projets.php : incluez projets.php avant de l'appeler.

const STATUTS_TACHE   = ['a_faire', 'en_cours', 'termine'];
const PRIORITES_TACHE = ['basse', 'moyenne', 'haute'];

// Valide le formulaire d'une tâche. $projet doit contenir 'date_debut'.
// Retourne [erreurs, valeurs nettoyées].
function validerTache(array $donnees, array $projet, array $membres)
{
    $erreurs     = [];
    $titre       = trim($donnees['titre'] ?? '');
    $description = trim($donnees['description'] ?? '');
    $assigne     = trim($donnees['id_assigne'] ?? '');
    $priorite    = $donnees['priorite'] ?? '';
    $statut      = $donnees['statut'] ?? '';
    $limite      = trim($donnees['date_limite'] ?? '');

    if ($titre === '' || mb_strlen($titre) > 150) {
        $erreurs[] = 'Le titre est obligatoire (150 caractères maximum).';
    }
    if (mb_strlen($description) > 1000) {
        $erreurs[] = 'La description ne doit pas dépasser 1000 caractères.';
    }

    // La personne attribuée doit être membre du projet.
    $idAssigne = null;
    if ($assigne !== '') {
        $idsMembres = array_map('intval', array_column($membres, 'id_utilisateur'));
        if (in_array((int) $assigne, $idsMembres, true)) {
            $idAssigne = (int) $assigne;
        } else {
            $erreurs[] = 'La personne choisie ne fait pas partie du projet.';
        }
    }

    if (!in_array($priorite, PRIORITES_TACHE, true)) {
        $erreurs[] = 'La priorité choisie est invalide.';
        $priorite  = 'moyenne';
    }
    if (!in_array($statut, STATUTS_TACHE, true)) {
        $erreurs[] = 'Le statut choisi est invalide.';
        $statut    = 'a_faire';
    }

    if (!dateValide($limite)) {
        $erreurs[] = "La date limite n'est pas valide.";
    } elseif ($limite < $projet['date_debut']) {
        $erreurs[] = 'La date limite ne peut pas être antérieure au début du projet ('
                   . formaterDate($projet['date_debut']) . ').';
    }

    return [$erreurs, [
        'titre'       => $titre,
        'description' => $description,
        'id_assigne'  => $idAssigne,
        'priorite'    => $priorite,
        'statut'      => $statut,
        'date_limite' => $limite,
    ]];
}

// Toutes les tâches des projets dont l'utilisateur est membre.
function listerTachesUtilisateur(PDO $pdo, $idUtilisateur)
{
    $sql = 'SELECT t.id_tache, t.id_projet, t.id_assigne, t.titre, t.statut, t.priorite, t.date_limite,
                   p.titre AS titre_projet, u.nom AS nom_assigne, mp.role
            FROM taches t
            JOIN projets p ON p.id_projet = t.id_projet
            JOIN membres_projet mp ON mp.id_projet = t.id_projet AND mp.id_utilisateur = :uid
            LEFT JOIN utilisateurs u ON u.id_utilisateur = t.id_assigne
            ORDER BY t.date_limite ASC, t.id_tache ASC';
    $requete = $pdo->prepare($sql);
    $requete->execute([':uid' => $idUtilisateur]);
    return $requete->fetchAll();
}

// Retourne la tâche SEULEMENT si l'utilisateur est membre de son projet (sinon false).
function trouverTachePourUtilisateur(PDO $pdo, $idTache, $idUtilisateur)
{
    $sql = 'SELECT t.*, p.titre AS titre_projet, p.date_debut AS date_debut_projet,
                   p.archive AS projet_archive, mp.role
            FROM taches t
            JOIN projets p ON p.id_projet = t.id_projet
            JOIN membres_projet mp ON mp.id_projet = t.id_projet AND mp.id_utilisateur = :uid
            WHERE t.id_tache = :id';
    $requete = $pdo->prepare($sql);
    $requete->execute([':id' => $idTache, ':uid' => $idUtilisateur]);
    return $requete->fetch();
}

function creerTache(PDO $pdo, $idProjet, array $valeurs)
{
    $requete = $pdo->prepare(
        'INSERT INTO taches (id_projet, id_assigne, titre, description, statut, priorite, date_limite)
         VALUES (:projet, :assigne, :titre, :description, :statut, :priorite, :limite)'
    );
    $requete->execute([
        ':projet'      => $idProjet,
        ':assigne'     => $valeurs['id_assigne'],
        ':titre'       => $valeurs['titre'],
        ':description' => $valeurs['description'] === '' ? null : $valeurs['description'],
        ':statut'      => $valeurs['statut'],
        ':priorite'    => $valeurs['priorite'],
        ':limite'      => $valeurs['date_limite'],
    ]);
}

function modifierTache(PDO $pdo, $idTache, array $valeurs)
{
    $requete = $pdo->prepare(
        'UPDATE taches
         SET id_assigne = :assigne, titre = :titre, description = :description,
             statut = :statut, priorite = :priorite, date_limite = :limite
         WHERE id_tache = :id'
    );
    $requete->execute([
        ':assigne'     => $valeurs['id_assigne'],
        ':titre'       => $valeurs['titre'],
        ':description' => $valeurs['description'] === '' ? null : $valeurs['description'],
        ':statut'      => $valeurs['statut'],
        ':priorite'    => $valeurs['priorite'],
        ':limite'      => $valeurs['date_limite'],
        ':id'          => $idTache,
    ]);
}

function changerStatutTache(PDO $pdo, $idTache, $statut)
{
    $requete = $pdo->prepare('UPDATE taches SET statut = :statut WHERE id_tache = :id');
    $requete->execute([':statut' => $statut, ':id' => $idTache]);
}

function supprimerTache(PDO $pdo, $idTache)
{
    $requete = $pdo->prepare('DELETE FROM taches WHERE id_tache = :id');
    $requete->execute([':id' => $idTache]);
}