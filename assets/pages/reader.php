<?php
session_start();
require_once __DIR__ . '/../../models/reader.php';
require_once __DIR__ . '/../../database/bdd.php';

// Configuration des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification de l'authentification
if (!isset($_SESSION['user']) || $_SESSION['user']['post'] !== 'reader') {
    header('Location: principale.php');
    exit();
}

// Initialisation
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Erreur de connexion à la base de données");
}

$reader = new Reader($db);

// Récupération des paramètres
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$selectedCategory = isset($_GET['category']) ? trim($_GET['category']) : '';

// Récupération des catégories
$categories = $reader->getCategories();

// Récupération des articles
$result = $reader->getArticles($page, $limit, 'confirme', $selectedCategory);
$articles = $result['success'] ? $result['data'] : [];
$error_message = $result['success'] ? null : $result['error'];

// Calcul de la pagination
$total_articles = $reader->getTotalArticles($selectedCategory);
$total_pages = ceil($total_articles / $limit);

// Article spécifique
$article = null;
if (isset($_GET['view_article'])) {
    $article = $reader->getArticleById($_GET['view_article']);
    if (!$article) {
        $error_message = "Article non trouvé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Lecteur</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">  
    <link rel="stylesheet" href="../style/stye_reader.css"> 
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-book-reader mr-2"></i>Espace Lecteur</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="principale.php"><i class="fas fa-home mr-1"></i>Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i>Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error_message ?? '') ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($article)): ?>
            <!-- Filtrage par catégorie -->
            <form action="" method="get" class="mb-4">
                <div class="form-row align-items-center justify-content-center">
                    <div class="col-auto">
                        <select name="category" class="form-control category-select">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category ?? '') ?>" 
                                        <?= $selectedCategory === $category ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter mr-1"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>

            <!-- Liste des articles -->
            <h2><i class="fas fa-newspaper mr-2"></i>Articles disponibles</h2>
            
            <?php if (empty($articles)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>Aucun article disponible pour le moment.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($articles as $article): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <?php if ($article['image_type'] === 'blob' && !empty($article['image'])): ?>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($article['image'] ?? '') ?>" 
                                         class="card-img-top article-thumbnail" 
                                         alt="Image de l'article"
                                         onerror="this.parentElement.innerHTML='<div class=\'image-placeholder\'><i class=\'fas fa-image mr-2\'></i>Image non disponible</div>'">
                                <?php elseif ($article['image_type'] === 'path' && !empty($article['image'])): ?>
                                    <img src="../../assets/images/<?= htmlspecialchars($article['image'] ?? '') ?>" 
                                         class="card-img-top article-thumbnail" 
                                         alt="Image de l'article"
                                         onerror="this.parentElement.innerHTML='<div class=\'image-placeholder\'><i class=\'fas fa-image mr-2\'></i>Image non disponible</div>'">
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="fas fa-image mr-2"></i>Image non disponible
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h3 class="card-title"><?= htmlspecialchars($article['titre'] ?? '') ?></h3>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <i class="fas fa-user mr-1"></i><?= htmlspecialchars(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? '')) ?> | 
                                        <i class="fas fa-tag mr-1"></i><?= htmlspecialchars($article['nom_categorie'] ?? '') ?>
                                    </h6>
                                    <p class="card-text"><?= htmlspecialchars($article['description'] ?? '') ?></p>
                                    <a href="?view_article=<?= $article['id'] ?><?= $selectedCategory ? '&category=' . urlencode($selectedCategory) : '' ?>" 
                                       class="btn btn-primary btn-block">
                                       <i class="fas fa-book-open mr-1"></i>Lire plus
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navigation des pages" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" 
                                       href="?page=<?= $i ?><?= $selectedCategory ? '&category=' . urlencode($selectedCategory) : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <!-- Vue détaillée d'un article -->
            <div class="card mb-4">
                <div class="card-body">
                    <h1 class="card-title text-center mb-4"><?= htmlspecialchars($article['titre'] ?? '') ?></h1>
                    <h6 class="card-subtitle mb-3 text-muted text-center">
                        <i class="fas fa-user mr-1"></i><?= htmlspecialchars(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? '')) ?> | 
                        <i class="fas fa-tag mr-1"></i><?= htmlspecialchars($article['nom_categorie'] ?? '') ?>
                    </h6>
                    <?php if ($article['image_type'] === 'blob' && !empty($article['image'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($article['image'] ?? '') ?>" 
                             class="img-fluid mb-3 article-image" 
                             alt="Image de l'article"
                             onerror="this.style.display='none'">
                    <?php elseif ($article['image_type'] === 'path' && !empty($article['image'])): ?>
                        <img src="../../assets/images/<?= htmlspecialchars($article['image'] ?? '') ?>" 
                             class="img-fluid mb-3 article-image" 
                             alt="Image de l'article"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                    <p class="lead text-center mb-4"><?= htmlspecialchars($article['description'] ?? '') ?></p>
                    <div class="article-content">
                        <?= nl2br(htmlspecialchars($article['contenu'] ?? '')) ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="reader.php<?= $selectedCategory ? '?category=' . urlencode($selectedCategory) : '' ?>" 
                           class="btn btn-primary">
                           <i class="fas fa-arrow-left mr-1"></i>Retour aux articles
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>