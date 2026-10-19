<?php
// Calculs du tableau de bord.

const NOMS_MOIS = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                   'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

// Retourne les compteurs de tâches de tous les projets de l'utilisateur.
function calculerIndicateurs(PDO $pdo, $idUtilisateur)
{
    $sql = "SELECT COUNT(t.id_tache) AS total,
                   COALESCE(SUM(t.statut = 'a_faire'), 0)  AS a_faire,
                   COALESCE(SUM(t.statut = 'en_cours'), 0) AS en_cours,
                   COALESCE(SUM(t.statut = 'termine'), 0)  AS termine,
                   COALESCE(SUM(t.statut <> 'termine' AND t.date_limite < :aujourdhui), 0) AS en_retard
            FROM taches t
            JOIN membres_projet mp ON mp.id_projet = t.id_projet AND mp.id_utilisateur = :uid";

    $requete = $pdo->prepare($sql);
    $requete->execute([
        ':aujourdhui' => date('Y-m-d'), // même règle de date que estEnRetard()
        ':uid'        => $idUtilisateur,
    ]);

    return array_map('intval', $requete->fetch());
}

// Nombre de tâches non terminées à échéance chaque jour, à partir d'aujourd'hui.
// Retourne un tableau ['AAAA-MM-JJ' => nombre].
function echeancesProchainsJours(array $taches, $jours = 14)
{
    $resultat = [];
    $debut    = new DateTime('today');

    for ($i = 0; $i < $jours; $i++) {
        $cle = (clone $debut)->modify('+' . $i . ' day')->format('Y-m-d');
        $resultat[$cle] = 0;
    }

    foreach ($taches as $t) {
        if ($t['statut'] !== 'termine' && isset($resultat[$t['date_limite']])) {
            $resultat[$t['date_limite']]++;
        }
    }

    return $resultat;
}

// Prépare l'affichage d'un mois : premier jour, nombre de jours, décalage (lundi = 0)
// et nombre de tâches non terminées à échéance chaque jour du mois.
function construireCalendrier($annee, $mois, array $taches)
{
    $premier  = new DateTime(sprintf('%04d-%02d-01', $annee, $mois));
    $prefixe  = $premier->format('Y-m');
    $parJour  = [];

    foreach ($taches as $t) {
        if ($t['statut'] !== 'termine' && substr($t['date_limite'], 0, 7) === $prefixe) {
            $jour = (int) substr($t['date_limite'], 8, 2);
            $parJour[$jour] = ($parJour[$jour] ?? 0) + 1;
        }
    }

    return [
        'premier'  => $premier,
        'nbJours'  => (int) $premier->format('t'),
        'decalage' => (int) $premier->format('N') - 1,
        'parJour'  => $parJour,
    ];
}