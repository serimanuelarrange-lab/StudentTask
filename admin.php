<?php
$titrePage  = 'Administration';
$pageActive = 'admin';

// On inclut le header (qui gère exigerConnexion(), session, DB et la structure HTML)
require_once __DIR__ . '/includes/header.php'; // Ajustez le chemin vers header.php si nécessaire

// Vérification du rôle Administrateur
if (($_SESSION['role'] ?? '') !== 'admin') {
    echo '<div class="alerte alerte-erreur">Accès refusé. Cette page est réservée aux administrateurs.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pdo = getPDO();
$message = '';

// Gestion de la suppression d'un membre
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id_a_supprimer = (int)$_GET['id'];
    
    if ($id_a_supprimer === (int)$_SESSION['utilisateur_id']) {
        $message = "Vous ne pouvez pas supprimer votre propre compte d'administrateur.";
    } else {
        $stmt_del = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
        $stmt_del->execute([$id_a_supprimer]);
        $message = "L'utilisateur a été supprimé avec succès.";
    }
}

// Récupération de tous les membres
$stmt = $pdo->query("SELECT id_utilisateur, nom, email, role, date_creation FROM utilisateurs ORDER BY date_creation DESC");
$utilisateurs = $stmt->fetchAll();
?>

<div class="page-entete">
    <h1>Administration - Gestion des Membres</h1>
</div>

<?php if ($message): ?>
    <div class="alerte alerte-info"><?= e($message) ?></div>
<?php endif; ?>

<div class="carte">
    <div class="table-conteneur">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Date d'inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $user): ?>
                <tr>
                    <td><?= e($user['id_utilisateur']) ?></td>
                    <td><strong><?= e($user['nom']) ?></strong></td>
                    <td><?= e($user['email']) ?></td>
                    <td>
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge badge-succes">Admin</span>
                        <?php else: ?>
                            <span class="badge">Membre</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($user['date_creation']) ?></td>
                    <td>
                        <?php if ($user['id_utilisateur'] != $_SESSION['utilisateur_id']): ?>
                            <a href="<?= BASE_URL ?>/admin.php?action=supprimer&id=<?= $user['id_utilisateur'] ?>" 
                               class="bouton-danger" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                               Supprimer
                            </a>
                        <?php else: ?>
                            <em>(Vous)</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </tbody>
        </table>
    </div>
</div>

<?php 
// Inclure le footer si votre application en possède un (ex: includes/footer.php)
if (file_exists(__DIR__ . '/includes/footer.php')) {
    require_once __DIR__ . '/includes/footer.php';
} else {
    echo '</div></div></body></html>';
}
?>