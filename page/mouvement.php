<?php
$title = "Mouvements de stock";
include '../visuel/barre.php';
include '../donnée/connect.php';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=razanateraa_cinema;charset=utf8", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// Récupération des filtres
$id_commande = $_POST['id_commande'] ?? '';
$id_produit = $_POST['id_produit'] ?? '';

// Construction de la clause WHERE
$where = [];
$params = [];

if (!empty($id_commande)) {
    $where[] = "m.id_commande = :id_commande";
    $params[':id_commande'] = $id_commande;
}
if (!empty($id_produit)) {
    $where[] = "m.id_produit = :id_produit";
    $params[':id_produit'] = $id_produit;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Requête principale avec INNER JOIN pour récupérer le nom du produit
$sql = "
    SELECT 
        m.id_mouvement, 
        m.id_commande, 
        m.id_produit, 
        p.nom AS nom_produit, 
        m.quantite, 
        m.type_mouvement
    FROM Mouvement m
    INNER JOIN Produit p ON m.id_produit = p.id_produit
    $whereSql
    ORDER BY m.id_mouvement DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container my-4">
    <div class="card">
        <div class="card-header text-center" style="background-color: rgb(206, 0, 0); color: white; font-size: 1.5rem;">
            Mouvements de stock
        </div>
        <div class="card-body">

            <!-- Formulaire de filtre -->
            <form method="post" class="row g-3 mb-4">
                <div class="col-md-4">
                    <input type="number" name="id_commande" class="form-control" placeholder="Filtrer par ID Commande" value="<?= htmlspecialchars($id_commande) ?>">
                </div>
                <div class="col-md-4">
                    <input type="number" name="id_produit" class="form-control" placeholder="Filtrer par ID Produit" value="<?= htmlspecialchars($id_produit) ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100" style="background-color: rgb(206, 0, 0)">Filtrer</button>
                </div>
            </form>

            <!-- Tableau des mouvements -->
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>ID Mouvement</th>
                            <th>ID Commande</th>
                            <th>Nom du Produit</th>
                            <th>Quantité</th>
                            <th>Type de Mouvement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mouvements)) : ?>
                            <tr><td colspan="5" class="text-center">Aucun mouvement trouvé.</td></tr>
                        <?php else : ?>
                            <?php foreach ($mouvements as $mouvement) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($mouvement['id_mouvement']) ?></td>
                                    <td><?= htmlspecialchars($mouvement['id_commande']) ?></td>
                                    <td><?= htmlspecialchars($mouvement['nom_produit']) ?></td>
                                    <td><?= htmlspecialchars($mouvement['quantite']) ?></td>
                                    <td><?= htmlspecialchars($mouvement['type_mouvement']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

</body>
</html>
