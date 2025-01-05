<?php
require_once __DIR__ . '/../../models/Admin.php';
require_once __DIR__ . '/../../database/bdd.php';

// Initialize the database connection
$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);

// Get the active tab from GET parameter or set default to 'articles'
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'articles';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'confirmer_article':
                if (isset($_POST['article_id'])) {
                    $result = $admin->confirmerArticle($_POST['article_id']);
                    if ($result['success']) {
                        $message = "Article confirmé avec succès.";
                    } else {
                        $error = "Erreur lors de la confirmation de l'article : " . $result['error'];
                    }
                }
                $activeTab = 'articles';
                break;
            case 'annuler_confirmation_article':
                if (isset($_POST['article_id'])) {
                    $result = $admin->annulerConfirmationArticle($_POST['article_id']);
                    if ($result['success']) {
                        $message = "Confirmation de l'article annulée avec succès.";
                    } else {
                        $error = "Erreur lors de l'annulation de la confirmation de l'article : " . $result['error'];
                    }
                }
                $activeTab = 'articles';
                break;
            case 'supprimer_article':
                if (isset($_POST['article_id'])) {
                    $result = $admin->supprimerArticle($_POST['article_id']);
                    if ($result['success']) {
                        $message = "Article supprimé avec succès.";
                    } else {
                        $error = "Erreur lors de la suppression de l'article : " . $result['error'];
                    }
                }
                $activeTab = 'articles';
                break;
            case 'bloquer_utilisateur':
                if (isset($_POST['user_id']) && isset($_POST['motif'])) {
                    $result = $admin->bloquerProfil($_POST['user_id'], $_POST['motif']);
                    if ($result['success']) {
                        $message = "Utilisateur bloqué avec succès.";
                    } else {
                        $error = "Erreur lors du blocage de l'utilisateur : " . $result['error'];
                    }
                }
                $activeTab = 'users';
                break;
            case 'ajouter_categorie':
                if (isset($_POST['nom']) && isset($_POST['description'])) {
                    $result = $admin->ajouterCategorie($_POST['nom'], $_POST['description']);
                    if ($result['success']) {
                        $message = "Catégorie ajoutée avec succès.";
                    } else {
                        $error = "Erreur lors de l'ajout de la catégorie : " . $result['error'];
                    }
                }
                $activeTab = 'categories';
                break;
            case 'modifier_categorie':
                if (isset($_POST['id']) && isset($_POST['nom']) && isset($_POST['description'])) {
                    $result = $admin->modifierCategorie($_POST['id'], $_POST['nom'], $_POST['description']);
                    if ($result['success']) {
                        $message = "Catégorie modifiée avec succès.";
                    } else {
                        $error = "Erreur lors de la modification de la catégorie : " . $result['error'];
                    }
                }
                $activeTab = 'categories';
                break;
            case 'supprimer_categorie':
                if (isset($_POST['id'])) {
                    $result = $admin->supprimerCategorie($_POST['id']);
                    if ($result['success']) {
                        $message = "Catégorie supprimée avec succès.";
                    } else {
                        $error = "Erreur lors de la suppression de la catégorie : " . $result['error'];
                    }
                }
                $activeTab = 'categories';
                break;
        }
    }
}

// Get filter parameters
$articleStatus = isset($_GET['article_status']) ? $_GET['article_status'] : null;
$userType = isset($_GET['user_type']) ? $_GET['user_type'] : null;

// Fetch data
$articlesResult = $admin->getArticles($articleStatus);
if ($articlesResult['success']) {
    $articles = $articlesResult['data'];
} else {
    $error = "Erreur lors de la récupération des articles : " . $articlesResult['error'];
    $articles = [];
}

$usersResult = $admin->consulterProfils($userType);
if ($usersResult['success']) {
    $users = $usersResult['data'];
} else {
    $error = "Erreur lors de la récupération des profils : " . $usersResult['error'];
    $users = [];
}

$categoriesResult = $admin->getCategories();
if ($categoriesResult['success']) {
    $categories = $categoriesResult['data'];
} else {
    $error = "Erreur lors de la récupération des catégories : " . $categoriesResult['error'];
    $categories = [];
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord administrateur</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .article-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Tableau de bord administrateur</h1>

        <?php if (isset($message)): ?>
            <div id="successMessage" class="alert alert-success" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'articles' ? 'active' : ''; ?>" id="articles-tab" data-toggle="tab" href="#articles" role="tab">Articles</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'users' ? 'active' : ''; ?>" id="users-tab" data-toggle="tab" href="#users" role="tab">Utilisateurs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'categories' ? 'active' : ''; ?>" id="categories-tab" data-toggle="tab" href="#categories" role="tab">Catégories</a>
            </li>
        </ul>

        <div class="tab-content mt-3" id="myTabContent">
            <div class="tab-pane fade <?php echo $activeTab === 'articles' ? 'show active' : ''; ?>" id="articles" role="tabpanel">
                <h2>Gestion des articles</h2>
                <div class="mb-3">
                    <a href="?tab=articles&article_status=confirme" class="btn btn-outline-primary">Articles confirmés</a>
                    <a href="?tab=articles&article_status=nom confirme" class="btn btn-outline-primary">Articles non confirmés</a>
                    <a href="?tab=articles" class="btn btn-outline-secondary">Tous les articles</a>
                </div>
                <?php if (empty($articles)): ?>
                    <p>Aucun article trouvé.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Titre</th>
                                <th>Description</th>
                                <th>Auteur</th>
                                <th>Catégorie</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                            <tr>
                                <td>
                                    <?php
                                    if (!empty($article['image'])) {
                                        $imagePath = '../images/' . $article['image'];
                                        if (file_exists($imagePath)) {
                                            echo '<img src="' . $imagePath . '" alt="Image de l\'article" class="article-image">';
                                        } else {
                                            echo '<img src="data:image/jpeg;base64,' . base64_encode($article['image']) . '" alt="Image de l\'article" class="article-image">';
                                        }
                                    } else {
                                        echo 'Pas d\'image';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($article['titre'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($article['description'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($article['nom_categorie'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($article['statut'] ?? ''); ?></td>
                                <td class="action-buttons">
                                    <?php if ($article['statut'] == 'nom confirme'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="confirmer_article">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">Confirmer</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="annuler_confirmation_article">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">Annuler confirmation</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?');">
                                        <input type="hidden" name="action" value="supprimer_article">
                                        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade <?php echo $activeTab === 'users' ? 'show active' : ''; ?>" id="users" role="tabpanel">
                <h2>Gestion des utilisateurs</h2>
                <div class="mb-3">
                    <a href="?tab=users&user_type=auteur" class="btn btn-outline-primary">Auteurs</a>
                    <a href="?tab=users&user_type=reader" class="btn btn-outline-primary">Lecteurs</a>
                    <a href="?tab=users" class="btn btn-outline-secondary">Tous les utilisateurs</a>
                </div>
                <?php if (empty($users)): ?>
                    <p>Aucun utilisateur trouvé.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nom'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['post'] ?? ''); ?></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#blockUserModal<?php echo $user['id']; ?>">
                                        Bloquer
                                    </button>

                                    <!-- Modal pour bloquer l'utilisateur -->
                                    <div class="modal fade" id="blockUserModal<?php echo $user['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="blockUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="blockUserModalLabel<?php echo $user['id']; ?>">Bloquer l'utilisateur</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="bloquer_utilisateur">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="form-group">
                                                            <label for="motif">Motif du blocage:</label>
                                                            <textarea class="form-control" id="motif" name="motif" required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                                        <button type="submit" class="btn btn-danger">Bloquer</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade <?php echo $activeTab === 'categories' ? 'show active' : ''; ?>" id="categories" role="tabpanel">
                <h2>Gestion des catégories</h2>
                <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#addCategoryModal">
                    Ajouter une catégorie
                </button>

                <?php if (empty($categories)): ?>
                    <p>Aucune catégorie trouvée.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['nom'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editCategoryModal<?php echo $category['id']; ?>">
                                        Modifier
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                        <input type="hidden" name="action" value="supprimer_categorie">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Modal pour modifier la catégorie -->
                            <div class="modal fade" id="editCategoryModal<?php echo $category['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel<?php echo $category['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editCategoryModalLabel<?php echo $category['id']; ?>">Modifier la catégorie</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="modifier_categorie">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <div class="form-group">
                                                    <label for="nom">Nom de la catégorie:</label>
                                                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($category['nom']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="description">Description:</label>
                                                    <textarea class="form-control" id="description" name="description" required><?php echo htmlspecialchars($category['description']); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                                <button type="submit" class="btn btn-primary">Modifier</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Modal pour ajouter une catégorie -->
                <div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addCategoryModalLabel">Ajouter une catégorie</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="ajouter_categorie">
                                    <div class="form-group">
                                        <label for="nom">Nom de la catégorie:</label>
                                        <input type="text" class="form-control" id="nom" name="nom" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="description">Description:</label>
                                        <textarea class="form-control" id="description" name="description" required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary">Ajouter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Ensure the correct tab is active after form submission
        $(document).ready(function() {
            $('#myTab a[href="#<?php echo $activeTab; ?>"]').tab('show');
        });
    </script>
    <script>
        // Fonction pour masquer le message de succès
        function hideSuccessMessage() {
            var successMessage = document.getElementById('successMessage');
            if (successMessage) {
                setTimeout(function() {
                    successMessage.style.transition = 'opacity 1s';
                    successMessage.style.opacity = '0';
                    setTimeout(function() {
                        successMessage.style.display = 'none';
                    }, 1000);
                }, 3000);
            }
        }

        // Appeler la fonction au chargement de la page
        window.onload = hideSuccessMessage;
    </script>
</body>
</html>

