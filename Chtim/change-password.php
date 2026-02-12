<?php
session_start();
include 'includes/db_connect.php';
include 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (strlen($new_password) < 6) {
        $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérifier l'ancien mot de passe
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (md5($current_password) !== $user['password']) {
            $error = "Le mot de passe actuel est incorrect.";
        } else {
            // Mettre à jour le mot de passe
            $new_password_hash = md5($new_password);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $new_password_hash, $user_id);

            if (mysqli_stmt_execute($update_stmt)) {
                $success = "Mot de passe modifié avec succès !";
            } else {
                $error = "Erreur lors de la modification du mot de passe.";
            }
        }
    }
}
?>

<style>
    .password-container {
        max-width: 600px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .password-card {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        padding: 40px;
        border: 1px solid rgba(102, 192, 244, 0.2);
    }

    .password-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .password-header h1 {
        font-size: 28px;
        background: linear-gradient(135deg, #fff 0%, var(--accent-cyan) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 10px;
    }

    .password-header p {
        color: var(--text-muted);
        font-size: 14px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 14px;
    }

    .form-group input {
        width: 100%;
        padding: 14px 18px;
        background: var(--bg-dark);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-md);
        color: var(--text-primary);
        font-size: 15px;
        transition: var(--transition-normal);
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(102, 192, 244, 0.1);
    }

    .submit-btn {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--accent-blue-light), var(--accent-blue-dark));
        border: none;
        border-radius: var(--radius-md);
        color: white;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: var(--transition-normal);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 192, 244, 0.4);
    }

    .alert {
        padding: 15px 20px;
        border-radius: var(--radius-md);
        margin-bottom: 25px;
        font-size: 14px;
    }

    .alert-success {
        background: rgba(29, 209, 161, 0.15);
        border-left: 3px solid var(--accent-green-light);
        color: var(--accent-green-light);
    }

    .alert-error {
        background: rgba(255, 107, 107, 0.15);
        border-left: 3px solid #ff6b6b;
        color: #ff6b6b;
    }

    .security-tips {
        background: rgba(0, 0, 0, 0.3);
        border-radius: var(--radius-md);
        padding: 20px;
        margin-top: 30px;
    }

    .security-tips h3 {
        color: var(--accent-cyan);
        font-size: 14px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .security-tips ul {
        color: var(--text-muted);
        font-size: 13px;
        line-height: 1.8;
        padding-left: 20px;
    }

    .back-link {
        display: inline-block;
        margin-top: 25px;
        color: var(--accent-blue);
        text-decoration: none;
        font-size: 14px;
        transition: var(--transition-normal);
    }

    .back-link:hover {
        color: var(--accent-cyan);
    }

    .password-strength {
        height: 4px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
        margin-top: 8px;
        overflow: hidden;
    }

    .password-strength-bar {
        height: 100%;
        width: 0%;
        transition: all 0.3s ease;
        border-radius: 2px;
    }

    .strength-weak {
        width: 33%;
        background: #ff6b6b;
    }

    .strength-medium {
        width: 66%;
        background: #feca57;
    }

    .strength-strong {
        width: 100%;
        background: var(--accent-green-light);
    }
</style>

<div class="password-container">
    <div class="password-card">
        <div class="password-header">
            <h1>🔒 Changer le mot de passe</h1>
            <p>Modifiez votre mot de passe pour sécuriser votre compte</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="current_password">Mot de passe actuel</label>
                <input type="password" id="current_password" name="current_password"
                    placeholder="Entrez votre mot de passe actuel" required>
            </div>

            <div class="form-group">
                <label for="new_password">Nouveau mot de passe</label>
                <input type="password" id="new_password" name="new_password"
                    placeholder="Entrez votre nouveau mot de passe" required>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strength-bar"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password"
                    placeholder="Confirmez votre nouveau mot de passe" required>
            </div>

            <button type="submit" class="submit-btn">Modifier le mot de passe</button>
        </form>

        <div class="security-tips">
            <h3>🛡️ Conseils de sécurité</h3>
            <ul>
                <li>Utilisez au moins 8 caractères</li>
                <li>Combinez lettres majuscules et minuscules</li>
                <li>Ajoutez des chiffres et des caractères spéciaux</li>
                <li>Évitez d'utiliser des informations personnelles</li>
                <li>Ne réutilisez pas vos mots de passe</li>
            </ul>
        </div>

        <a href="profile.php?id=<?php echo $user_id; ?>" class="back-link">← Retour au profil</a>
    </div>
</div>

<script>
    // Indicateur de force du mot de passe
    const newPasswordInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('strength-bar');

    newPasswordInput.addEventListener('input', function () {
        const password = this.value;
        let strength = 0;

        if (password.length >= 6) strength++;
        if (password.length >= 10) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        strengthBar.className = 'password-strength-bar';

        if (strength <= 2) {
            strengthBar.classList.add('strength-weak');
        } else if (strength <= 4) {
            strengthBar.classList.add('strength-medium');
        } else {
            strengthBar.classList.add('strength-strong');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
