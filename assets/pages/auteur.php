<?php
session_start();
require_once __DIR__ . '/../../models/auteur.php';
require_once __DIR__ . '/../../database/bdd.php';

$database = new Database();
$db = $database->getConnection();
$auteur = new Auteur($db);

$auteur_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$auteur_id) {
    die("ID de l'auteur non spécifié.");
}

$auteur_info = $auteur->getAuteurInfo($auteur_id);

// Récupération des statistiques
$status_stats = $auteur->getArticleStatusStats($auteur_id);
$category_stats = $auteur->getArticlesByCategoryStats($auteur_id);
$deleted_count = $auteur->getDeletedArticlesCount($auteur_id);

$message = '';
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $titre = $_POST['titre'];
        $description = $_POST['description'];
        $contenu = $_POST['contenu'];
        $categorie_id = $_POST['categorie'];
        $image = isset($_FILES['image']) ? $_FILES['image'] : null;

        try {
            $auteur->createArticle($auteur_id, $titre, $description, $contenu, $image, $categorie_id);
            $message = "Article créé avec succès!";
        } catch (Exception $e) {
            $message = "Erreur lors de la création de l'article: " . $e->getMessage();
        }
    } elseif (isset($_POST['update'])) {
        $article_id = $_POST['article_id'];
        $titre = $_POST['titre'];
        $description = $_POST['description'];
        $contenu = $_POST['contenu'];
        $categorie_id = $_POST['categorie'];
        $image = isset($_FILES['image']) ? $_FILES['image'] : null;

        try {
            $auteur->updateArticle($article_id, $auteur_id, $titre, $description, $contenu, $image, $categorie_id);
            $message = "Article mis à jour avec succès!";
        } catch (Exception $e) {
            $message = "Erreur lors de la mise à jour de l'article: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete'])) {
        $article_id = $_POST['article_id'];

        try {
            $auteur->deleteArticle($article_id, $auteur_id);
            $message = "Article supprimé avec succès!";
        } catch (Exception $e) {
            $message = "Erreur lors de la suppression de l'article: " . $e->getMessage();
        }
    }

    if ($is_ajax) {
        echo json_encode(['message' => $message]);
        exit;
    }
}

$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
$articles = $auteur->getArticlesByAuteurAndCategory($auteur_id, $selected_category);
$categories = $auteur->getAllCategories();

if ($is_ajax) {
    // Ajuster les chemins d'images pour les requêtes AJAX
    foreach ($articles as &$article) {
        if (!empty($article['image'])) {
            $image_path = $article['image'];
            if (!file_exists($image_path) && file_exists('../../' . $image_path)) {
                $article['image'] = '../../' . $image_path;
            }
        } else {
            $article['image'] = '/assets/images/placeholder.jpg';
        }
    }
    echo json_encode(['articles' => $articles]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Auteur</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #1a1a1a;
            color: #ffffff;
            font-family: 'Arial', sans-serif;
        }
        .container {
            background-color: #2a2a2a;
            border-radius: 10px;
            padding: 30px;
            margin-top: 50px;
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.1);
        }
        h1, h2 {
            color: #ff3333;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .btn-primary {
            background-color: #ff3333;
            border-color: #ff3333;
        }
        .btn-primary:hover {
            background-color: #cc0000;
            border-color: #cc0000;
        }
        .btn-secondary {
            background-color: #333333;
            border-color: #333333;
        }
        .btn-secondary:hover {
            background-color: #4d4d4d;
            border-color: #4d4d4d;
        }
        .card {
            background-color: #333333;
            border: none;
            transition: transform 0.3s ease-in-out;
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card-title {
            color: #ff3333;
        }
        .chart-container {
            background-color: #333333;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .form-control {
            background-color: #4d4d4d;
            border: none;
            color: #ffffff;
        }
        .form-control:focus {
            background-color: #666666;
            color: #ffffff;
        }
        .modal-content {
            background-color: #2a2a2a;
            color: #ffffff;
        }
        .close {
            color: #ffffff;
        }
        .alert-success {
            background-color: #28a745;
            color: #ffffff;
            border: none;
        }
        .alert-danger {
            background-color: #dc3545;
            color: #ffffff;
            border: none;
        }
        .hidden {
            display: none;
        }
        .navbar {
            background-color: #1a1a1a;
        }
        .navbar-brand, .nav-link {
            color: #ffffff !important;
        }
        .dropdown-menu {
            background-color: #333333;
        }
        .dropdown-item {
            color: #ffffff;
        }
        .dropdown-item:hover {
            background-color: #ff3333;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Espace Auteur</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Catégories
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="#" data-category="">Toutes les catégories</a>
                            <?php foreach ($categories as $categorie): ?>
                                <a class="dropdown-item" href="#" data-category="<?php echo $categorie['id']; ?>">
                                    <?php echo htmlspecialchars($categorie['nom']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="mb-5">Bienvenue, <?php echo htmlspecialchars($auteur_info['prenom'] . ' ' . $auteur_info['nom']); ?></h1>
        
        <div id="message-container"></div>

        <h2 class="mt-5 mb-4">Statistiques</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <canvas id="deletedChart"></canvas>
                </div>
            </div>
        </div>

        <button id="toggleForm" class="btn btn-primary mb-4">Afficher/Masquer le formulaire</button>

        <div id="createArticleForm" class="hidden mb-5">
            <h2 class="mb-4">Créer un nouvel article</h2>
            <form id="newArticleForm" action="?id=<?php echo htmlspecialchars($auteur_id); ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titre">Titre</label>
                    <input type="text" class="form-control" id="titre" name="titre" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="contenu">Contenu</label>
                    <textarea class="form-control" id="contenu" name="contenu" required></textarea>
                </div>
                <div class="form-group">
                    <label for="categorie">Catégorie</label>
                    <select class="form-control" id="categorie" name="categorie" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?php echo $categorie['id']; ?>">
                                <?php echo htmlspecialchars($categorie['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="image">Image</label>
                    <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                </div>
                <button type="submit" name="create" class="btn btn-primary">Créer l'article</button>
            </form>
        </div>

        <h2 class="mt-5 mb-4">Mes articles</h2>
        <div id="articlesSection">
            <div class="mb-4">
                <label for="searchArticle">Rechercher un article:</label>
                <input type="text" id="searchArticle" class="form-control" placeholder="Entrez le titre de l'article">
            </div>

            <div id="articlesList" class="row">
                <?php if (empty($articles)): ?>
                    <p class="col-12 alert alert-info">Aucun article trouvé.</p>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                        <div class="col-md-4 mb-4 article-card" data-category="<?php echo $article['id']; ?>">
                            <div class="card h-100">
                                <?php if (!empty($article['image'])): ?>
                                    <?php
                                    $image_path = $article['image'];
                                    if (!file_exists($image_path) && file_exists('../../' . $image_path)) {
                                        $image_path = '../../' . $image_path;
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($article['titre']); ?>">
                                <?php else: ?>
                                    <img src="/assets/images/placeholder.jpg" class="card-img-top" alt="Image par défaut">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($article['titre']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($article['description'], 0, 100)) . '...'; ?></p>
                                    <p class="card-text"><small class="text-muted">Catégorie: <?php echo htmlspecialchars($article['nom'] ?? 'Non catégorisé'); ?></small></p>
                                </div>
                                <div class="card-footer">
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editModal<?php echo $article['id']; ?>">
                                        Modifier
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal<?php echo $article['id']; ?>">
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Modal pour modifier l'article -->
                        <div class="modal fade" id="editModal<?php echo $article['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $article['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editModalLabel<?php echo $article['id']; ?>">Modifier l'article</h5>
                                        <button type="button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="?id=<?php echo htmlspecialchars($auteur_id); ?>" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                            <div class="form-group">
                                                <label for="titre<?php echo $article['id']; ?>">Titre</label>
                                                <input type="text" class="form-control" id="titre<?php echo $article['id']; ?>" name="titre" value="<?php echo htmlspecialchars($article['titre']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="description<?php echo $article['id']; ?>">Description</label>
                                                <textarea class="form-control" id="description<?php echo $article['id']; ?>" name="description" required><?php echo htmlspecialchars($article['description']); ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="contenu<?php echo $article['id']; ?>">Contenu</label>
                                                <textarea class="form-control" id="contenu<?php echo $article['id']; ?>" name="contenu" required><?php echo htmlspecialchars($article['contenu']); ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="categorie<?php echo $article['id']; ?>">Catégorie</label>
                                                <select class="form-control" id="categorie<?php echo $article['id']; ?>" name="categorie" required>
                                                    <?php foreach ($categories as $categorie): ?>
                                                        <option value="<?php echo $categorie['id']; ?>" <?php echo ($categorie['id'] == $article['id_categorie']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($categorie['nom']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="image<?php echo $article['id']; ?>">Nouvelle image (optionnel)</label>
                                                <input type="file" class="form-control-file" id="image<?php echo $article['id']; ?>" name="image" accept="image/*">
                                            </div>
                                            <button type="submit" name="update" class="btn btn-primary">Mettre à jour</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal pour supprimer l'article -->
                        <div class="modal fade" id="deleteModal<?php echo $article['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?php echo $article['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $article['id']; ?>">Confirmer la suppression</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        Êtes-vous sûr de vouloir supprimer cet article ?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                        <form action="?id=<?php echo htmlspecialchars($auteur_id); ?>" method="POST">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-danger">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleFormButton = document.getElementById('toggleForm');
            const createArticleForm = document.getElementById('createArticleForm');
            const searchInput = document.getElementById('searchArticle');
            const articles = document.querySelectorAll('.article-card');

            toggleFormButton.addEventListener('click', function() {
                createArticleForm.classList.toggle('hidden');
            });

            searchInput.addEventListener('input', filterArticles);

            function filterArticles() {
                const searchTerm = searchInput.value.toLowerCase();
                let visibleCount = 0;

                $('.article-card').each(function() {
                    const article = $(this);
                    const title = article.find('.card-title').text().toLowerCase();
                    const shouldShow = title.includes(searchTerm);
                    
                    if (shouldShow) {
                        article.show();
                        visibleCount++;
                    } else {
                        article.hide();
                    }
                });

                if (visibleCount === 0) {
                    if ($('#noResultsMessage').length === 0) {
                        $('#articlesList').prepend('<p id="noResultsMessage" class="col-12 alert alert-info">Aucun article ne correspond à votre recherche.</p>');
                    } else {
                        $('#noResultsMessage').show();
                    }
                } else {
                    $('#noResultsMessage').hide();
                }
            }

            // Initial filter application
            filterArticles();

            // Création des graphiques
            const statusChart = new Chart(document.getElementById('statusChart'), {
                type: 'pie',
                data: {
                    labels: ['Confirmé', 'Non confirmé'],
                    datasets: [{
                        data: [<?php echo $status_stats['confirme']; ?>, <?php echo $status_stats['non_confirme']; ?>],
                        backgroundColor: ['#36A2EB', '#FF6384']
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Statut des articles'
                    }
                }
            });

            const categoryChart = new Chart(document.getElementById('categoryChart'), {
                type: 'pie',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($cat) { return "'" . addslashes($cat['category_name']) . "'"; }, $category_stats)); ?>],
                    datasets: [{
                        data: [<?php echo implode(', ', array_column($category_stats, 'article_count')); ?>],
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Articles par catégorie'
                    }
                }
            });

            const deletedChart = new Chart(document.getElementById('deletedChart'), {
                type: 'pie',
                data: {
                    labels: ['Supprimés', 'Actifs'],
                    datasets: [{
                        data: [<?php echo $deleted_count; ?>, <?php echo array_sum(array_column($category_stats, 'article_count')); ?>],
                        backgroundColor: ['#FF6384', '#36A2EB']
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Articles supprimés vs actifs'
                    }
                }
            });

            // Gestion du formulaire d'ajout d'article
            $('#newArticleForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('create', '1');

                $.ajax({
                    url: '?id=<?php echo htmlspecialchars($auteur_id); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        var result = JSON.parse(response);
                        $('#message-container').html('<div class="alert alert-success">' + result.message + '</div>');
                        $('#newArticleForm')[0].reset();
                        refreshArticles();
                    },
                    error: function() {
                        $('#message-container').html('<div class="alert alert-danger">Une erreur est survenue lors de la création de l\'article.</div>');
                    }
                });
            });

            function refreshArticles() {
                $.ajax({
                    url: '?id=<?php echo htmlspecialchars($auteur_id); ?>',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        var articlesList = $('#articlesList');
                        articlesList.empty();

                        if (response.articles.length === 0) {
                            articlesList.append('<p class="col-12 alert alert-info">Aucun article trouvé.</p>');
                        } else {
                            response.articles.forEach(function(article) {
                                var articleHtml = `
                                    <div class="col-md-4 mb-4 article-card" data-category="${article.id_categorie}">
                                        <div class="card h-100">
                                            <img src="${article.image}" class="card-img-top" alt="${article.titre}">
                                            <div class="card-body">
                                                <h5 class="card-title">${article.titre}</h5>
                                                <p class="card-text">${article.description.substring(0, 100)}...</p>
                                                <p class="card-text"><small class="text-muted">Catégorie: ${article.nom_categorie || 'Non catégorisé'}</small></p>
                                            </div>
                                            <div class="card-footer">
                                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editModal${article.id}">
                                                    Modifier
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal${article.id}">
                                                    Supprimer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                articlesList.append(articleHtml);
                            });
                        }
                        filterArticles();
                    },
                    error: function() {
                        $('#message-container').html('<div class="alert alert-danger">Une erreur est survenue lors du rafraîchissement des articles.</div>');
                    }
                });
            }

            // Gestion du menu de catégories
            $('.dropdown-item').on('click', function(e) {
                e.preventDefault();
                const selectedCategory = $(this).data('category');
                
                $.ajax({
                    url: '?id=<?php echo htmlspecialchars($auteur_id); ?>&category=' + selectedCategory,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        var articlesList = $('#articlesList');
                        articlesList.empty();

                        if (response.articles.length === 0) {
                            articlesList.append('<p class="col-12 alert alert-info">Aucun article trouvé dans cette catégorie.</p>');
                        } else {
                            response.articles.forEach(function(article) {
                                var articleHtml = `
                                    <div class="col-md-4 mb-4 article-card" data-category="${article.id_categorie}">
                                        <div class="card h-100">
                                            <img src="${article.image}" class="card-img-top" alt="${article.titre}">
                                            <div class="card-body">
                                                <h5 class="card-title">${article.titre}</h5>
                                                <p class="card-text">${article.description.substring(0, 100)}...</p>
                                                <p class="card-text"><small class="text-muted">Catégorie: ${article.nom_categorie || 'Non catégorisé'}</small></p>
                                            </div>
                                            <div class="card-footer">
                                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editModal${article.id}">
                                                    Modifier
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal${article.id}">
                                                    Supprimer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                articlesList.append(articleHtml);
                            });
                        }
                    },
                    error: function() {
                        $('#message-container').html('<div class="alert alert-danger">Une erreur est survenue lors du chargement des articles de cette catégorie.</div>');
                    }
                });
            });
        });
    </script>
</body>
</html>

