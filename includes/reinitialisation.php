<?php
// Réinitialisation du mot de passe par code envoyé par e-mail.
require_once __DIR__ . '/email.php';

const DUREE_CODE_MINUTES    = 15;  // durée de validité du code
const MAX_TENTATIVES_CODE   = 5;   // essais autorisés par code
const DELAI_RENVOI_SECONDES = 60;  // délai minimum entre deux demandes

// Crée un code et l'envoie par e-mail.
// Ne révèle jamais si l'adresse existe : retourne [envoyé ?, raison technique].
function demanderReinitialisation(PDO $pdo, $email)
{
    // Ménage : on supprime les codes expirés depuis plus de 24 h.
    $nettoyage = $pdo->prepare('DELETE FROM reinitialisations_mdp WHERE date_expiration < :limite');
    $nettoyage->execute([':limite' => date('Y-m-d H:i:s', time() - 86400)]);

    $requete = $pdo->prepare('SELECT id_utilisateur, nom FROM utilisateurs WHERE email = :email');
    $requete->execute([':email' => $email]);
    $utilisateur = $requete->fetch();

    if (!$utilisateur) {
        return [false, '']; // adresse inconnue : on ne dit rien
    }
    $idUtilisateur = (int) $utilisateur['id_utilisateur'];

    // Délai minimum entre deux demandes.
    $dernier = $pdo->prepare(
        'SELECT date_creation FROM reinitialisations_mdp
         WHERE id_utilisateur = :id ORDER BY id_reinit DESC LIMIT 1'
    );
    $dernier->execute([':id' => $idUtilisateur]);
    $ligne = $dernier->fetch();
    if ($ligne && time() - strtotime($ligne['date_creation']) < DELAI_RENVOI_SECONDES) {
        return [false, 'Un code vient d\'être envoyé : patientez ' . DELAI_RENVOI_SECONDES . ' secondes avant d\'en demander un autre.'];
    }

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    try {
        $pdo->beginTransaction();

        // Les anciens codes encore valides sont annulés.
        $annulation = $pdo->prepare(
            'UPDATE reinitialisations_mdp SET utilise = 1 WHERE id_utilisateur = :id AND utilise = 0'
        );
        $annulation->execute([':id' => $idUtilisateur]);

        $ajout = $pdo->prepare(
            'INSERT INTO reinitialisations_mdp (id_utilisateur, code_hash, date_creation, date_expiration)
             VALUES (:id, :hash, :creation, :expiration)'
        );
        $ajout->execute([
            ':id'         => $idUtilisateur,
            ':hash'       => password_hash($code, PASSWORD_DEFAULT),
            ':creation'   => date('Y-m-d H:i:s'),
            ':expiration' => date('Y-m-d H:i:s', time() + DUREE_CODE_MINUTES * 60),
        ]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return envoyerEmailCodeReinitialisation($utilisateur['nom'], $email, $code, DUREE_CODE_MINUTES);
}

function marquerCodeUtilise(PDO $pdo, $idReinit)
{
    $requete = $pdo->prepare('UPDATE reinitialisations_mdp SET utilise = 1 WHERE id_reinit = :id');
    $requete->execute([':id' => $idReinit]);
}

// Vérifie le code. Retourne ['id_utilisateur' => ..., 'id_reinit' => ...] s'il est valable, sinon false.
// Le même résultat (false) est renvoyé pour un e-mail inconnu, un code faux ou expiré.
function verifierCodeReinitialisation(PDO $pdo, $email, $code)
{
    $requete = $pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE email = :email');
    $requete->execute([':email' => $email]);
    $utilisateur = $requete->fetch();
    if (!$utilisateur) {
        return false;
    }
    $idUtilisateur = (int) $utilisateur['id_utilisateur'];

    $recherche = $pdo->prepare(
        'SELECT id_reinit, code_hash, date_expiration, tentatives
         FROM reinitialisations_mdp
         WHERE id_utilisateur = :id AND utilise = 0
         ORDER BY id_reinit DESC LIMIT 1'
    );
    $recherche->execute([':id' => $idUtilisateur]);
    $enregistrement = $recherche->fetch();
    if (!$enregistrement) {
        return false;
    }

    // Code expiré ou trop d'essais : il est définitivement annulé.
    if (strtotime($enregistrement['date_expiration']) < time()
        || (int) $enregistrement['tentatives'] >= MAX_TENTATIVES_CODE) {
        marquerCodeUtilise($pdo, $enregistrement['id_reinit']);
        return false;
    }

    if (!password_verify($code, $enregistrement['code_hash'])) {
        $echec = $pdo->prepare('UPDATE reinitialisations_mdp SET tentatives = tentatives + 1 WHERE id_reinit = :id');
        $echec->execute([':id' => $enregistrement['id_reinit']]);
        return false;
    }

    return ['id_utilisateur' => $idUtilisateur, 'id_reinit' => (int) $enregistrement['id_reinit']];
}

// Remplace le mot de passe et annule tous les codes de l'utilisateur, en une seule opération.
function changerMotDePasse(PDO $pdo, $idUtilisateur, $nouveauMotDePasse)
{
    try {
        $pdo->beginTransaction();

        $maj = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe_hash = :hash WHERE id_utilisateur = :id');
        $maj->execute([
            ':hash' => password_hash($nouveauMotDePasse, PASSWORD_DEFAULT),
            ':id'   => $idUtilisateur,
        ]);

        $annulation = $pdo->prepare('UPDATE reinitialisations_mdp SET utilise = 1 WHERE id_utilisateur = :id');
        $annulation->execute([':id' => $idUtilisateur]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}