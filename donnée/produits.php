<?php
session_start();
include 'donnée/connect.php';

try {
    $pdo = new PDO('mysql:host=' . $DB_HOST . ';dbname=razanateraa_cinema;charset=utf8', $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// Initialisation du panier
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

            $update = $pdo->prepare(
                $type_mouvement === 'entree'
                    ? "UPDATE QuantiteStock SET stock_actuel = stock_actuel + ? WHERE id_produit = ?"
                    : "UPDATE QuantiteStock SET stock_actuel = GREATEST(stock_actuel - ?, 0) WHERE id_produit = ?"
            );
            $update->execute([$quantite, $id]);

            $stmt = $pdo->prepare("INSERT INTO Addition (id_commande, id_produit, quantite) VALUES (?, ?, ?)");
            $stmt->execute([$id_commande, $id, $quantite]);
        }

        $_SESSION['panier'] = [];

        // Conserver les paramètres d’URL actuels lors de la redirection
        $params = $_GET;
        $params['success'] = 1;
        $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
        header("Location: $redirect_url");
        exit();
    }
}

// Pagination & recherche
$perPage = 40;
$page = isset($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
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
