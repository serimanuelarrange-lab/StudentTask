<?php
// Fonctions d'accès aux données des projets.

// Vrai si $date est une date réelle au format AAAA-MM-JJ.
function dateValide($date)
{
    $d = DateTime::createFromFormat('Y-m-d', (string) $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Valide les champs du formulaire projet. Retourne [erreurs, valeurs nettoyées].
function validerProjet(array $donnees)
{
    $erreurs     = [];
    $titre       = trim($donnees['titre'] ?? '');
    $description = trim($donnees['description'] ?? '');
    $debut       = trim($donnees['date_debut'] ?? '');
    $limite      = trim($donnees['date_limite'] ?? '');

    if ($titre === '' || mb_strlen($titre) > 150) {
        $erreurs[] = 'Le titre est obligatoire (150 caractères maximum).';
    }
    if (mb_strlen($description) > 1000) {
        $erreurs[] = 'La description ne doit pas dépasser 1000 caractères.';
    }
    if (!dateValide($debut)) {
        $erreurs[] = "La date de début n'est pas valide.";
    }
    if (!dateValide($limite)) {
        $erreurs[] = "La date limite n'est pas valide.";
    }
    if (dateValide($debut) && dateValide($limite) && $limite < $debut) {
        $erreurs[] = 'La date limite ne peut pas être antérieure à la date de début.';
    }

    return [$erreurs, [
        'titre'       => $titre,
        'description' => $description,
        'date_debut'  => $debut,
        'date_limite' => $limite,
    ]];
}

// Liste les projets d'un utilisateur, avec le nombre de tâches (total et terminées).
function listerProjetsUtilisateur(PDO $pdo, $idUtilisateur)
{
    $sql = 'SELECT p.id_projet, p.titre, p.description, p.date_debut, p.date_limite, p.archive,
                   mp.role,
                   COUNT(t.id_tache) AS total,
                   COALESCE(SUM(t.statut = \'termine\'), 0) AS termine
            FROM projets p
            JOIN membres_projet mp ON mp.id_projet = p.id_projet AND mp.id_utilisateur = :uid
            LEFT JOIN taches t ON t.id_projet = p.id_projet
            GROUP BY p.id_projet, p.titre, p.description, p.date_debut, p.date_limite, p.archive, mp.role
            ORDER BY p.archive ASC, p.date_limite ASC';
    $requete = $pdo->prepare($sql);
    $requete->execute([':uid' => $idUtilisateur]);
    return $requete->fetchAll();
}

// Retourne le projet SEULEMENT si l'utilisateur en est membre (sinon false).
// C'est le contrôle d'accès : un non-membre ne peut pas voir le projet.
function trouverProjetPourUtilisateur(PDO $pdo, $idProjet, $idUtilisateur)
{
    $sql = 'SELECT p.*, mp.role
            FROM projets p
            JOIN membres_projet mp ON mp.id_projet = p.id_projet
            WHERE p.id_projet = :id AND mp.id_utilisateur = :uid';
    $requete = $pdo->prepare($sql);
    $requete->execute([':id' => $idProjet, ':uid' => $idUtilisateur]);
    return $requete->fetch();
}

function listerMembresProjet(PDO $pdo, $idProjet)
{
    $sql = 'SELECT u.id_utilisateur, u.nom, u.email, mp.role
            FROM membres_projet mp
            JOIN utilisateurs u ON u.id_utilisateur = mp.id_utilisateur
            WHERE mp.id_projet = :id
            ORDER BY (mp.role = \'chef_projet\') DESC, u.nom ASC';
    $requete = $pdo->prepare($sql);
    $requete->execute([':id' => $idProjet]);
    return $requete->fetchAll();
}

function listerTachesProjet(PDO $pdo, $idProjet)
{
    $sql = 'SELECT t.id_tache, t.titre, t.statut, t.priorite, t.date_limite, u.nom AS nom_assigne
            FROM taches t
            LEFT JOIN utilisateurs u ON u.id_utilisateur = t.id_assigne
            WHERE t.id_projet = :id
            ORDER BY t.date_limite ASC';
    $requete = $pdo->prepare($sql);
    $requete->execute([':id' => $idProjet]);
    return $requete->fetchAll();
}

// ---------- Gestion des membres ----------

function trouverUtilisateurParEmail(PDO $pdo, $email)
{
    $requete = $pdo->prepare('SELECT id_utilisateur, nom FROM utilisateurs WHERE email = :email');
    $requete->execute([':email' => $email]);
    return $requete->fetch();
}

function estMembreProjet(PDO $pdo, $idProjet, $idUtilisateur)
{
    $requete = $pdo->prepare(
        'SELECT 1 FROM membres_projet WHERE id_projet = :projet AND id_utilisateur = :utilisateur'
    );
    $requete->execute([':projet' => $idProjet, ':utilisateur' => $idUtilisateur]);
    return (bool) $requete->fetchColumn();
}

function ajouterMembreProjet(PDO $pdo, $idProjet, $idUtilisateur)
{
    $requete = $pdo->prepare(
        "INSERT INTO membres_projet (id_projet, id_utilisateur, role) VALUES (:projet, :utilisateur, 'membre')"
    );
    $requete->execute([':projet' => $idProjet, ':utilisateur' => $idUtilisateur]);
}

// Retire un membre (jamais le chef) et désattribue ses tâches du projet.
// Retourne true si un membre a bien été retiré.
function retirerMembreProjet(PDO $pdo, $idProjet, $idUtilisateur)
{
    try {
        $pdo->beginTransaction();

        $suppression = $pdo->prepare(
            "DELETE FROM membres_projet
             WHERE id_projet = :projet AND id_utilisateur = :utilisateur AND role = 'membre'"
        );
        $suppression->execute([':projet' => $idProjet, ':utilisateur' => $idUtilisateur]);
        $retire = $suppression->rowCount() > 0;

        if ($retire) {
            $liberation = $pdo->prepare(
                'UPDATE taches SET id_assigne = NULL WHERE id_projet = :projet AND id_assigne = :utilisateur'
            );
            $liberation->execute([':projet' => $idProjet, ':utilisateur' => $idUtilisateur]);
        }

        $pdo->commit();
        return $retire;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}