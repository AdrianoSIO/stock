<title>Stock</title>
<?php include '../visuel/barre.php'; ?>

<div class="container my-4">
    <div class="card">
        <div class="card-header" style="background-color: rgb(206, 0, 0); color: white; font-size: 1.5rem; text-align: center;">
            Niveau de stock des produits
        </div>
        <div class="card-body">
            <?php
            include '../donnée/connect.php';
            $pdo = new PDO('mysql:host=' . $DB_HOST . ';dbname=razanateraa_cinema;charset=utf8', $DB_USER, $DB_PASS);

            $message = '';

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
                    $message = '<div class="alert alert-success text-center">Modification réussie pour le stock ID ' . htmlspecialchars($id_stock) . '.</div>';
                } else {
                    $message = '<div class="alert alert-danger text-center">Erreur lors de la modification du stock ID ' . htmlspecialchars($id_stock) . '.</div>';
                }
            }

            // Pagination
            $parPage = 30;
            $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $parPage;

            $total = $pdo->query("SELECT COUNT(*) FROM QuantiteStock")->fetchColumn();

            // Récupérer les stocks avec INNER JOIN pour le nom du produit
            $sqlStock = "
                SELECT qs.id_stock, qs.id_produit, p.nom AS nom_produit, qs.seuil_min, qs.seuil_max, qs.stock_actuel
                FROM QuantiteStock qs
                INNER JOIN Produit p ON qs.id_produit = p.id_produit
                ORDER BY qs.id_stock ASC
                LIMIT :offset, :parPage
            ";
            $stmtStock = $pdo->prepare($sqlStock);
            $stmtStock->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmtStock->bindValue(':parPage', $parPage, PDO::PARAM_INT);
            $stmtStock->execute();

            // Construire l'URL de base pour la pagination
            $queryParams = $_GET;
            unset($queryParams['page']);
            $urlBase = basename($_SERVER['PHP_SELF']);
            $urlBase .= !empty($queryParams) ? '?' . http_build_query($queryParams) . '&' : '?';
            ?>

            <?= $message ?>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>id_stock</th>
                            <th>Produit</th>
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
                                        <?= htmlspecialchars($row['nom_produit']) ?>
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

            <?php include '../visuel/pagination.php'; ?>

        </div>
    </div>
</div>
