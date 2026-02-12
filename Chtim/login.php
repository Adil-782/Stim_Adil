<?php
// Inclusion connexion
if (file_exists('includes/db_connect.php')) {
    include 'includes/db_connect.php';
}
else {
    include 'db_connect.php';
}

session_start();

// GESTION DÉCONNEXION (Fix session non détruite)
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Détruire toutes les variables de session
    $_SESSION = array();

    // Si on veut détruire complètement la session, effacez également le cookie de session.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finalement, détruire la session.
    session_destroy();

    // Redirection propre
    header("Location: index.php");
    exit();
}

// Si l'utilisateur est déjà connecté, on le redirige vers le magasin
if (isset($_SESSION['user_id'])) {
    header("Location: store.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password'];
    $pass_hash = md5($pass);

    // MODIF ICI : On sélectionne aussi 'avatar'
    $sql = "SELECT id, username, role, wallet_balance, avatar FROM users WHERE username = '$user' AND password = '$pass_hash'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        // Régénération de l'ID de session pour sécurité (Fix Session Fixation)
        session_regenerate_id(true);

        $row = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['wallet'] = $row['wallet_balance'];
        $_SESSION['avatar'] = $row['avatar'];

        // CHARGEMENT DES FLAGS DÉJÀ VALIDÉS (Fix Session Pollution / Manque de persistance)
        $_SESSION['completed_flags'] = []; // On repart de zéro pour ce user

        $userId = $row['id'];
        $sqlSolves = "SELECT challenge_flag FROM user_solves WHERE user_id = $userId";
        $resSolves = mysqli_query($conn, $sqlSolves);

        if ($resSolves) {
            while ($solve = mysqli_fetch_assoc($resSolves)) {
                // On stocke le format attendu par submit-flag.php
                // Note : On n'a pas les metadata (name, points, etc.) ici, mais on stocke juste le flag pour vérification
                $_SESSION['completed_flags'][] = ['flag' => $solve['challenge_flag']];
            }
        }

        header("Location: store.php");
        exit();
    }
    else {
        $message = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion stim</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Motiva+Sans:wght@400;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="global-header">
    <div class="header-content">
        <div class="logo"><h1><a href="index.php"><span style="color:#fff;">S</span>TIM</a></h1></div>
    </div>
</div>
<div class="login-wrapper">
    <div class="login-left">
        <div class="login-title">CONNEXION</div>
        <?php if ($message): ?>
            <div style="background-color: #c94f4f; color: white; padding: 10px; margin-bottom: 15px;">
                <?php echo $message; ?>
            </div>
        <?php
endif; ?>
        <form method="POST" action="">
            <div class="steam-input-group">
                <label>Nom de compte</label>
                <input type="text" name="username" class="steam-input" required>
            </div>
            <div class="steam-input-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="steam-input" required>
            </div>
            <div style="text-align: right; margin-top: -10px; margin-bottom: 15px;">
                <a href="forgot_password.php" style="color: #b8b6b4; font-size: 12px; text-decoration: none;">Mot de passe oublié ?</a>
            </div>
            <button type="submit" class="btn-steam-login">Se connecter</button>
        </form>
    </div>
    <div class="login-right">
        <div style="margin-bottom: 40px;">
            <p style="color:#1999ff; font-size:12px;">CONNEXION VIA QR</p>
            <div class="qr-placeholder"><div style="width:130px; height:130px; background: radial-gradient(#000 30%, transparent 31%); background-size: 10px 10px; opacity: 0.8;"></div></div>
        </div>
        <div style="border-top: 1px solid #333; padding-top: 20px;">
            <p style="color:#b8b6b4; font-size:12px; margin-bottom: 15px;">Nouveau sur stim ?</p>
            <a href="register.php" class="btn-steam-register">Créer un compte</a>
        </div>
    </div>
</div>
</body>
</html>
