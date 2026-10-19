<?php
// Fonctions utilitaires de StudentTask.

// Échappe un texte avant de l'afficher en HTML (protection contre XSS).
function e($texte)
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

// Progression en pourcentage, sans division par zéro.
function calculerProgression($tachesTerminees, $totalTaches)
{
    if ($totalTaches <= 0) {
        return 0;
    }
    return (int) round(($tachesTerminees / $totalTaches) * 100);
}

// Libellé français d'un statut de tâche.
function libelleStatut($statut)
{
    $libelles = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'termine' => 'Terminé'];
    return $libelles[$statut] ?? $statut;
}

// Libellé français d'une priorité.
function libellePriorite($priorite)
{
    $libelles = ['basse' => 'Basse', 'moyenne' => 'Moyenne', 'haute' => 'Haute'];
    return $libelles[$priorite] ?? $priorite;
}

// Règle métier : une tâche est en retard si sa date limite est avant aujourd'hui
// ET si elle n'est pas terminée. Une tâche due aujourd'hui n'est pas en retard.
function estEnRetard($dateLimite, $statut)
{
    if ($statut === 'termine' || empty($dateLimite)) {
        return false;
    }
    return $dateLimite < date('Y-m-d');
}

// Transforme AAAA-MM-JJ en JJ/MM/AAAA pour l'affichage.
function formaterDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', (string) $date);
    return $d ? $d->format('d/m/Y') : '';
}