<?php
// Formulaire de tâche réutilisable (création et modification).
// Variables attendues : $projet (avec 'titre'), $membres, $valeurs, $libelleBouton, $lienAnnuler.
?>
<form method="post" action="">
    <?= champCsrf() ?>

    <div class="champ">
        <label for="titre">Titre de la tâche *</label>
        <input type="text" id="titre" name="titre" required maxlength="150" value="<?= e($valeurs['titre']) ?>">
    </div>
    <div class="champ">
        <label for="description">Description</label>
        <textarea id="description" name="description" maxlength="1000"><?= e($valeurs['description']) ?></textarea>
    </div>

    <div class="ligne-champs">
        <div class="champ">
            <label for="projet">Projet</label>
            <input type="text" id="projet" value="<?= e($projet['titre']) ?>" disabled>
        </div>
        <div class="champ">
            <label for="id_assigne">Attribuée à</label>
            <select id="id_assigne" name="id_assigne">
                <option value="">Non attribuée</option>
                <?php foreach ($membres as $m): ?>
                    <option value="<?= e($m['id_utilisateur']) ?>"
                        <?= (int) $valeurs['id_assigne'] === (int) $m['id_utilisateur'] ? 'selected' : '' ?>>
                        <?= e($m['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="ligne-champs">
        <div class="champ">
            <label for="priorite">Priorité *</label>
            <select id="priorite" name="priorite" required>
                <?php foreach (PRIORITES_TACHE as $valeur): ?>
                    <option value="<?= e($valeur) ?>" <?= $valeurs['priorite'] === $valeur ? 'selected' : '' ?>><?= e(libellePriorite($valeur)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="champ">
            <label for="statut">Statut *</label>
            <select id="statut" name="statut" required>
                <?php foreach (STATUTS_TACHE as $valeur): ?>
                    <option value="<?= e($valeur) ?>" <?= $valeurs['statut'] === $valeur ? 'selected' : '' ?>><?= e(libelleStatut($valeur)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="champ">
            <label for="date_limite">Date limite *</label>
                       <input type="date" id="date_limite" name="date_limite" required min="<?= e($projet['date_debut'] ?? '') ?>" value="<?= e($valeurs['date_limite']) ?>">
        </div>
    </div>

    <div class="actions-formulaire">
        <button class="bouton" type="submit"><?= e($libelleBouton) ?></button>
        <a class="bouton bouton-secondaire" href="<?= e($lienAnnuler) ?>">Annuler</a>
    </div>
</form>