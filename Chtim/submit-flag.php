<?php
session_start();
include 'includes/db_connect.php';
include 'includes/header.php';

$message = "";
$messageType = "";
$points = 0;
$challengeName = "";

// Liste des flags valides (hardcodé)
$validFlags = [
    'STEAM{B1GN_L0G1N_SUCC3SS}' => [
        'name' => 'The Steam Employee Leak',
        'points' => 100,
        'difficulty' => 'Facile'
    ],
    'STEAM{UPL04D_BYPASS_M4ST3R}' => [
        'name' => 'Operation: Gabe\'s Hidden Server',
        'points' => 300,
        'difficulty' => 'Difficile'
    ],
    'STEAM{H0ST_H34D3R_P0WN3D}' => [
        'name' => 'Password Recovery Heist',
        'points' => 500,
        'difficulty' => 'Insane'
    ],
    'STEAM{SQL_1NJ3CT10N_K1NG}' => [
        'name' => 'Database Treasure Hunt',
        'points' => 400,
        'difficulty' => 'Difficile'
    ],
    'STEAM{XSS_C00K13_ST34L3R}' => [
        'name' => 'Community Takeover',
        'points' => 500,
        'difficulty' => 'Insane'
    ]
];

// Traitement de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submittedFlag = trim($_POST['flag'] ?? '');

    if (empty($submittedFlag)) {
        $message = "⚠️ Veuillez entrer un flag.";
        $messageType = "error";
    }
    elseif (isset($validFlags[$submittedFlag])) {
        // Initialiser le tableau des flags complétés si nécessaire
        if (!isset($_SESSION['completed_flags'])) {
            $_SESSION['completed_flags'] = [];
        }

        // Vérifier si le flag n'a pas déjà été soumis (VIA BASE DE DONNÉES)
        $alreadyCompleted = false;

        if (isset($_SESSION['user_id'])) {
            $checkUserId = intval($_SESSION['user_id']);
            $checkSql = "SELECT 1 FROM user_solves WHERE user_id = $checkUserId AND challenge_flag = '" . mysqli_real_escape_string($conn, $submittedFlag) . "'";
            $checkRes = mysqli_query($conn, $checkSql);
            if ($checkRes && mysqli_num_rows($checkRes) > 0) {
                $alreadyCompleted = true;
            }
        }
        else {
            // Fallback session si non connecté (peu probable dans ce flow)
            if (isset($_SESSION['completed_flags'])) {
                foreach ($_SESSION['completed_flags'] as $completedFlag) {
                    if ($completedFlag['flag'] === $submittedFlag) {
                        $alreadyCompleted = true;
                        break;
                    }
                }
            }
        }

        if ($alreadyCompleted) {
            $message = "⚠️ Vous avez déjà validé ce challenge !";
            $messageType = "error";

            // Sync session just in case
            $challengeName = $validFlags[$submittedFlag]['name'];
            $points = $validFlags[$submittedFlag]['points'];
            $difficulty = $validFlags[$submittedFlag]['difficulty'];

            $inSession = false;
            if (!isset($_SESSION['completed_flags']))
                $_SESSION['completed_flags'] = [];
            foreach ($_SESSION['completed_flags'] as $cf) {
                if ($cf['flag'] === $submittedFlag)
                    $inSession = true;
            }
            if (!$inSession) {
                $_SESSION['completed_flags'][] = [
                    'flag' => $submittedFlag,
                    'name' => $challengeName,
                    'points' => $points,
                    'difficulty' => $difficulty,
                    'time' => date('Y-m-d H:i:s')
                ];
            }
        }
        else {
            // Flag correct et nouveau !
            $challengeName = $validFlags[$submittedFlag]['name'];
            $points = $validFlags[$submittedFlag]['points'];
            $difficulty = $validFlags[$submittedFlag]['difficulty'];

            $message = "🎉 Excellent travail ! Flag validé !";
            $messageType = "success";

            // Stocker dans la session pour affichage immédiat
            $_SESSION['last_flag_success'] = [
                'name' => $challengeName,
                'points' => $points,
                'difficulty' => $difficulty,
                'time' => date('Y-m-d H:i:s')
            ];

            // Ajouter au tableau des flags complétés (pour le profil)
            $_SESSION['completed_flags'][] = [
                'flag' => $submittedFlag,
                'name' => $challengeName,
                'points' => $points,
                'difficulty' => $difficulty,
                'time' => date('Y-m-d H:i:s')
            ];

            // Mettre à jour les points en base de données si l'utilisateur est connecté
            if (isset($_SESSION['user_id'])) {
                $userId = intval($_SESSION['user_id']);

                // 1. D'abord, on essaie d'insérer le solve dans la table de suivi
                // Grâce à la contrainte UNIQUE, cela échouera si déjà validé (double protection)
                $insertSolve = "INSERT INTO user_solves (user_id, challenge_flag) VALUES ($userId, '$submittedFlag')";

                if (mysqli_query($conn, $insertSolve)) {
                    // 2. Si l'insertion fonctionne, on crédite les points
                    $updateQuery = "UPDATE users SET ctf_points = ctf_points + $points WHERE id = $userId";
                    $result = mysqli_query($conn, $updateQuery);

                    if (!$result) {
                        error_log("Erreur CTF points update: " . mysqli_error($conn));
                    }
                }
                else {
                    // Si erreur d'insertion (doublon probable malgré le check session), on ne donne pas les points
                    // Mais on laisse le message "Succès" s'afficher si c'était juste un problème de synchro session, 
                    // ou on change le message ?
                    // Pour éviter la confusion, si c'est un doublon DB, on le considère comme "Déjà validé"
                    if (mysqli_errno($conn) == 1062) { // Erreur Duplicate entry
                        $message = "⚠️ Vous avez déjà validé ce challenge !";
                        $messageType = "error";
                        // On retire de la session success pour ne pas afficher le popup
                        unset($_SESSION['last_flag_success']);
                        // On s'assure qu'il est dans la session completed
                        $inSession = false;
                        foreach ($_SESSION['completed_flags'] as $cf) {
                            if ($cf['flag'] === $submittedFlag)
                                $inSession = true;
                        }
                        if (!$inSession) {
                            $_SESSION['completed_flags'][] = [
                                'flag' => $submittedFlag,
                                'name' => $challengeName,
                                'points' => $points,
                                'difficulty' => $difficulty,
                                'time' => date('Y-m-d H:i:s')
                            ];
                        }
                    }
                    else {
                        error_log("Erreur Insert Solve: " . mysqli_error($conn));
                    }
                }
            }
        }
    }
    else {
        $message = "❌ Flag incorrect. Réessayez !";
        $messageType = "error";
    }
}
?>

<style>
    .flag-container {
        max-width: 600px;
        margin: 40px auto;
        padding: 20px;
    }

    .flag-header {
        background: linear-gradient(135deg, rgba(15, 20, 30, 0.95) 0%, rgba(20, 30, 50, 0.9) 100%);
        padding: 40px;
        border-radius: var(--radius-lg);
        margin-bottom: 30px;
        border: 1px solid rgba(102, 192, 244, 0.2);
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .flag-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3, #54a0ff);
    }

    .flag-header h1 {
        font-size: 32px;
        background: linear-gradient(135deg, #fff 0%, var(--accent-cyan) 50%, #ff6b6b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 10px;
    }

    .flag-header p {
        color: var(--text-muted);
        font-size: 14px;
    }

    .flag-box {
        background: linear-gradient(145deg, var(--bg-card) 0%, rgba(22, 32, 45, 0.95) 100%);
        border-radius: var(--radius-lg);
        padding: 40px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .alert {
        padding: 15px 20px;
        border-radius: var(--radius-md);
        margin-bottom: 25px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-error {
        background: rgba(255, 71, 87, 0.15);
        border: 1px solid rgba(255, 71, 87, 0.3);
        color: #ff4757;
    }

    .alert-success {
        background: rgba(29, 209, 161, 0.15);
        border: 1px solid rgba(29, 209, 161, 0.4);
        color: #1dd1a1;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        color: var(--accent-blue);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }

    .form-group input {
        width: 100%;
        padding: 16px 18px;
        background: rgba(0, 0, 0, 0.3);
        border: 2px solid rgba(255, 255, 255, 0.05);
        border-radius: var(--radius-md);
        color: var(--text-primary);
        font-size: 15px;
        font-family: 'Consolas', monospace;
        transition: all 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(102, 192, 244, 0.15);
    }

    .form-group input::placeholder {
        color: var(--text-muted);
        font-family: 'Inter', sans-serif;
    }

    .btn-submit {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, var(--accent-blue-light) 0%, var(--accent-blue-dark) 100%);
        border: none;
        border-radius: var(--radius-md);
        color: white;
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102, 192, 244, 0.4);
    }

    .success-card {
        background: linear-gradient(135deg, rgba(29, 209, 161, 0.2) 0%, rgba(29, 209, 161, 0.05) 100%);
        border: 2px solid #1dd1a1;
        border-radius: var(--radius-lg);
        padding: 30px;
        margin-top: 25px;
        animation: celebrate 0.6s ease;
    }

    @keyframes celebrate {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    .success-card h3 {
        color: #1dd1a1;
        font-size: 20px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .challenge-info {
        background: rgba(0, 0, 0, 0.3);
        padding: 20px;
        border-radius: var(--radius-md);
        margin-bottom: 15px;
    }

    .challenge-info .label {
        font-size: 11px;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }

    .challenge-info .value {
        font-size: 18px;
        color: var(--text-primary);
        font-weight: 700;
    }

    .points-display {
        text-align: center;
        padding: 25px;
        background: rgba(0, 0, 0, 0.4);
        border-radius: var(--radius-md);
        border: 2px dashed #1dd1a1;
    }

    .points-display .points {
        font-size: 48px;
        font-weight: 800;
        background: linear-gradient(135deg, #1dd1a1 0%, #48dbfb 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .points-display .label {
        font-size: 12px;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-top: 5px;
    }

    .back-link {
        display: inline-block;
        margin-top: 25px;
        color: var(--text-muted);
        text-decoration: none;
        font-size: 13px;
        transition: color 0.3s;
        text-align: center;
        width: 100%;
    }

    .back-link:hover {
        color: var(--accent-blue);
    }

    .hint-box {
        background: rgba(72, 219, 251, 0.1);
        border-left: 3px solid var(--accent-cyan);
        padding: 15px 20px;
        border-radius: 0 var(--radius-md) var(--radius-md) 0;
        margin-top: 20px;
    }

    .hint-box .hint-title {
        font-size: 11px;
        color: var(--accent-cyan);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .hint-box p {
        color: var(--text-muted);
        font-size: 13px;
        margin: 0;
    }
</style>

<div class="flag-container">
    <div class="flag-header">
        <h1>🚩 Validation de Flag</h1>
        <p>Soumettez vos flags pour valider vos découvertes</p>
    </div>

    <div class="flag-box">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php
endif; ?>

        <?php if ($messageType === "success" && isset($_SESSION['last_flag_success'])): ?>
            <div class="success-card">
                <h3>✨ Challenge Complété !</h3>

                <div class="challenge-info">
                    <div class="label">Nom du Challenge</div>
                    <div class="value">
                        <?php echo htmlspecialchars($_SESSION['last_flag_success']['name']); ?>
                    </div>
                </div>

                <div class="challenge-info">
                    <div class="label">Difficulté</div>
                    <div class="value">
                        <?php echo htmlspecialchars($_SESSION['last_flag_success']['difficulty']); ?>
                    </div>
                </div>

                <div class="points-display">
                    <div class="points">+
                        <?php echo $_SESSION['last_flag_success']['points']; ?>
                    </div>
                    <div class="label">Points Gagnés</div>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <small style="color: var(--text-muted);">
                        Validé le
                        <?php echo $_SESSION['last_flag_success']['time']; ?>
                    </small>
                </div>
            </div>
        <?php
endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>🏁 Entrez votre flag</label>
                <input type="text" name="flag" placeholder="STEAM{...}" required autocomplete="off" autofocus>
            </div>

            <button type="submit" class="btn-submit">Valider le Flag</button>
        </form>

        <div class="hint-box">
            <div class="hint-title">💡 Format attendu</div>
            <p>Les flags suivent le format : <code style="color: var(--accent-cyan);">STEAM{...}</code></p>
        </div>

        <a href="challenges.php" class="back-link">← Retour aux challenges</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
