<?php
// Inclusion connexion
if (file_exists('includes/db_connect.php')) {
    include 'includes/db_connect.php';
}
else {
    include 'db_connect.php';
}

session_start();

$message = "";
$messageType = ""; // 'success' or 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';

    if (!empty($username)) {
        // Sécurisation de l'entrée
        $usernameSafe = mysqli_real_escape_string($conn, $username);
        
        // Vérifier si l'utilisateur existe
        $query = "SELECT id FROM users WHERE username = '$usernameSafe'";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Générer un token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Sauvegarder le token en base
            $insert_query = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "iss", $user['id'], $token, $expires);
            
            if (mysqli_stmt_execute($stmt)) {
                 // Lien de réinitialisation (redirige vers l'ancienne page pour l'étape finale)
                $host = $_SERVER['HTTP_HOST']; // On utilise le host actuel
                $reset_link = "http://" . $host . "/reset-password.php?step=reset&token=" . $token;

                $message = "Lien de réinitialisation généré :<br><a href='$reset_link' style='color: #66c0f4; word-break: break-all;'>$reset_link</a>";
                $messageType = "success";
            } else {
                 $message = "Erreur lors de la génération du lien.";
                 $messageType = "error";
            }
        } else {
            // Pour sécurité, on peut afficher le même message ou dire "utilisateur inconnu"
            // Ici on reste simple
            $message = "Nom d'utilisateur introuvable.";
            $messageType = "error";
        }
    } else {
        $message = "Veuillez entrer un nom d'utilisateur.";
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié - stim</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Motiva+Sans:wght@400;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Styles spécifiques pour centrer et simplifier */
        /* On retire le body override pour garder le background du style.css global */
        
        .center-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
            padding: 20px;
            min-height: calc(100vh - 80px); /* Hauteur moins header */
        }
        .forgot-box {
            background: linear-gradient(145deg, rgba(27, 40, 56, 0.95) 0%, rgba(23, 26, 33, 0.98) 100%);
            backdrop-filter: blur(20px);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 0 60px rgba(0,0,0,0.4), 0 0 20px rgba(102, 192, 244, 0.1);
            border-radius: 20px; /* Adapting var(--radius-xl) */
            border: 1px solid rgba(102, 192, 244, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        /* Effet de lueur subtile */
        .forgot-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(102, 192, 244, 0.03) 0%, transparent 60%);
            animation: pulse 4s infinite ease-in-out;
            pointer-events: none;
        }

        .forgot-title {
            font-family: 'Motiva Sans', sans-serif;
            font-size: 28px;
            font-weight: 300;
            letter-spacing: 2px;
            margin-bottom: 30px;
            text-align: center;
            text-transform: uppercase;
        }
        
        .steam-input {
            /* Surcharge légère pour s'assurer du style */
            background: rgba(0, 0, 0, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
        }
        
        .steam-input:focus {
            border-color: #66c0f4 !important;
            background: rgba(0, 0, 0, 0.4) !important;
            box-shadow: 0 0 15px rgba(102, 192, 244, 0.1) !important;
        }

        .back-link {
            display: inline-block;
            color: #8f98a0;
            font-size: 13px;
            text-decoration: none;
        }
        .back-link:hover {
            color: #fff;
            transform: translateX(-3px);
        }
        
        .msg-box {
            padding: 15px;
            margin-bottom: 25px;
            font-size: 14px;
            border-radius: 4px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .msg-error {
            background: rgba(185, 55, 55, 0.2);
            border: 1px solid rgba(185, 55, 55, 0.5);
            color: #ff6b6b;
        }
        .msg-success {
            background: rgba(102, 192, 244, 0.1);
            border: 1px solid #66c0f4;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="global-header">
    <div class="header-content" style="max-width: 940px; margin: 0 auto; padding: 0;">
        <div class="logo"><h1><a href="index.php" style="text-decoration: none;"><span style="color:#fff;">S</span>TIM</a></h1></div>
    </div>
</div>

<div class="center-wrapper">
    <!-- Utilisation de classes similaires à login-wrapper mais adaptées pour centrage -->
    <div class="forgot-box" style="animation: fadeInUp 0.6s ease-out;">
        <div class="forgot-title" style="background: linear-gradient(135deg, #fff 0%, #66c0f4 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            Récupération
        </div>
        
        <?php if ($message): ?>
            <div class="msg-box <?php echo ($messageType == 'error') ? 'msg-error' : 'msg-success'; ?>" style="animation: fadeIn 0.4s ease-out;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="steam-input-group" style="animation-delay: 0.1s;">
                <label>Nom de compte</label>
                <input type="text" name="username" class="steam-input" required placeholder="Entrez votre nom d'utilisateur">
            </div>
            
            <button type="submit" class="btn-steam-login" style="margin-top: 20px; animation: slideIn 0.4s ease-out backwards; animation-delay: 0.2s;">
                Générer le lien
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; animation: fadeIn 0.8s ease-out;">
            <a href="login.php" class="back-link" style="transition: all 0.2s;">
                <span style="font-size: 14px;">←</span> Retour à la connexion
            </a>
        </div>
    </div>
</div>

</body>
</html>
