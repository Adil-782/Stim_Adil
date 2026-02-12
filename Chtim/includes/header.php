<?php
if (session_status() === PHP_SESSION_NONE) {
    // VULNÉRABILITÉ: Cookie sans HttpOnly (Challenge 3 - Titouan)
    // Permet le vol de cookie via JavaScript dans un contexte XSS
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false, // ⚠️ VULNÉRABILITÉ INTENTIONNELLE
        'samesite' => 'Lax'
    ]);
    session_start();
}
// Note: On ne redirige plus si pas connecté pour permettre l'accès visiteur

// Rafraichissement du solde si possible
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = $_SESSION['user_id'];
    $sql_w = "SELECT wallet_balance FROM users WHERE id = $uid";
    $res_w = mysqli_query($conn, $sql_w);
    if ($res_w && $row_w = mysqli_fetch_assoc($res_w)) {
        $_SESSION['wallet'] = $row_w['wallet_balance'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>stim</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link
        href="https://fonts.googleapis.com/css2?family=Motiva+Sans:wght@400;700&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
</head>

<body>

    <div class="global-header">
        <div class="header-content">
            <div class="logo">
                <h1><a href="store.php"><span style="color:#fff;">S</span>TIM</a></h1>
            </div>
            <div class="nav-links">
                <a href="store.php">MAGASIN</a>
                <a href="challenges.php">CHALLENGES</a>
                <a href="community.php">COMMUNAUTÉ</a>
                <a href="submit.php">DÉVELOPPEURS</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" style="background: rgba(255, 107, 107, 0.2); color: #ff6b6b;">⚙️ ADMIN</a>
                <?php
endif; ?>
            </div>

            <div class="user-panel" style="display:flex; align-items:center; gap:10px;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="text-align:right;">
                        <span
                            style="font-weight:bold; color:#c7d5e0;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        |
                        <a href="login.php?logout=true">déco</a>
                        <br>
                        <span style="color:#a4d007; font-size:11px;"><?php echo $_SESSION['wallet']; ?> €</span>
                    </div>

                    <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>">
                        <div style="width: 34px; height: 34px; border: 1px solid #555;">
                            <img src="<?php echo $_SESSION['avatar']; ?>"
                                style="width:100%; height:100%; object-fit: cover;">
                        </div>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-header-login">Connexion</a>
                    <a href="register.php" class="btn-header-register">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="store-nav">
        <div class="store-nav-content">
            <a href="store.php">Votre magasin</a>
            <a href="#">Nouveautés</a>
            <a href="#">Catégories</a>
        </div>
    </div>

    <div class="container">
