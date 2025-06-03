<title>Stock</title>
<!-- Mini-nav-bar rouge dans le folder visuel -->
<?php include '../visuel/barre.php'; ?> 

<!-- Affichage de la table Addition avec la date de commande -->
<div class="container my-4">
    <div class="card">
        <div class="card-header" style="background-color: rgb(206, 0, 0); color: white; font-size: 1.5rem; text-align: center;">
            Niveau de stock des produits
        </div>
        <div class="card-body">
            <?php
            // Connexion à la base de données
            include '../donnée/connect.php';
            $pdo = new PDO('mysql:host=' . $DB_HOST . ';dbname=razanateraa_cinema;charset=utf8', $DB_USER, $DB_PASS);

            $message = '';

            // Traitement de la modification
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
                $id_stock = (int)$_POST['id_stock'];
                $seuil_min = (int)$_POST['seuil_min'];
                $seuil_max = (int)$_POST['seuil_max'];
                $stock_actuel = (int)$_POST['stock_actuel'];

                $sqlUpdate = "UPDATE QuantiteStock SET seuil_min = :seuil_min, seuil_max = :seuil_max, stock_actuel = :stock_actuel WHERE id_stock = :id_stock";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $success = $stmtUpdate->execute([
                    ':seuil_min' => $seuil_min,
                    ':seuil_max' => $seuil_max,
                    ':stock_actuel' => $stock_actuel,
                    ':id_stock' => $id_stock
                ]);

                if ($success) {
                    $message = '<div class="alert alert-success text-center" role="alert">Modification réussie pour le stock ID ' . htmlspecialchars($id_stock) . '.</div>';
                } else {
                    $message = '<div class="alert alert-danger text-center" role="alert">Erreur lors de la modification du stock ID ' . htmlspecialchars($id_stock) . '.</div>';
                }
            }

            // Pagination
            $parPage = 30;;
            $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $parPage;

            // Compter le nombre total de stocks
            $totalReq = $pdo->query("SELECT COUNT(*) FROM QuantiteStock");
            $total = $totalReq->fetchColumn();
            $totalPages = ceil($total / $parPage);

            // Récupérer les stocks
            $sqlStock = "SELECT id_stock, id_produit, seuil_min, seuil_max, stock_actuel FROM QuantiteStock ORDER BY id_stock ASC LIMIT :offset, :parPage";
            $stmtStock = $pdo->prepare($sqlStock);
            $stmtStock->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmtStock->bindValue(':parPage', $parPage, PDO::PARAM_INT);
            $stmtStock->execute();
            ?>

            <!-- Afficher le message -->
            <?= $message ?>

            <!-- Tableau de modification du stock -->
            <div class="container my-4">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>id_stock</th>
                                        <th>id_produit</th>
                                        <th>seuil_min</th>
                                        <th>seuil_max</th>
                                        <th>stock_actuel</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $stmtStock->fetch(PDO::FETCH_ASSOC)) : ?>
                                        <tr>
                                            <form method="post">
                                                <td>
                                                    <input type="hidden" name="id_stock" value="<?= htmlspecialchars($row['id_stock']) ?>">
                                                    <?= htmlspecialchars($row['id_stock']) ?>
                                                </td>
                                                <td>
                                                    <input type="hidden" name="id_produit" value="<?= htmlspecialchars($row['id_produit']) ?>">
                                                    <?= htmlspecialchars($row['id_produit']) ?>
                                                </td>
                                                <td>
                                                    <input type="number" name="seuil_min" value="<?= htmlspecialchars($row['seuil_min']) ?>" class="form-control" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="seuil_max" value="<?= htmlspecialchars($row['seuil_max']) ?>" class="form-control" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="stock_actuel" value="<?= htmlspecialchars($row['stock_actuel']) ?>" class="form-control" required>
                                                </td>
                                                <td>
                                                    <button type="submit" name="update_stock" class="btn btn-danger btn-sm">Modifier</button>
                                                </td>
                                            </form>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination incluse -->
                        <?php include '../visuel/pagination.php'; ?>

                    </div>
                </div>
            </div>
