<?php
session_start();

$message = "";
$messageType = "";
$loggedIn = false;

// Compte vulnérable - Mot de passe faible lié à Valve
$validUsername = "adil_dev";
$validPassword = "glados"; // Personnage de Portal - facile à bruteforce avec wordlist Valve

// Traitement du login - AUCUNE PROTECTION CONTRE BRUTE FORCE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Pas de rate limiting, pas de captcha, pas de blocage = VULNÉRABLE
    if ($username === $validUsername && $password === $validPassword) {
        $loggedIn = true;
    } else {
        $message = "Identifiants incorrects.";
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>AdilPanel - Valve Internal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0a0e14;
            --bg-card: #1a1f2e;
            --accent: #ff6b35;
            --accent-dark: #c94f20;
            --text: #e4e4e4;
            --text-muted: #8892a0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image:
                radial-gradient(ellipse at 20% 80%, rgba(255, 107, 53, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(255, 107, 53, 0.05) 0%, transparent 50%);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-box {
            background: linear-gradient(145deg, var(--bg-card) 0%, #151a26 100%);
            border-radius: 16px;
            padding: 40px;
            border: 1px solid rgba(255, 107, 53, 0.2);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent) 0%, #ff8f5a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 2px;
        }

        .logo p {
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 5px;
        }

        .valve-badge {
            display: inline-block;
            background: rgba(255, 107, 53, 0.15);
            color: var(--accent);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 15px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(255, 71, 87, 0.15);
            border: 1px solid rgba(255, 71, 87, 0.3);
            color: #ff4757;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--accent);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            color: var(--text);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.15);
        }

        .form-group input::placeholder {
            color: var(--text-muted);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
        }

        .security-notice {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .security-notice p {
            color: var(--text-muted);
            font-size: 11px;
        }

        /* Dashboard (après connexion) */
        .dashboard {
            text-align: center;
        }

        .dashboard h2 {
            color: var(--text);
            font-size: 24px;
            margin-bottom: 10px;
        }

        .dashboard .welcome {
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        .access-code-box {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.2) 0%, rgba(255, 107, 53, 0.05) 100%);
            border: 2px solid var(--accent);
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
        }

        .access-code-box .label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .access-code-box .code {
            font-size: 20px;
            font-weight: 800;
            color: var(--accent);
            font-family: 'Consolas', monospace;
            letter-spacing: 3px;
        }

        .flag-box {
            background: rgba(29, 209, 161, 0.15);
            border: 1px solid rgba(29, 209, 161, 0.4);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .flag-box .label {
            font-size: 10px;
            color: #1dd1a1;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        .flag-box .flag {
            font-family: 'Consolas', monospace;
            font-size: 14px;
            color: #1dd1a1;
            word-break: break-all;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--accent);
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <?php if (!$loggedIn): ?>
                <!-- Page de Login -->
                <div class="logo">
                    <h1>AdilPanel</h1>
                    <p>Valve Internal System</p>
                    <span class="valve-badge">🔒 RESTRICTED ACCESS</span>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        ⚠️
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Identifiant</label>
                        <input type="text" name="username" placeholder="Entrez votre identifiant" required>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-login">Se connecter</button>
                </form>

                <div class="security-notice">
                    <p>⚠️ Accès réservé aux employés Valve autorisés</p>
                    <a href="../challenges.php"
                        style="display: inline-block; margin-top: 10px; color: #66c0f4; text-decoration: none; font-size: 12px; transition: color 0.3s;">
                        ← Retour aux challenges
                    </a>
                </div>
            <?php else: ?>
                <!-- Dashboard après connexion réussie -->
                <div class="dashboard">
                    <h2>🎉 Bienvenue, Adil !</h2>
                    <p class="welcome">Accès au panneau d'administration confirmé.</p>

                    <div class="access-code-box">
                        <div class="label">📍 Code d'Accès - Étage 4</div>
                        <div class="code">VALVE-4F-2024-GMAN</div>
                    </div>

                    <div class="flag-box">
                        <div class="label">🚩 Challenge Complété !</div>
                        <div class="flag">STEAM{B1GN_L0G1N_SUCC3SS}</div>
                    </div>

                    <a href="../challenges.php" class="back-link">← Retour aux challenges</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
