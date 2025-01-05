<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir les chemins pour les images
$baseImagePath = __DIR__ . '/../images/'; // Chemin système
$webImagePath = '../images/'; // Chemin web relatif

error_log('Base image directory path: ' . $baseImagePath);
if (!is_dir($baseImagePath)) {
    error_log('WARNING: Images directory does not exist: ' . $baseImagePath);
} else {
    error_log('Images directory exists and is ' . (is_writable($baseImagePath) ? 'writable' : 'not writable'));
}

require_once __DIR__ . '/../../models/Admin.php';
require_once __DIR__ . '/../../database/bdd.php';

// Initialize the database connection
$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);

// Get the active tab and sub-tab from GET parameters or set defaults
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'articles';
$activeSubTab = isset($_GET['sub_tab']) ? $_GET['sub_tab'] : '';

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
            case 'debloquer_utilisateur':
                if (isset($_POST['user_id'])) {
                    $result = $admin->debloquerProfil($_POST['user_id']);
                    if ($result['success']) {
                        $message = "Utilisateur débloqué avec succès.";
                    } else {
                        $error = "Erreur lors du déblocage de l'utilisateur : " . $result['error'];
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
$userStatus = isset($_GET['user_status']) ? $_GET['user_status'] : 'active';

// Fetch data
$articlesResult = $admin->getArticles($articleStatus);
if ($articlesResult['success']) {
    $articles = $articlesResult['data'];
} else {
    $error = "Erreur lors de la récupération des articles : " . $articlesResult['error'];
    $articles = [];
}

if ($userStatus === 'blocked') {
    $usersResult = $admin->consulterProfilsBloques($userType);
} else {
    $usersResult = $admin->consulterProfils($userType);
}
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

// Fetch statistics
$articleStatusStats = $admin->getArticleStatusStats();
$userBlockStats = $admin->getUserBlockStats();
$articleCategoryStats = $admin->getArticleCountByCategory();

?>

<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord administrateur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../style/style_admin.css">
    <script src="../js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      
</head>
<body class="flex h-screen overflow-hidden">
    <aside class="w-64 sidebar overflow-y-auto">
        <div class="p-4">
            <h1 class="text-2xl font-bold mb-6">Admin Dashboard</h1>
            <nav>
                <ul class="space-y-2">
                    <li>
                        <a href="#articles" class="nav-link block p-2 rounded-lg <?php echo $activeTab === 'articles' ? 'active' : ''; ?>" onclick="showTab('articles')">Articles</a>
                    </li>
                    <li>
                        <a href="#users" class="nav-link block p-2 rounded-lg <?php echo $activeTab === 'users' ? 'active' : ''; ?>" onclick="showTab('users')">Utilisateurs</a>
                        <?php if ($activeTab === 'users'): ?>
                        <ul class="ml-4 mt-2 space-y-1">
                            <li><a href="?tab=users&sub_tab=auteur" class="nav-link block p-1 rounded-lg <?php echo $activeSubTab === 'auteur' ? 'active' : ''; ?>">Auteurs</a></li>
                            <li><a href="?tab=users&sub_tab=reader" class="nav-link block p-1 rounded-lg <?php echo $activeSubTab === 'reader' ? 'active' : ''; ?>">Lecteurs</a></li>
                            <li><a href="?tab=users&sub_tab=blocked" class="nav-link block p-1 rounded-lg <?php echo $activeSubTab === 'blocked' ? 'active' : ''; ?>">Bloqués</a></li>
                        </ul>
                        <?php endif; ?>
                    </li>
                    <li>
                        <a href="#categories" class="nav-link block p-2 rounded-lg <?php echo $activeTab === 'categories' ? 'active' : ''; ?>" onclick="showTab('categories')">Catégories</a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="p-4">
            <h2 class="text-xl font-semibold mb-4">Statistiques</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-medium mb-2">Statut des articles</h3>
                    <canvas id="articleStatusChart"></canvas>
                </div>
                <div>
                    <h3 class="text-sm font-medium mb-2">Statut des utilisateurs</h3>
                    <canvas id="userBlockChart"></canvas>
                </div>
                <div>
                    <h3 class="text-sm font-medium mb-2">Articles par catégorie</h3>
                    <canvas id="articleCategoryChart"></canvas>
                </div>
            </div>
        </div>
    </aside>
    <main class="flex-1 overflow-x-hidden overflow-y-auto main-content">
        <div class="container mx-auto px-6 py-8">
            <h1 class="text-3xl font-semibold mb-6">Tableau de bord administrateur</h1>

            <?php if (isset($message)): ?>
                <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <div class="bg-card text-card-foreground rounded-lg shadow-md">
                <div class="border-b border-border">
                    <nav class="-mb-px flex">
                        <a href="#articles" class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm leading-5 nav-link <?php echo $activeTab === 'articles' ? 'active' : ''; ?>" onclick="showTab('articles')">
                            Articles
                        </a>
                        <a href="#users" class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm leading-5 nav-link <?php echo $activeTab === 'users' ? 'active' : ''; ?>" onclick="showTab('users')">
                            Utilisateurs
                        </a>
                        <a href="#categories" class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm leading-5 nav-link <?php echo $activeTab === 'categories' ? 'active' : ''; ?>" onclick="showTab('categories')">
                            Catégories
                        </a>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Articles Tab Content -->
                    <div id="articles" class="tab-content <?php echo $activeTab === 'articles' ? '' : 'hidden'; ?>">
                        <h2 class="text-2xl font-semibold mb-4">Gestion des articles</h2>
                        <div class="mb-4 space-x-2">
                            <a href="?tab=articles&article_status=confirme" class="inline-block bg-primary text-primary-foreground px-4 py-2 rounded-md text-sm font-medium <?php echo $articleStatus === 'confirme' ? 'active' : ''; ?>">Articles confirmés</a>
                            <a href="?tab=articles&article_status=nom confirme" class="inline-block bg-secondary text-secondary-foreground px-4 py-2 rounded-md text-sm font-medium <?php echo $articleStatus === 'nom confirme' ? 'active' : ''; ?>">Articles non confirmés</a>
                            <a href="?tab=articles" class="inline-block bg-accent text-accent-foreground px-4 py-2 rounded-md text-sm font-medium <?php echo $articleStatus === null ? 'active' : ''; ?>">Tous les articles</a>
                        </div>
                        <?php if (empty($articles)): ?>
                            <p class="text-muted-foreground">Aucun article trouvé.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left table">
                                    <thead class="text-xs uppercase bg-muted">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Image</th>
                                            <th scope="col" class="px-6 py-3">Titre</th>
                                            <th scope="col" class="px-6 py-3">Description</th>
                                            <th scope="col" class="px-6 py-3">Auteur</th>
                                            <th scope="col" class="px-6 py-3">Catégorie</th>
                                            <th scope="col" class="px-6 py-3">Statut</th>
                                            <th scope="col" class="px-6 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($articles as $article): ?>
                                        <tr class="border-b border-border">
                                            <td class="px-6 py-4">
                                                <?php
                                                if (!empty($article['image'])) {
                                                    if ($article['image_type'] === 'path') {
                                                        $fullImagePath = $baseImagePath . basename($article['image']);
                                                        $webPath = $webImagePath . basename($article['image']);

                                                        if (file_exists($fullImagePath)) {
                                                            echo '<img src="' . htmlspecialchars($webPath) . '" alt="Image de l\'article" class="article-image" onclick="openImageModal(\'' . htmlspecialchars($webPath) . '\')">';
                                                        } else {
                                                            echo 'Image non trouvée: ' . htmlspecialchars(basename($article['image']));
                                                        }
                                                    } else if ($article['image_type'] === 'blob') {
                                                        echo '<img src="data:image/jpeg;base64,' . base64_encode($article['image']) . '" alt="Image de l\'article" class="article-image" onclick="openImageModal(this.src)">';
                                                    }
                                                } else {
                                                    echo 'Pas d\'image';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($article['titre'] ?? ''); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($article['description'] ?? ''); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? '')); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($article['nom_categorie'] ?? ''); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($article['statut'] ?? ''); ?></td>
                                            <td class="px-6 py-4">
                                                <?php if ($article['statut'] == 'nom confirme'): ?>
                                                    <form method="POST" class="inline-block">
                                                        <input type="hidden" name="action" value="confirmer_article">
                                                        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-2 rounded text-xs">Confirmer</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="inline-block">
                                                        <input type="hidden" name="action" value="annuler_confirmation_article">
                                                        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-2 rounded text-xs">Annuler confirmation</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?');">
                                                    <input type="hidden" name="action" value="supprimer_article">
                                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded text-xs">Supprimer</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Users Tab Content -->
                    <div id="users" class="tab-content <?php echo $activeTab === 'users' ? '' : 'hidden'; ?>">
                        <h2 class="text-2xl font-semibold mb-4">Gestion des utilisateurs</h2>
                        <div class="mb-4 space-x-2">
                            <a href="?tab=users&user_type=auteur" class="inline-block bg-primary text-primary-foreground px-4 py-2 rounded-md text-sm font-medium <?php echo $userType === 'auteur' ? 'active' : ''; ?>">Auteurs</a>
                            <a href="?tab=users&user_type=reader" class="inline-block bg-secondary text-secondary-foreground px-4 py-2 rounded-md text-sm font-medium <?php echo $userType === 'reader' ? 'active' : ''; ?>">Lecteurs</a>
                            <a href="?tab=users&user_status=blocked" class="inline-block bg-destructive text-destructive-foreground px-4 py-2 rounded-md text-sm font-medium <?php echo $userStatus === 'blocked' ? 'active' : ''; ?>">Utilisateurs bloqués</a>
                            <a href="?tab=users" class="inline-block bg-accent text-accent-foreground px-4 py-2 rounded-md text-sm font-medium <?php echo $userType === null && $userStatus === 'active' ? 'active' : ''; ?>">Tous les utilisateurs</a>
                        </div>
                        <?php if (empty($users)): ?>
                            <p class="text-muted-foreground">Aucun utilisateur trouvé.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left table">
                                    <thead class="text-xs uppercase bg-muted">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Nom</th>
                                            <th scope="col" class="px-6 py-3">Email</th>
                                            <th scope="col" class="px-6 py-3">Type</th>
                                            <?php if ($userStatus === 'blocked'): ?>
                                                <th scope="col" class="px-6 py-3">Motif du blocage</th>
                                            <?php endif; ?>
                                            <th scope="col" class="px-6 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr class="border-b border-border">
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($user['nom'] ?? ''); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($user['post'] ?? ''); ?></td>
                                            <?php if ($userStatus === 'blocked'): ?>
                                                <td class="px-6 py-4"><?php echo htmlspecialchars($user['motif_supprime'] ?? ''); ?></td>
                                            <?php endif; ?>
                                            <td class="px-6 py-4">
                                                <?php if ($userStatus === 'blocked'): ?>
                                                    <form method="POST" class="inline-block">
                                                        <input type="hidden" name="action" value="debloquer_utilisateur">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-2 rounded text-xs">Débloquer</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded text-xs" onclick="openBlockUserModal(<?php echo $user['id']; ?>)">
                                                        Bloquer
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Categories Tab Content -->
                    <div id="categories" class="tab-content <?php echo $activeTab === 'categories' ? '' : 'hidden'; ?>">
                        <h2 class="text-2xl font-semibold mb-4">Gestion des catégories</h2>
                        <button type="button" class="bg-primary text-primary-foreground px-4 py-2 rounded-md text-sm font-medium mb-4" onclick="openAddCategoryModal()">
                            Ajouter une catégorie
                        </button>

                        <?php if (empty($categories)): ?>
                            <p class="text-muted-foreground">Aucune catégorie trouvée.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left table">
                                    <thead class="text-xs uppercase bg-muted">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Nom</th>
                                            <th scope="col" class="px-6 py-3">Description</th>
                                            <th scope="col" class="px-6 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                        <tr class="border-b border-border">
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($category['nom'] ?? ''); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                            <td class="px-6 py-4">
                                                <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-2 rounded text-xs" onclick="openEditCategoryModal(<?php echo $category['id']; ?>, '<?php echo addslashes($category['nom']); ?>', '<?php echo addslashes($category['description']); ?>')">
                                                    Modifier
                                                </button>
                                                <form method="POST" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                                    <input type="hidden" name="action" value="supprimer_categorie">
                                                    <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded text-xs">Supprimer</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal pour l'image zoomée -->
    <div id="imageModal" class="image-modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="zoomedImage">
    </div>

    <!-- Modal pour bloquer un utilisateur -->
    <div id="blockUserModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Bloquer l'utilisateur
                    </h3>
                    <div class="mt-2">
                        <form id="blockUserForm" method="POST">
                            <input type="hidden" name="action" value="bloquer_utilisateur">
                            <input type="hidden" name="user_id" id="blockUserId">
                            <div class="mb-4">
                                <label for="motif" class="block text-sm font-medium text-gray-700">Motif du blocage:</label>
                                <textarea id="motif" name="motif" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required></textarea>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" form="blockUserForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Bloquer
                    </button>
                    <button type="button" onclick="closeBlockUserModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter une catégorie -->
    <div id="addCategoryModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Ajouter une catégorie
                    </h3>
                    <div class="mt-2">
                    export default function StyledForm() {
  return (
    <form id="addCategoryForm" method="POST" className="bg-black p-6 rounded-lg">
      <input type="hidden" name="action" value="ajouter_categorie" />
      <div className="mb-4">
        <label htmlFor="nom" className="block text-sm font-medium text-red-500 mb-2">
          Nom de la catégorie:
        </label>
        <input
          type="text"
          id="nom"
          name="nom"
          className="mt-1 block w-full rounded-md border-gray-600 bg-gray-800 text-white placeholder-gray-400 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50"
          required
        />
      </div>
      <div className="mb-4">
        <label htmlFor="description" className="block text-sm font-medium text-red-500 mb-2">
          Description:
        </label>
        <textarea
          id="description"
          name="description"
          rows={3}
          className="mt-1 block w-full rounded-md border-gray-600 bg-gray-800 text-white placeholder-gray-400 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50"
          required
        ></textarea>
      </div>
      <button
        type="submit"
        className="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50"
      >
        Ajouter la catégorie
      </button>
    </form>
  )
}


                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" form="addCategoryForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Ajouter
                    </button>
                    <button type="button" onclick="closeAddCategoryModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour modifier une catégorie -->
    <div id="editCategoryModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Modifier la catégorie
                    </h3>
                    <div class="mt-2">
                        <form id="editCategoryForm" method="POST">
                            <input type="hidden" name="action" value="modifier_categorie">
                            <input type="hidden" name="id" id="editCategoryId">
                            <div class="mb-4">
                                <label for="editNom" class="block text-sm font-medium text-gray-700">Nom de la catégorie:</label>
                                <input type="text" id="editNom" name="nom" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                            </div>
                            <div class="mb-4">
                                <label for="editDescription" class="block text-sm font-medium text-gray-700">Description:</label>
                                <textarea id="editDescription" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required></textarea>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" form="editCategoryForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Modifier
                    </button>
                    <button type="button" onclick="closeEditCategoryModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
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
            showTab('<?php echo $activeTab; ?>');
        });

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
        window.onload = function() {
            hideSuccessMessage();
            showTab('<?php echo $activeTab; ?>');
            createCharts();
        };

        // Fonctions pour l'image zoomée
        function openImageModal(src) {
            var modal = document.getElementById("imageModal");
            var modalImg = document.getElementById("zoomedImage");

            // Assurez-vous que l'image est chargée avant d'afficher le modal
            modalImg.onload = function() {
                modal.style.display = "flex";
            };

            // Définir la source de l'image
            if (src.startsWith('data:image')) {
                modalImg.src = src; // Pour les images BLOB
            } else {
                modalImg.src = src; // Pour les images fichiers, utiliser la source directe
            }
        }

        function closeImageModal() {
            document.getElementById("imageModal").style.display = "none";
        }

        // Ajouter l'événement de clic à la croix de fermeture
        document.querySelector('.close').addEventListener('click', closeImageModal);

        // Fermer l'image zoomée en cliquant en dehors de l'image
        window.onclick = function(event) {
            var modal = document.getElementById("imageModal");
            if (event.target == modal) {
                closeImageModal();
            }
        }
        document.getElementById("imageModal").style.display = "none"; // Added to hide modal initially

        document.getElementById("zoomedImage").onerror = function() {
            console.error("Erreur de chargement de l'image");
            closeImageModal();
        };

        // Fonctions pour le modal de blocage d'utilisateur
        function openBlockUserModal(userId) {
            document.getElementById('blockUserId').value = userId;
            document.getElementById('blockUserModal').classList.remove('hidden');
        }

        function closeBlockUserModal() {
            document.getElementById('blockUserModal').classList.add('hidden');
        }

        // Fonctions pour le modal d'ajout de catégorie
        function openAddCategoryModal() {
            document.getElementById('addCategoryModal').classList.remove('hidden');
        }

        function closeAddCategoryModal() {
            document.getElementById('addCategoryModal').classList.add('hidden');
        }

        // Fonctions pour le modal de modification de catégorie
        function openEditCategoryModal(id, nom, description) {
            document.getElementById('editCategoryId').value = id;
            document.getElementById('editNom').value = nom;
            document.getElementById('editDescription').value = description;
            document.getElementById('editCategoryModal').classList.remove('hidden');
        }

        function closeEditCategoryModal() {
            document.getElementById('editCategoryModal').classList.add('hidden');
        }

        // Fonction pour afficher l'onglet sélectionné
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            document.getElementById(tabId).classList.remove('hidden');

            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector(`.nav-link[href="#${tabId}"]`).classList.add('active');

            // Mettre à jour l'URL avec le nouvel onglet actif
            history.pushState(null, '', `?tab=${tabId}`);
        }

        // Création des graphiques
        function createCharts() {
            // Graphique pour le statut des articles
            var articleStatusCtx = document.getElementById('articleStatusChart').getContext('2d');
            new Chart(articleStatusCtx, {
                type: 'pie',
                data: {
                    labels: ['Confirmés', 'Non confirmés'],
                    datasets: [{
                        data: [<?php echo $articleStatusStats['data']['confirme']; ?>, <?php echo $articleStatusStats['data']['non_confirme']; ?>],
                        backgroundColor: ['#36a2eb', '#ff6384']
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

            // Graphique pour les utilisateurs bloqués/non bloqués
            var userBlockCtx = document.getElementById('userBlockChart').getContext('2d');
            new Chart(userBlockCtx, {
                type: 'pie',
                data: {
                    labels: ['Non bloqués', 'Bloqués'],
                    datasets: [{
                        data: [<?php echo $userBlockStats['data']['non_bloques']; ?>, <?php echo $userBlockStats['data']['bloques']; ?>],
                        backgroundColor: ['#4bc0c0', '#ff9f40']
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Statut des utilisateurs'
                    }
                }
            });

            // Graphique pour le nombre d'articles par catégorie
            var articleCategoryCtx = document.getElementById('articleCategoryChart').getContext('2d');
            new Chart(articleCategoryCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($item) { return "'" . $item['category'] . "'"; }, $articleCategoryStats['data'])); ?>],
                    datasets: [{
                        label: 'Nombre d\'articles',
                        data: [<?php echo implode(', ', array_map(function($item) { return $item['count']; }, $articleCategoryStats['data'])); ?>],
                        backgroundColor: '#ff6384'
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Articles par catégorie'
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
        }
    </script>
</body>
</html>

