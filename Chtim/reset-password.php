<?php
// Challenge 1: Host Header Injection (CWE-640)
// Créateur: Nassim
// Difficulté: ★★★★★

include 'includes/db_connect.php';

$step = isset($_GET['step']) ? $_GET['step'] : 'request';
$message = '';
$error = '';

// STEP 1: Demande de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'request') {
    $username = $_POST['username'] ?? '';

    if (!empty($username)) {
        // Vérifier si l'utilisateur existe
        $query = "SELECT id, email FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Générer un token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Sauvegarder le token en base
            $insert_query = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "iss", $user['id'], $token, $expires);
            mysqli_stmt_execute($insert_stmt);

            // VULNÉRABILITÉ (Niveau 2): Support mal implémenté des proxies inverses
            // Pour simuler un environnement "sécurisé" par défaut, on force localhost
            // Mais on fait quand même confiance à l'en-tête X-Forwarded-Host s'il est présent !
            $host = "localhost";
            
            if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
            }
            
            $reset_link = "http://" . $host . "/reset-password.php?step=reset&token=" . $token;

            // Simuler l'envoi d'email (en réalité, afficher le lien)
            $message = "Lien de réinitialisation généré (normalement envoyé par email):<br><br>";
            $message .= "<code style='background: rgba(0,255,0,0.1); padding: 10px; display: block; border-radius: 5px;'>";
            $message .= htmlspecialchars($reset_link);
            $message .= "</code><br><br>";
            $message .= "<small style='color: var(--text-muted);'>🎯 Conseil: Essayez de manipuler l'en-tête HTTP 'Host' avec Burp Suite ou cURL...</small>";
        }
        else {
            $error = "Utilisateur introuvable.";
        }
    }
    else {
        $error = "Veuillez entrer un nom d'utilisateur.";
    }
}

// STEP 2: Réinitialisation du mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'reset') {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($token) && !empty($new_password) && $new_password === $confirm_password) {
        // Vérifier le token
        $query = "SELECT user_id FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($token_data = mysqli_fetch_assoc($result)) {
            // Mettre à jour le mot de passe (vulnérable: MD5)
            $hashed = md5($new_password);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $hashed, $token_data['user_id']);
            mysqli_stmt_execute($update_stmt);

            // Marquer le token comme utilisé
            $mark_query = "UPDATE password_reset_tokens SET used = 1 WHERE token = ?";
            $mark_stmt = mysqli_prepare($conn, $mark_query);
            mysqli_stmt_bind_param($mark_stmt, "s", $token);
            mysqli_stmt_execute($mark_stmt);

            $message = "✅ Mot de passe réinitialisé avec succès !<br><br>";
            $message .= "<div style='background: rgba(102, 192, 244, 0.1); padding: 15px; border-radius: 8px; border-left: 3px solid var(--accent-blue);'>";
            $message .= "<strong>🚩 FLAG:</strong> <code>STEAM{H0ST_H34D3R_P0WN3D}</code>";
            $message .= "</div>";
        }
        else {
            $error = "Token invalide ou expiré.";
        }
    }
    else {
        $error = "Erreur: Vérifiez que les mots de passe correspondent.";
    }
}

include 'includes/header.php';
?>

<style>
    .reset-container {
        max-width: 600px;
        margin: 60px auto;
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        padding: 40px;
        border: 1px solid rgba(102, 192, 244, 0.2);
    }

    .reset-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .reset-header h1 {
        font-size: 28px;
        background: linear-gradient(135deg, #ff6b6b, var(--accent-cyan));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 10px;
    }

    .challenge-info {
        background: rgba(255, 107, 107, 0.1);
        border-left: 3px solid #ff6b6b;
        padding: 15px;
        border-radius: 0 8px 8px 0;
        margin-bottom: 25px;
        font-size: 13px;
        color: var(--text-muted);
    }

    .challenge-info strong {
        color: #ff6b6b;
        display: block;
        margin-bottom: 5px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 14px;
    }

    .form-group input {
        width: 100%;
        padding: 12px 15px;
        background: var(--bg-dark);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-md);
        color: var(--text-primary);
        font-size: 14px;
        transition: var(--transition-normal);
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(102, 192, 244, 0.1);
    }

    .submit-btn {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, var(--accent-blue-light), var(--accent-blue-dark));
        border: none;
        border-radius: var(--radius-md);
        color: white;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: var(--transition-normal);
        text-transform: uppercase;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 192, 244, 0.4);
    }

    .message {
        background: rgba(72, 219, 251, 0.1);
        border-left: 3px solid var(--accent-cyan);
        padding: 15px;
        border-radius: 0 8px 8px 0;
        margin-bottom: 20px;
        color: var(--text-primary);
    }

    .error {
        background: rgba(255, 107, 107, 0.1);
        border-left: 3px solid #ff6b6b;
        padding: 15px;
        border-radius: 0 8px 8px 0;
        margin-bottom: 20px;
        color: #ff6b6b;
    }

    .hint-box {
        background: rgba(0, 0, 0, 0.3);
        padding: 12px;
        border-radius: var(--radius-md);
        margin-top: 20px;
        font-size: 12px;
        color: var(--text-muted);
    }

    .hint-box strong {
        color: var(--accent-green-light);
    }

    .back-link {
        display: inline-block;
        margin-top: 20px;
        color: var(--accent-blue);
        text-decoration: none;
        font-size: 14px;
    }

    .back-link:hover {
        text-decoration: underline;
    }
</style>

<div class="reset-container">
    <div class="reset-header">
        <h1>🔐 Password Recovery Heist</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Challenge créé par Nassim</p>
    </div>

    <div class="challenge-info">
        <strong>🎯 CHALLENGE CTF</strong>
        <div>CWE-640: Weakness in Password Recovery Mechanism (Host Header Injection)</div>
        <div>Difficulté: ★★★★★ | Points: 500</div>
    </div>

    <?php if ($message): ?>
        <div class="message">
            <?php echo $message; ?>
        </div>
    <?php
endif; ?>

    <?php if ($error): ?>
        <div class="error">❌
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php
endif; ?>

    <?php if ($step === 'request'): ?>
        <!-- STEP 1: Request Reset -->
        <form method="POST" action="?step=request">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" placeholder="Entrez votre nom d'utilisateur" required>
            </div>

            <button type="submit" class="submit-btn">Demander la réinitialisation</button>
        </form>

        <div class="hint-box">
            <strong>💡 Indice:</strong> Le serveur utilise l'en-tête HTTP Host pour construire le lien de réinitialisation.
            Que se passe-t-il si vous modifiez cet en-tête ?<br>
            <code style="color: var(--accent-cyan);">curl -H "Host: attacker.com" -X POST ...</code>
        </div>

    <?php
elseif ($step === 'reset'): ?>
        <!-- STEP 2: Reset Password -->
        <form method="POST" action="?step=reset">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">

            <div class="form-group">
                <label for="new_password">Nouveau mot de passe</label>
                <input type="password" id="new_password" name="new_password" placeholder="Entrez le nouveau mot de passe"
                    required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmez le mot de passe"
                    required>
            </div>

            <button type="submit" class="submit-btn">Réinitialiser le mot de passe</button>
        </form>
    <?php
endif; ?>

    <a href="challenges.php" class="back-link">← Retour aux challenges</a>
</div>

<?php include 'includes/footer.php'; ?>
