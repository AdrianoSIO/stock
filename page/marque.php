<title>Marques</title>
<?php include '../visuel/barre.php'; ?>

<div class="container my-4">
    <div class="card">
        <div class="card-header" style="background-color: rgb(206, 0, 0); color: white; font-size: 1.5rem; text-align: center;">
            Liste des marques
        </div>
        
        <div class="card-body">
            <!-- Formulaire d'ajout de marque -->
            <form method="post" class="mb-4 d-flex" style="max-width:400px; margin:auto;">
                <input type="text" name="nouvelle_marque" class="form-control me-2" placeholder="Nom de la nouvelle marque" required>
                <button type="submit" class="btn btn-danger btn-sm">Ajouter</button>
            </form>

            <?php
            include '../donnée/connect.php';

            try {
                $pdo = new PDO('mysql:host=' . $DB_HOST . ';dbname=razanateraa_cinema;charset=utf8', $DB_USER, $DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage()) . '</div>';
                exit;
            }

            // Traitement de l'ajout de marque
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nouvelle_marque'])) {
                $nouvelleMarque = trim($_POST['nouvelle_marque']);

                if ($nouvelleMarque !== '') {
                    // Vérifie si la marque existe déjà (insensible à la casse)
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM Marque WHERE LOWER(nom) = LOWER(:nom)");
                    $stmtCheck->execute([':nom' => $nouvelleMarque]);
                    $existe = $stmtCheck->fetchColumn();

                    if ($existe > 0) {
                        echo '<div class="alert alert-warning text-center">La marque <strong>' . htmlspecialchars($nouvelleMarque) . '</strong> existe déjà.</div>';
                    } else {
                        $stmtInsert = $pdo->prepare("INSERT INTO Marque (nom) VALUES (:nom)");
                        $stmtInsert->bindValue(':nom', $nouvelleMarque, PDO::PARAM_STR);
                        $stmtInsert->execute();
                        // Redirection pour éviter le renvoi du formulaire
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                } else {
                    echo '<div class="alert alert-warning text-center">Veuillez entrer un nom de marque.</div>';
                }
            }

            // Pagination
            $parPage = 20;
            $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $parPage;

            $total = $pdo->query("SELECT COUNT(*) FROM Marque")->fetchColumn();

            // Récupérer les marques avec limite et offset
            $sqlMarques = "SELECT id_marque, nom FROM Marque ORDER BY id_marque ASC LIMIT :offset, :parPage";
            $stmtMarques = $pdo->prepare($sqlMarques);
            $stmtMarques->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmtMarques->bindValue(':parPage', $parPage, PDO::PARAM_INT);
            $stmtMarques->execute();

            // Construire l'URL de base pour la pagination
            $queryParams = $_GET;
            unset($queryParams['page']);
            $urlBase = basename($_SERVER['PHP_SELF']);
            $urlBase .= (!empty($queryParams)) ? '?' . http_build_query($queryParams) . '&' : '?';
            ?>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-secondary">
                        <tr>
                            <th>ID</th>
                            <th>Nom de la marque</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($marque = $stmtMarques->fetch(PDO::FETCH_ASSOC)) : ?>
                            <tr>
                                <td><?= htmlspecialchars($marque['id_marque']) ?></td>
                                <td><?= htmlspecialchars($marque['nom']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php include '../visuel/pagination.php'; ?>

        </div>
    </div>
</div>
