<?php
require_once __DIR__ . '/../../models/Admin.php';
require_once __DIR__ . '/../../database/bdd.php';

// Initialize the database connection
$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'confirmer_article':
                if (isset($_POST['article_id'])) {
                    $result = $admin->confirmerArticle($_POST['article_id']);
                    if ($result) {
                        $message = "Article confirmé avec succès.";
                    } else {
                        $error = "Erreur lors de la confirmation de l'article.";
                    }
                }
                break;
            case 'bloquer_utilisateur':
                if (isset($_POST['user_id']) && isset($_POST['motif'])) {
                    $result = $admin->bloquerProfil($_POST['user_id'], $_POST['motif']);
                    if ($result) {
                        $message = "Utilisateur bloqué avec succès.";
                    } else {
                        $error = "Erreur lors du blocage de l'utilisateur.";
                    }
                }
                break;
            case 'ajouter_categorie':
                if (isset($_POST['nom']) && isset($_POST['description'])) {
                    $result = $admin->ajouterCategorie($_POST['nom'], $_POST['description']);
                    if ($result) {
                        $message = "Catégorie ajoutée avec succès.";
                    } else {
                        $error = "Erreur lors de l'ajout de la catégorie.";
                    }
                }
                break;
        }
    }
}

// Fetch data
$articles = $admin->getArticles('non_confirme');
$users = $admin->consulterProfils();
$categories = $admin->getCategories();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord administrateur</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Tableau de bord administrateur</h1>

        <?php if (isset($message)): ?>
            <div class="alert alert-success" role="alert">
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
                <a class="nav-link active" id="articles-tab" data-toggle="tab" href="#articles" role="tab">Articles</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab">Utilisateurs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="categories-tab" data-toggle="tab" href="#categories" role="tab">Catégories</a>
            </li>
        </ul>

        <div class="tab-content mt-3" id="myTabContent">
            <div class="tab-pane fade show active" id="articles" role="tabpanel">
                <h2>Articles en attente de confirmation</h2>
                <?php if (empty($articles)): ?>
                    <p>Aucun article en attente de confirmation.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Auteur</th>
                                <th>Catégorie</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($article['titre']); ?></td>
                                <td><?php echo htmlspecialchars($article['nom_auteur']); ?></td>
                                <td><?php echo htmlspecialchars($article['nom_categorie']); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="confirmer_article">
                                        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Confirmer</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="users" role="tabpanel">
                <h2>Gestion des utilisateurs</h2>
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
                                <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['post']); ?></td>
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

            <div class="tab-pane fade" id="categories" role="tabpanel">
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['nom']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                            </tr>
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
</body>
</html>