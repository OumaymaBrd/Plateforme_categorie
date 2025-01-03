<?php
session_start();
require_once __DIR__ . '/database/bdd.php';
require_once __DIR__ . '/models/user.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = $_POST['matricule'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($matricule) || empty($password)) {
        $message = "Veuillez remplir tous les champs.";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);

        $userData = $user->getUserByMatricule($matricule, $password);
        
        if ($userData) {
            if ($userData['post'] === 'auteur') {
                header("Location: assets/pages/auteur.php?id=" . $userData['id_user']);
                exit();
            } else {
                $message = "Accès non autorisé. Seuls les auteurs peuvent se connecter.";
            }
        } else {
            $message = "Matricule ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Connexion</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="matricule">Matricule</label>
                                <input type="text" class="form-control" id="matricule" name="matricule" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

