<?php
// On inclut la connexion
if (file_exists('includes/db_connect.php')) {
    include 'includes/db_connect.php';
}
else {
    include 'db_connect.php';
}

$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = md5($_POST['password']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // 1. GESTION AVATAR PAR DÉFAUT (API)
    $avatar_path = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($user);

    // 2. GESTION UPLOAD (Si fichier envoyé)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0) {
        $target_dir = "uploads/avatars/"; // Dossier de destination
        $filename = basename($_FILES["avatar"]["name"]);
        $target_file = $target_dir . $filename;

        // VULNÉRABILITÉ : Pas de vérification d'extension
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            // On enregistre le chemin COMPLET dans la BDD (ex: uploads/avatars/photo.jpg)
            $avatar_path = $target_file;
        }
        else {
            $msg = "Erreur lors de l'upload de l'image.";
        }
    }

    // 3. INSERTION BDD
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$user'");
    if (mysqli_num_rows($check) > 0) {
        $msg = "Ce nom d'utilisateur est déjà pris.";
        $msg_type = "color: #c94f4f;";
    }
    else {
        $sql = "INSERT INTO users (username, password, email, wallet_balance, avatar, is_public) 
                VALUES ('$user', '$pass', '$email', 100.00, '$avatar_path', 1)";

        if (mysqli_query($conn, $sql)) {
            header("Location: login.php?registered=1");
            exit();
        }
        else {
            $msg = "Erreur SQL : " . mysqli_error($conn);
            $msg_type = "color: #c94f4f;";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription stim</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Motiva+Sans:wght@400;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="global-header">
    <div class="header-content">
        <div class="logo"><h1><a href="store.php"><span style="color:#fff;">S</span>TIM</a></h1></div>
    </div>
</div>
<div class="login-wrapper">
    <div class="login-left" style="width:100%; border:none;">
        <div class="login-title">CRÉER UN COMPTE</div>
        <?php if ($msg): ?>
            <div style="background-color: rgba(0,0,0,0.4); padding: 10px; margin-bottom: 15px; border: 1px solid #555; <?php echo $msg_type; ?>">
                <?php echo $msg; ?>
            </div>
        <?php
endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="steam-input-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" class="steam-input" required>
            </div>
            <div class="steam-input-group">
                <label>Email</label>
                <input type="email" name="email" class="steam-input" required>
            </div>
            <div class="steam-input-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="steam-input" required>
            </div>
            <div class="steam-input-group">
                <label>Avatar (Facultatif)</label>
                <div style="background: #2a3f5a; padding: 10px;">
                    <input type="file" name="avatar" id="avatarInput" style="color: #c7d5e0;">
                </div>
            </div>
            <div id="avatarPreview" style="display:none; margin-top: 10px;">
                <img id="imgPreview" src="#" style="width: 80px; height: 80px; border: 2px solid #66c0f4; object-fit: cover;">
            </div>
            <button type="submit" class="btn-steam-login">S'inscrire</button>
        </form>
        <p style="text-align:center; margin-top:20px;">
            <a href="login.php" style="color:#b8b6b4;">Déjà un compte ?</a>
            <br>
            <a href="forgot_password.php" style="color:#b8b6b4; font-size: 12px; display: inline-block; margin-top: 5px;">Mot de passe oublié ?</a>
        </p>
    </div>
</div>
<script>
    document.getElementById('avatarInput').addEventListener('change', function(e) {
        if (e.target.files[0]) {
            let reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('imgPreview').src = ev.target.result;
                document.getElementById('avatarPreview').style.display = 'block';
            }
            reader.readAsDataURL(e.target.files[0]);
        }
    });
</script>
</body>
</html>
