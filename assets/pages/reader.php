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
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
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

// Gestion des likes/dislikes et favoris
if (isset($_POST['action']) && isset($_POST['article_id'])) {
    $action = $_POST['action'];
    $article_id = (int)$_POST['article_id'];
    $user_id = $id;
    
    if ($action === 'like' || $action === 'dislike') {
        $reader->handleLikeDislike($article_id, $user_id, $action);
    } elseif ($action === 'toggle_favorite') {
        $result = $reader->toggleFavorite($article_id, $user_id);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => $result]);
            exit;
        }
    }
    
    // Rediriger pour éviter la soumission multiple du formulaire
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header("Location: reader.php?id=$id" . (isset($_GET['view_article']) ? "&view_article=" . $_GET['view_article'] : ""));
        exit();
    }
}

// Gestion des commentaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comment'], $_POST['article_id'])) {
        $comment = trim($_POST['comment']);
        $article_id = (int)$_POST['article_id'];
        $user_id = $id;
        
        if (!empty($comment)) {
            $result = $reader->addComment($article_id, $user_id, $comment);
            if ($result) {
                header("Location: reader.php?id=$id&view_article=$article_id");
                exit();
            } else {
                $error_message = "Erreur lors de l'ajout du commentaire.";
            }
        } else {
            $error_message = "Le commentaire ne peut pas être vide.";
        }
    } elseif (isset($_POST['edit_comment'], $_POST['comment_id'])) {
        $comment = trim($_POST['edit_comment']);
        $comment_id = (int)$_POST['comment_id'];
        $article_id = (int)$_POST['article_id'];
        
        if (!empty($comment)) {
            $result = $reader->updateComment($comment_id, $comment);
            if ($result) {
                header("Location: reader.php?id=$id&view_article=$article_id");
                exit();
            } else {
                $error_message = "Erreur lors de la modification du commentaire.";
            }
        } else {
            $error_message = "Le commentaire ne peut pas être vide.";
        }
    } elseif (isset($_POST['delete_comment'], $_POST['comment_id'])) {
        $comment_id = (int)$_POST['comment_id'];
        $article_id = (int)$_POST['article_id'];
        
        $result = $reader->deleteComment($comment_id);
        if ($result) {
            header("Location: reader.php?id=$id&view_article=$article_id");
            exit();
        } else {
            $error_message = "Erreur lors de la suppression du commentaire.";
        }
    }
}

// Récupération des favoris
$favorites = $reader->getFavorites($id);

// Fonction utilitaire pour gérer les valeurs null dans htmlspecialchars
function e($value) {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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
    <style>
        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .comment-box {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .comment-actions {
            margin-top: 10px;
        }
        .favorite-icon {
            cursor: pointer;
            font-size: 1.5em;
            color: #ccc;
        }
        .favorite-icon.active {
            color: #ffc107;
        }
        /* Style pour le modal des favoris */
        #favoritesModal .modal-content {
            background-color: #000;
            color: #fff;
        }
        #favoritesModal .modal-header {
            border-bottom: 1px solid #333;
        }
        #favoritesModal .modal-footer {
            border-top: 1px solid #333;
        }
        #favoritesModal .close {
            color: #fff;
        }
        #favoritesModal .list-group-item {
            background-color: #222;
            border-color: #333;
            color: #fff;
        }
        #favoritesModal .list-group-item:hover {
            background-color: #333;
        }
        #favoritesModal .text-muted {
            color: #aaa !important;
        }
        #favoritesModal .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
        }
        #favoritesModal .btn-outline-danger:hover {
            color: #fff;
            background-color: #dc3545;
        }
        #favoritesModal .modal-title {
            color: #fff;
        }
        #favoritesModal small {
            color: #aaa;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
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
                        <a class="nav-link" href="#" data-toggle="modal" data-target="#favoritesModal">
                            <i class="fas fa-star mr-1"></i>Favoris
                            <span class="badge badge-light" id="favoritesCount"><?= count($favorites) ?></span>
                        </a>
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
                <i class="fas fa-exclamation-circle mr-2"></i><?= e($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($article)): ?>
            <!-- Filtrage par catégorie -->
            <form action="" method="get" class="mb-4">
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="form-row align-items-center justify-content-center">
                    <div class="col-auto">
                        <select name="category" class="form-control category-select">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e($category) ?>" 
                                        <?= $selectedCategory === $category ? 'selected' : '' ?>>
                                    <?= e($category) ?>
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
                                    <img src="data:image/jpeg;base64,<?= base64_encode($article['image']) ?>" 
                                         class="card-img-top article-thumbnail" 
                                         alt="Image de l'article"
                                         onerror="this.parentElement.innerHTML='<div class=\'image-placeholder\'><i class=\'fas fa-image mr-2\'></i>Image non disponible</div>'">
                                <?php elseif ($article['image_type'] === 'path' && !empty($article['image'])): ?>
                                    <img src="../../assets/images/<?= e($article['image']) ?>" 
                                         class="card-img-top article-thumbnail" 
                                         alt="Image de l'article"
                                         onerror="this.parentElement.innerHTML='<div class=\'image-placeholder\'><i class=\'fas fa-image mr-2\'></i>Image non disponible</div>'">
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="fas fa-image mr-2"></i>Image non disponible
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h3 class="card-title"><?= e($article['titre']) ?></h3>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <i class="fas fa-user mr-1"></i><?= e(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? '')) ?> | 
                                        <i class="fas fa-tag mr-1"></i><?= e($article['nom_categorie'] ?? '') ?>
                                    </h6>
                                    <p class="card-text"><?= e($article['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="?id=<?= $id ?>&view_article=<?= $article['id'] ?><?= $selectedCategory ? '&category=' . urlencode($selectedCategory) : '' ?>" 
                                           class="btn btn-primary">
                                           <i class="fas fa-book-open mr-1"></i>Lire plus
                                        </a>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                                            <button type="submit" name="action" value="toggle_favorite" class="btn btn-link p-0">
                                                <i class="fas fa-star favorite-icon <?= $reader->isFavorite($article['id'], $id) ? 'active' : '' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
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
                                       href="?id=<?= $id ?>&page=<?= $i ?><?= $selectedCategory ? '&category=' . urlencode($selectedCategory) : '' ?>">
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
                    <h1 class="card-title text-center mb-4"><?= e($article['titre']) ?></h1>
                    <h6 class="card-subtitle mb-3 text-muted text-center">
                        <i class="fas fa-user mr-1"></i><?= e(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? '')) ?> | 
                        <i class="fas fa-tag mr-1"></i><?= e($article['nom_categorie'] ?? '') ?>
                    </h6>
                    <?php if ($article['image_type'] === 'blob' && !empty($article['image'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($article['image']) ?>" 
                             class="img-fluid mb-3 article-image" 
                             alt="Image de l'article"
                             onerror="this.style.display='none'">
                    <?php elseif ($article['image_type'] === 'path' && !empty($article['image'])): ?>
                        <img src="../../assets/images/<?= e($article['image']) ?>" 
                             class="img-fluid mb-3 article-image" 
                             alt="Image de l'article"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                    <p class="lead text-center mb-4"><?= e($article['description']) ?></p>
                    <div class="article-content">
                        <?= nl2br(e($article['contenu'])) ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="reader.php?id=<?= $id ?><?= $selectedCategory ? '&category=' . urlencode($selectedCategory) : '' ?>" 
                           class="btn btn-primary">
                           <i class="fas fa-arrow-left mr-1"></i>Retour aux articles
                        </a>
                    </div>
                </div>
            </div>

            <!-- Section likes/dislikes et favoris -->
            <div class="text-center mt-4 mb-4">
                <?php 
                $likes = $reader->getArticleLikes($article['id']);
                $userLikeStatus = $reader->getUserLikeStatus($article['id'], $id);
                $isFavorite = $reader->isFavorite($article['id'], $id);
                ?>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                    <button type="submit" name="action" value="like" class="btn btn-outline-success mr-2 <?= $userLikeStatus === 'like' ? 'active' : '' ?>">
                        <i class="fas fa-thumbs-up"></i> 
                        <span id="likeCount"><?= $likes['likes'] ?? 0 ?></span>
                    </button>
                </form>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                    <button type="submit" name="action" value="dislike" class="btn btn-outline-danger mr-2 <?= $userLikeStatus === 'dislike' ? 'active' : '' ?>">
                        <i class="fas fa-thumbs-down"></i>
                        <span id="dislikeCount"><?= $likes['dislikes'] ?? 0 ?></span>
                    </button>
                </form>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                    <button type="submit" name="action" value="toggle_favorite" class="btn btn-outline-warning">
                        <i class="fas fa-star"></i> 
                        <?= $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>
                    </button>
                </form>
            </div>

            <!-- Section commentaires -->
            <div class="mt-4">
                <h3><i class="fas fa-comments mr-2"></i>Commentaires</h3>
                
                <!-- Formulaire d'ajout de commentaire -->
                <form action="" method="post" class="mb-4">
                    <div class="form-group">
                        <textarea class="form-control" name="comment" rows="3" required 
                                  placeholder="Écrivez votre commentaire ici..."></textarea>
                    </div>
                    <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i>Envoyer
                    </button>
                </form>

                <!-- Liste des commentaires -->
                <?php
                $comments = $reader->getComments($article['id']);
                if (!empty($comments)): ?>
                    <div class="comments-list">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-box">
                                <div class="comment-header">
                                    <img src="<?= e($comment['avatar_url'] ?? '/assets/images/default-avatar.png') ?>" alt="Avatar" class="comment-avatar">
                                    <h5 class="mb-0">
                                        <?= e(($comment['prenom'] ?? '') . ' ' . ($comment['nom'] ?? '')) ?>
                                    </h5>
                                </div>
                                <p><?= nl2br(e($comment['commentaire'] ?? '')) ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($comment['date_creation'] ?? '')) ?>
                                </small>
                                <?php if ($comment['id_utilisateur'] == $id): ?>
                                    <div class="comment-actions">
                                        <button class="btn btn-sm btn-outline-primary edit-comment" data-comment-id="<?= $comment['id'] ?>">
                                            <i class="fas fa-edit"></i> Modifier
                                        </button>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                            <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                                            <button type="submit" name="delete_comment" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?');">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>Aucun commentaire pour le moment.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal des favoris -->
    <div class="modal fade" id="favoritesModal" tabindex="-1" role="dialog" aria-labelledby="favoritesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="favoritesModalLabel">
                        <i class="fas fa-star text-warning mr-2"></i>Mes articles favoris
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (empty($favorites)): ?>
                        <p class="text-center">Vous n'avez pas encore d'articles favoris.</p>
                    <?php else: ?>
                        <div class="list-group" id="favoritesList">
                            <?php foreach ($favorites as $favorite): ?>
                                <div class="list-group-item" data-favorite-id="<?= $favorite['id'] ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1"><?= e($favorite['titre']) ?></h5>
                                            <p class="mb-1"><?= e($favorite['description']) ?></p>
                                            <small>
                                                <i class="fas fa-tag mr-1"></i><?= e($favorite['nom_categorie']) ?>
                                            </small>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger remove-favorite" data-article-id="<?= $favorite['id'] ?>">
                                            <i class="fas fa-trash"></i> Retirer
                                        </button>
                                    </div>
                                    <a href="?id=<?= $id ?>&view_article=<?= $favorite['id'] ?>" 
                                       class="stretched-link" 
                                       onclick="$('#favoritesModal').modal('hide');"></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="commentModal" tabindex="-1" role="dialog" aria-labelledby="commentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commentModalLabel">Ajouter un commentaire</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="commentForm">
                        <div class="form-group">
                            <label for="commentText">Votre commentaire</label>
                            <textarea class="form-control" id="commentText" rows="3" required></textarea>
                        </div>
                        <input type="hidden" id="articleId" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" id="submitComment">Envoyer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Script pour l'édition des commentaires
            $('.edit-comment').click(function() {
                var commentId = $(this).data('comment-id');
                var commentText = $(this).closest('.comment-box').find('p').text();
                var editForm = `
                    <form method="post" class="edit-comment-form">
                        <div class="form-group">
                            <textarea class="form-control" name="edit_comment" rows="3" required>${commentText}</textarea>
                        </div>
                        <input type="hidden" name="comment_id" value="${commentId}">
                        <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                        <button type="submit" class="btn btn-primary">Sauvegarder</button>
                        <button type="button" class="btn btn-secondary cancel-edit">Annuler</button>
                    </form>
                `;
                $(this).closest('.comment-box').find('p').replaceWith(editForm);
                $(this).hide();
            });

            $(document).on('click', '.cancel-edit', function() {
                var commentBox = $(this).closest('.comment-box');
                var commentText = commentBox.find('textarea').val();
                commentBox.find('.edit-comment-form').replaceWith(`<p>${commentText}</p>`);
                commentBox.find('.edit-comment').show();
            });

            // Gestion de la suppression des favoris
            $('.remove-favorite').click(function(e) {
                e.preventDefault();
                e.stopPropagation();
                var articleId = $(this).data('article-id');
                var listItem = $(this).closest('.list-group-item');
                
                if (confirm('Êtes-vous sûr de vouloir retirer cet article de vos favoris ?')) {
                    $.ajax({
                        url: 'reader.php',
                        type: 'POST',
                        data: {
                            action: 'toggle_favorite',
                            article_id: articleId
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            if (data.success) {
                                listItem.fadeOut(300, function() {
                                    $(this).remove();
                                    updateFavoritesCount();
                                    if ($('#favoritesList').children().length === 0) {
                                        $('#favoritesList').html('<p class="text-center">Vous n\'avez pas encore d\'articles favoris.</p>');
                                    }
                                });
                            }
                        },
                        error: function() {
                            alert('Une erreur est survenue lors de la suppression du favori.');
                        }
                    });
                }
            });

            function updateFavoritesCount() {
                var count = $('#favoritesList').children('.list-group-item').length;
                $('#favoritesCount').text(count);
            }
        });
    </script>
</body>
</html>

