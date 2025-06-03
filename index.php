<?php
session_start();
include 'donnée/connect.php';

try {
    $pdo = new PDO('mysql:host=' . $DB_HOST . ';dbname=razanateraa_cinema;charset=utf8', $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// Initialisation du panier en session
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Suppression d'un article du panier
if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    $id_supprimer = intval($_GET['supprimer']);
    unset($_SESSION['panier'][$id_supprimer]);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produits = $_POST['id_produits'] ?? [];
    $quantites = $_POST['quantites'] ?? [];
    $actions = $_POST['actions'] ?? [];

    foreach ($id_produits as $index => $id) {
        $id = intval($id);
        $quantite = intval($quantites[$index]);
        $action = $actions[$index];

        if ($quantite > 0 && in_array($action, ['ajouter', 'supprimer'])) {
            $_SESSION['panier'][$id] = [
                'quantite' => $quantite,
                'action' => $action
            ];
        }
    }

    // Validation du panier
    if (isset($_POST['valider_panier'])) {
        $stmt = $pdo->prepare("INSERT INTO Commande (date_commande) VALUES (CURDATE())");
        $stmt->execute();
        $id_commande = $pdo->lastInsertId();

        foreach ($_SESSION['panier'] as $id => $details) {
            $quantite = $details['quantite'];
            $action = $details['action'];
            $type_mouvement = $action === 'ajouter' ? 'entree' : 'sortie';

            $stmt = $pdo->prepare("INSERT INTO Mouvement (id_produit, id_commande, type_mouvement, quantite) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $id_commande, $type_mouvement, $quantite]);

            if ($type_mouvement === 'entree') {
                $update = $pdo->prepare("UPDATE QuantiteStock SET stock_actuel = stock_actuel + ? WHERE id_produit = ?");
                $update->execute([$quantite, $id]);
            } else {
                $update = $pdo->prepare("UPDATE QuantiteStock SET stock_actuel = GREATEST(stock_actuel - ?, 0) WHERE id_produit = ?");
                $update->execute([$quantite, $id]);
            }

            $stmt = $pdo->prepare("INSERT INTO Addition (id_commande, id_produit, quantite) VALUES (?, ?, ?)");
            $stmt->execute([$id_commande, $id, $quantite]);
        }

        $_SESSION['panier'] = [];

        // Redirection avec conservation des paramètres
        $params = $_GET;
        $params['success'] = 1;
        $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
        header("Location: $redirect_url");
        exit();
    }
}

// Recherche et pagination
$perPage = 40;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE Produit.nom LIKE :search OR Marque.nom LIKE :search OR Categorie.nom LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$sqlCount = "SELECT COUNT(*) FROM Produit
             LEFT JOIN Marque ON Produit.id_marque = Marque.id_marque
             LEFT JOIN Categorie ON Produit.id_categorie = Categorie.id_categorie
             $where";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalProduits = $stmtCount->fetchColumn();
$totalPages = ceil($totalProduits / $perPage);

$sql = "SELECT 
            Produit.id_produit, Produit.nom, Produit.format, Produit.actif, 
            Marque.nom AS marque, Categorie.nom AS categorie,
            QuantiteStock.stock_actuel, QuantiteStock.seuil_min, QuantiteStock.seuil_max
        FROM Produit
        LEFT JOIN Marque ON Produit.id_marque = Marque.id_marque
        LEFT JOIN Categorie ON Produit.id_categorie = Categorie.id_categorie
        LEFT JOIN QuantiteStock ON Produit.id_produit = QuantiteStock.id_produit
        $where
        ORDER BY Produit.id_produit ASC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Produits</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>

<?php include 'visuel/special.php'; ?>

<div class="container my-4">
  <div class="card">
    <div class="card-header text-center" style="background-color: rgb(206, 0, 0); color: white; font-size: 1.5rem;">
      Liste des produits
    </div>

    <form method="get" class="my-3 d-flex justify-content-center" role="search">
      <input type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher un produit, marque ou catégorie" class="form-control w-50" />
      <button type="submit" class="btn btn-primary ms-2" style="background-color: rgb(206, 0, 0)">Rechercher</button>
    </form>

    <form method="post">
      <div class="card-body">

        <?php if (isset($_GET['success'])): ?>
          <div class="alert alert-success">
            Mouvements enregistrés, stocks mis à jour, commande créée avec succès.
          </div>
        <?php endif; ?>

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-secondary">
              <tr>
                <th>Identifiant</th>
                <th>Nom</th>
                <th>Format</th>
                <th>Actif</th>
                <th>Marque</th>
                <th>Catégorie</th>
                <th>Stock actuel</th>
                <th>Quantité</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <?php
                $stock = $row['stock_actuel'] ?? 0;
                $seuil_min = $row['seuil_min'] ?? 0;
                $seuil_max = $row['seuil_max'] ?? 0;

                $highlightClass = '';
                if ($stock < $seuil_min) {
                  $highlightClass = 'table-warning';
                } elseif ($stock > $seuil_max) {
                  $highlightClass = 'table-success';
                }

                $actif = $row['actif'];
                $actifClass = $actif == 1 ? 'table-success' : 'table-danger';
                ?>
                <tr>
                  <td><?= htmlspecialchars($row['id_produit']) ?></td>
                  <td><?= htmlspecialchars($row['nom']) ?></td>
                  <td><?= htmlspecialchars($row['format']) ?></td>
                  <td class="<?= $actifClass ?>">
                    <?= $actif == 1 ? 'Actif' : 'Inactif' ?>
                  </td>
                  <td><?= htmlspecialchars($row['marque']) ?></td>
                  <td><?= htmlspecialchars($row['categorie']) ?></td>
                  <td class="<?= $highlightClass ?>"><?= $stock ?></td>
                  <td>
                    <input type="number" name="quantites[]" class="form-control" min="0" value="0" style="width: 90px;">
                    <input type="hidden" name="id_produits[]" value="<?= htmlspecialchars($row['id_produit']) ?>">
                  </td>
                  <td>
                    <select name="actions[]" class="form-select">
                      <option value="ajouter">Ajouter</option>
                      <option value="supprimer">Supprimer</option>
                    </select>
                  </td>
                </tr>
              <?php endwhile; ?>
              <?php if ($totalProduits == 0): ?>
                <tr><td colspan="9" class="text-center">Aucun produit trouvé.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <button type="submit" name="voir_panier" class="btn btn-warning">
          <i class="bi bi-clipboard-check"></i> Mouvements du stock
        </button>

<?php if (!empty($_SESSION['panier'])): ?>
  <div class="mt-4 p-3 border bg-light">
    <h5>Contenu du panier :</h5>
    <ul class="list-group">
      <?php foreach ($_SESSION['panier'] as $id => $details): ?>
        <?php
        $stmtNom = $pdo->prepare("SELECT nom FROM Produit WHERE id_produit = ?");
        $stmtNom->execute([$id]);
        $produit = $stmtNom->fetch(PDO::FETCH_ASSOC);
        $nomProduit = $produit ? $produit['nom'] : 'Produit inconnu';

        $actionText = $details['action'] === 'ajouter' ? 'ajouter' : 'retirer';
        ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?= "Vous souhaitez $actionText {$details['quantite']} de " . htmlspecialchars($nomProduit) . "." ?>
          <a href="?supprimer=<?= urlencode($id) ?>" class="btn btn-sm btn-danger ms-3">
            <i class="bi bi-trash"></i> Supprimer
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <button type="submit" name="valider_panier" class="btn btn-success mt-3">
      <i class="bi bi-check-circle"></i> Valider le panier
    </button>
  </div>
<?php endif; ?>

      </div>
    </form>

    <nav class="my-4">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(['page' => $i, 'search' => $search]) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>

  </div>
</div>
</body>
</html>
