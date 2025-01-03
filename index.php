<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/models/user.php';
require_once __DIR__ . '/database/bdd.php';
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $prenom = trim($_POST['prenom']);
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $tel = trim($_POST['tel']);
    $password = $_POST['password'];
    $post = trim($_POST['post']);

    if ($user->register($prenom, $nom, $email, $tel, $password, $post)) {
        $success_message = "Inscription réussie!";
    } else {
        $error_message = "Erreur lors de l'inscription.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $matricule = trim($_POST['matricule']);
    $password = $_POST['login_password'];

    $result = $user->getUserByMatricule($matricule, $password);
    if ($result) {
        $_SESSION['user'] = $result;
        if ($result['post'] === 'admin') {
            header("Location: assets/pages/administration.php");
        } else if ($result['post'] === 'auteur') {
            header("Location: assets/pages/auteur.php?id=" . $result['id_user']);
        } else if ($result['post'] === 'reader') {
            // Redirect to reader page if needed
            // header("Location: assets/pages/reader.php");
        }
        exit();
    } else {
        $login_error = "Matricule ou mot de passe incorrect.";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <title>Netflix-Style Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v2.1.9/css/unicons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style/style_index.css">
</head>
<body>
    <div class="section">
        <div class="container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success text-center" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="row full-height justify-content-center">
                <div class="col-12 text-center align-self-center py-5">
                    <div class="section pb-5 pt-5 pt-sm-2 text-center">
                        <h6 class="mb-0 pb-3"><span>Log In </span><span>Sign Up</span></h6>
                        <input class="checkbox" type="checkbox" id="reg-log" name="reg-log"/>
                        <label for="reg-log"></label>
                        <div class="card-3d-wrap mx-auto">
                            <div class="card-3d-wrapper">
                                <div class="card-front">
                                    <div class="center-wrap">
                                        <div class="section text-center">
                                            <h4 class="mb-4 pb-3">Log In</h4>
                                            <form method="POST" action="">
                                                <div class="form-group">
                                                    <input type="text" name="matricule" class="form-style" 
                                                           placeholder="Votre Matricule" required>
                                                    <i class="input-icon uil uil-user"></i>
                                                </div>    
                                                <div class="form-group mt-2">
                                                    <input type="password" name="login_password" class="form-style" 
                                                           placeholder="Password" required>
                                                    <i class="input-icon uil uil-lock-alt"></i>
                                                </div>
                                                <button type="submit" name="login" class="btn mt-4">Login</button>
                                                <?php if (isset($login_error)): ?>
                                                    <p class="text-danger mt-3"><?php echo htmlspecialchars($login_error); ?></p>
                                                <?php endif; ?>
                                                <p class="mb-0 mt-4 text-center">
                                                    <a href="#" class="link">Forgot your password?</a>
                                                </p>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-back">
                                    <div class="center-wrap">
                                        <div class="section text-center">
                                            <h4 class="mb-3 pb-3">Sign Up</h4>
                                            <form method="POST" action="">
                                                <div class="form-group">
                                                    <input type="text" name="prenom" class="form-style" placeholder="Votre Prenom" required>
                                                    <i class="input-icon uil uil-user"></i>
                                                </div>    
                                                <div class="form-group">
                                                    <input type="text" name="nom" class="form-style" placeholder="Votre Nom" required>
                                                    <i class="input-icon uil uil-user"></i>
                                                </div>    
                                                <div class="form-group mt-2">
                                                    <input type="tel" name="tel" class="form-style" placeholder="Votre Num tel" required>
                                                    <i class="input-icon uil uil-phone"></i>
                                                </div>    
                                                <div class="form-group mt-2">
                                                    <input type="email" name="email" class="form-style" placeholder="Email" required>
                                                    <i class="input-icon uil uil-at"></i>
                                                </div>
                                                <div class="form-group mt-2">
                                                    <input type="password" name="password" class="form-style" placeholder="Password" required>
                                                    <i class="input-icon uil uil-lock-alt"></i>
                                                </div>
                                                <div class="form-group mt-2">
                                                    <select name="post" class="form-style" required>
                                                        <option value="">Select Post</option>
                                                        <option value="auteur">Auteur</option>
                                                        <option value="reader">Reader</option>
                                                    </select>
                                                    <i class="input-icon uil uil-briefcase"></i>
                                                </div>
                                                <button type="submit" name="register" class="btn mt-4">Register</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

