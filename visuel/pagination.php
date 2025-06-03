<?php
// pagination.php
// Affiche une pagination centrée et responsive
// Variables attendues :
// $total (int) : nombre total d’éléments
// $parPage (int) : nombre d’éléments par page
// $page (int) : page courante
// $urlBase (string) : URL de base, sans le paramètre "page", ex : "marque.php" ou "stock.php?foo=bar&"

if (!isset($total) || !isset($parPage) || !isset($page) || !isset($urlBase)) {
    echo '<div class="alert alert-danger">Pagination : variables manquantes.</div>';
    return;
}

$totalPages = ceil($total / $parPage);
if ($totalPages <= 1) return; // Pas besoin de pagination

// Nombre de pages à afficher autour de la page courante
$maxPagesToShow = 7;
$startPage = max(1, $page - intval($maxPagesToShow / 2));
$endPage = min($totalPages, $startPage + $maxPagesToShow - 1);

// Ajuster startPage si on est en fin de pagination
if ($endPage - $startPage + 1 < $maxPagesToShow) {
    $startPage = max(1, $endPage - $maxPagesToShow + 1);
}
?>

<nav aria-label="Pagination">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= htmlspecialchars($urlBase . 'page=' . ($page - 1)) ?>">Précédent</a>
            </li>
        <?php endif; ?>

        <?php if ($startPage > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($urlBase . 'page=1') ?>">1</a></li>
            <?php if ($startPage > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($urlBase . 'page=' . $i) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
                <li class="page-item disabled btn btn-danger btn-sm"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link " href="<?= htmlspecialchars($urlBase . 'page=' . $totalPages) ?>"><?= $totalPages ?></a></li>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
            <li class="page-item ">
                <a class="page-link" href="<?= htmlspecialchars($urlBase . 'page=' . ($page + 1)) ?>">Suivant</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
