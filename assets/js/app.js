// ============================================================
// StudentTask : comportements JavaScript
// Rappel : la validation PHP reste la vraie protection.
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    initialiserMenu();
    initialiserFiltresTaches();
    initialiserValidationFormulaires();
});

// Met en minuscules et retire les accents, pour comparer "tâche" et "tache".
function normaliser(texte) {
    return String(texte || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

// ---------- Menu sur téléphone ----------
function initialiserMenu() {
    var bouton = document.getElementById('menu-toggle');
    var menu = document.getElementById('sidebar');

    if (bouton && menu) {
        bouton.addEventListener('click', function () {
            var ouvert = menu.classList.toggle('ouverte');
            bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        });
    }
}

// ---------- Recherche, filtres et tri de la page Tâches ----------
function initialiserFiltresTaches() {
    var corps = document.getElementById('corps-taches');
    if (!corps) {
        return; // on n'est pas sur la page Tâches
    }

    var champs = {
        recherche: document.getElementById('recherche'),
        projet: document.getElementById('filtre-projet'),
        membre: document.getElementById('filtre-membre'),
        statut: document.getElementById('filtre-statut'),
        priorite: document.getElementById('filtre-priorite'),
        tri: document.getElementById('tri')
    };
    var etatVide = document.getElementById('etat-vide-filtre');
    var boutonReinit = document.getElementById('reinitialiser-filtres');
    var ordrePriorite = { haute: 0, moyenne: 1, basse: 2 };

    // On prépare chaque ligne une seule fois.
    var lignes = Array.prototype.slice.call(corps.querySelectorAll('tr')).map(function (tr) {
        return { tr: tr, texte: normaliser(tr.dataset.recherche) };
    });

    function appliquer() {
        var q = normaliser(champs.recherche.value);
        var projet = champs.projet.value;
        var membre = champs.membre.value;
        var statut = champs.statut.value;
        var priorite = champs.priorite.value;

        var compte = { affichees: 0, a_faire: 0, en_cours: 0, termine: 0, retard: 0 };

        lignes.forEach(function (ligne) {
            var d = ligne.tr.dataset;
            var correspond =
                (q === '' || ligne.texte.indexOf(q) !== -1) &&
                (projet === '' || d.projet === projet) &&
                (membre === '' || (membre === '0' ? d.assigne === '' : d.assigne === membre)) &&
                (statut === '' || d.statut === statut) &&
                (priorite === '' || d.priorite === priorite);

            ligne.tr.hidden = !correspond;

            if (correspond) {
                compte.affichees++;
                compte[d.statut]++;
                if (d.retard === '1') {
                    compte.retard++;
                }
            }
        });

        // Tri : par date limite (défaut) ou par priorité puis date.
        var tri = champs.tri.value;
        lignes.slice().sort(function (a, b) {
            var da = a.tr.dataset, db = b.tr.dataset;
            if (tri === 'priorite') {
                var diff = ordrePriorite[da.priorite] - ordrePriorite[db.priorite];
                if (diff !== 0) { return diff; }
            }
            if (da.date < db.date) { return -1; }
            if (da.date > db.date) { return 1; }
            return Number(da.id) - Number(db.id);
        }).forEach(function (ligne) {
            corps.appendChild(ligne.tr);
        });

        // Indicateurs mis à jour en direct.
        majTexte('compteur-affichees', compte.affichees);
        majTexte('res-a_faire', compte.a_faire);
        majTexte('res-en_cours', compte.en_cours);
        majTexte('res-termine', compte.termine);
        majTexte('res-retard', compte.retard);

        if (etatVide) {
            etatVide.hidden = compte.affichees > 0;
        }
    }

    function majTexte(id, valeur) {
        var element = document.getElementById(id);
        if (element) {
            element.textContent = valeur;
        }
    }

    champs.recherche.addEventListener('input', appliquer);
    ['projet', 'membre', 'statut', 'priorite', 'tri'].forEach(function (nom) {
        champs[nom].addEventListener('change', appliquer);
    });

    if (boutonReinit) {
        boutonReinit.addEventListener('click', function () {
            champs.recherche.value = '';
            champs.projet.value = '';
            champs.membre.value = '';
            champs.statut.value = '';
            champs.priorite.value = '';
            champs.tri.value = 'date';
            appliquer();
            champs.recherche.focus();
        });
    }

    // Recherche lancée depuis la barre du haut : ?q=texte
    var recherchePassee = new URLSearchParams(window.location.search).get('q');
    if (recherchePassee) {
        champs.recherche.value = recherchePassee;
    }

    appliquer();
}

// ---------- Validation ergonomique des formulaires ----------
function initialiserValidationFormulaires() {
    // Date limite : jamais avant la date de début (projet) ou le début du projet (tâche).
    var debut = document.getElementById('date_debut');
    var limite = document.getElementById('date_limite');

    if (limite) {
        var verifierDates = function () {
            limite.setCustomValidity('');
            if (debut && debut.value) {
                limite.min = debut.value;
            }
            var min = limite.getAttribute('min');
            if (limite.value && min && limite.value < min) {
                limite.setCustomValidity(debut
                    ? 'La date limite ne peut pas être antérieure à la date de début.'
                    : 'La date limite ne peut pas être antérieure au début du projet.');
            }
        };

        limite.addEventListener('input', verifierDates);
        limite.addEventListener('change', function () {
            verifierDates();
            if (!limite.checkValidity()) {
                limite.reportValidity();
            }
        });
        if (debut) {
            debut.addEventListener('input', verifierDates);
        }
        verifierDates();
    }

    // Inscription : les deux mots de passe doivent être identiques.
    var motDePasse = document.getElementById('mot_de_passe');
    var confirmation = document.getElementById('confirmation');

    if (motDePasse && confirmation) {
        var verifierMotsDePasse = function () {
            confirmation.setCustomValidity(
                confirmation.value && confirmation.value !== motDePasse.value
                    ? 'Les deux mots de passe ne correspondent pas.'
                    : ''
            );
        };
        motDePasse.addEventListener('input', verifierMotsDePasse);
        confirmation.addEventListener('input', verifierMotsDePasse);
    }
}