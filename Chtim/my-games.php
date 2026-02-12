<?php
// Challenge 2: IDOR (Insecure Direct Object Reference)
// Créateur: Nathan
// Difficulté: ★★★★☆
// Partie 2: Accès aux clés de jeux d'autres utilisateurs

session_start();
include 'includes/db_connect.php';
include 'includes/header.php';

// VULNÉRABILITÉ IDOR: Pas de vérification que l'user_id correspond à l'utilisateur connecté
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1);

// Récupérer les informations de l'utilisateur ciblé
$user_query = "SELECT username, role FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$target_user = mysqli_fetch_assoc($user_result);

// Récupérer les clés de jeux de l'utilisateur ciblé
$keys_query = "SELECT gk.id, gk.key_code, gk.flag, g.title, g.image_cover 
               FROM game_keys gk 
               JOIN games g ON gk.game_id = g.id 
               WHERE gk.user_id = ?
               ORDER BY gk.created_at DESC";

$keys_stmt = mysqli_prepare($conn, $keys_query);
mysqli_stmt_bind_param($keys_stmt, "i", $user_id);
mysqli_stmt_execute($keys_stmt);
$keys_result = mysqli_stmt_get_result($keys_stmt);

$game_keys = [];
while ($row = mysqli_fetch_assoc($keys_result)) {
    $game_keys[] = $row;
}
?>

<style>
    .mygames-container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-header {
        background: linear-gradient(135deg, rgba(15, 20, 30, 0.95) 0%, rgba(20, 30, 50, 0.9) 100%);
        padding: 40px;
        border-radius: var(--radius-lg);
        margin-bottom: 30px;
        border: 1px solid rgba(102, 192, 244, 0.2);
    }

    .page-header h1 {
        font-size: 32px;
        background: linear-gradient(135deg, #fff 0%, var(--accent-green-light) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 10px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 20px;
        padding: 15px;
        background: rgba(102, 192, 244, 0.1);
        border-radius: var(--radius-md);
    }

    .user-info .username {
        font-size: 18px;
        font-weight: 700;
        color: var(--accent-cyan);
    }

    .user-info .role {
        padding: 4px 12px;
        background: rgba(255, 107, 107, 0.2);
        color: #ff6b6b;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .idor-hint {
        background: rgba(255, 107, 107, 0.15);
        border-left: 3px solid #ff6b6b;
        padding: 20px;
        border-radius: 0 8px 8px 0;
        margin: 20px 0;
    }

    .idor-hint h3 {
        color: #ff6b6b;
        font-size: 16px;
        margin-bottom: 10px;
    }

    .idor-hint code {
        background: rgba(0, 0, 0, 0.3);
        padding: 2px 8px;
        border-radius: 4px;
        color: var(--accent-cyan);
        font-family: 'Courier New', monospace;
    }

    .keys-grid {
        display: grid;
        gap: 20px;
    }

    .key-card {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        padding: 25px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: var(--transition-normal);
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .key-card:hover {
        border-color: rgba(102, 192, 244, 0.3);
        transform: translateX(5px);
    }

    .key-card img {
        width: 120px;
        height: 80px;
        object-fit: cover;
        border-radius: var(--radius-md);
    }

    .key-info {
        flex: 1;
    }

    .key-info h3 {
        font-size: 18px;
        color: var(--text-primary);
        margin-bottom: 10px;
    }

    .key-code {
        font-family: 'Courier New', monospace;
        font-size: 16px;
        color: var(--accent-green-light);
        background: rgba(0, 0, 0, 0.3);
        padding: 10px 15px;
        border-radius: var(--radius-md);
        display: inline-block;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .flag-reveal {
        margin-top: 15px;
        padding: 15px;
        background: linear-gradient(135deg, rgba(102, 192, 244, 0.2), rgba(72, 219, 251, 0.2));
        border-radius: var(--radius-md);
        border: 1px solid var(--accent-cyan);
    }

    .flag-reveal strong {
        color: var(--accent-cyan);
        font-size: 14px;
    }

    .flag-reveal code {
        display: block;
        margin-top: 8px;
        font-size: 18px;
        color: #fff;
        font-weight: 800;
        letter-spacing: 1px;
    }

    .empty-state {
        text-align: center;
        padding: 80px 40px;
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        border: 1px dashed rgba(102, 192, 244, 0.3);
    }

    .empty-state .icon {
        font-size: 64px;
        margin-bottom: 20px;
    }

    .navigation {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        justify-content: center;
    }

    .navigation a {
        padding: 12px 25px;
        background: var(--bg-card);
        border: 1px solid rgba(102, 192, 244, 0.3);
        border-radius: var(--radius-md);
        color: var(--accent-blue);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition-normal);
    }

    .navigation a:hover {
        background: var(--accent-blue);
        color: white;
        transform: translateY(-2px);
    }

    .user-selector {
        text-align: center;
        margin: 20px 0;
        padding: 15px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: var(--radius-md);
    }

    .user-selector a {
        display: inline-block;
        margin: 5px;
        padding: 8px 15px;
        background: var(--bg-hover);
        border-radius: var(--radius-md);
        color: var(--accent-cyan);
        text-decoration: none;
        font-size: 13px;
        transition: var(--transition-normal);
    }

    .user-selector a:hover {
        background: var(--accent-blue);
        color: white;
    }
</style>

<div class="mygames-container">
    <div class="page-header">
        <h1>🎮 My Game Keys</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Vos clés d'activation de jeux</p>

        <?php if ($target_user): ?>
            <div class="user-info">
                <span>👤</span>
                <span class="username">
                    <?php echo htmlspecialchars($target_user['username']); ?>
                </span>
                <span class="role">
                    <?php echo htmlspecialchars($target_user['role']); ?>
                </span>
                <span style="color: var(--text-muted); font-size: 13px;">User ID:
                    <?php echo $user_id; ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <div class="idor-hint">
        <h3>🎯 Challenge IDOR (Insecure Direct Object Reference)</h3>
        <p style="color: var(--text-muted); font-size: 14px; line-height: 1.7;">
            Cette page affiche les clés de jeux d'un utilisateur basé sur le paramètre <code>?user_id=X</code>.
            Aucune vérification n'est effectuée pour s'assurer que vous accédez à vos propres données.<br><br>
            <strong style="color: #ff6b6b;">Essayez de changer le user_id dans l'URL pour accéder aux clés d'autres
                utilisateurs !</strong><br>
            Indice: user_id entre 1 et 5
        </p>
    </div>

    <div class="user-selector">
        <strong style="color: var(--text-secondary);">Quick Access:</strong>
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <a href="?user_id=<?php echo $i; ?>">User
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>

    <?php if (!empty($game_keys)): ?>
        <div class="keys-grid">
            <?php foreach ($game_keys as $key): ?>
                <div class="key-card">
                    <?php if (!empty($key['image_cover'])): ?>
                        <img src="<?php echo htmlspecialchars($key['image_cover']); ?>" alt="Game">
                    <?php endif; ?>
                    <div class="key-info">
                        <h3>
                            <?php echo htmlspecialchars($key['title']); ?>
                        </h3>
                        <div class="key-code">🔑
                            <?php echo htmlspecialchars($key['key_code']); ?>
                        </div>

                        <?php if (!empty($key['flag'])): ?>
                            <div class="flag-reveal">
                                <strong>🚩 FLAG TROUVÉ:</strong>
                                <code><?php echo htmlspecialchars($key['flag']); ?></code>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="icon">🎮</div>
            <h3>Aucune clé de jeu</h3>
            <p>Cet utilisateur n'a pas encore de clés de jeux.</p>
        </div>
    <?php endif; ?>

    <div class="navigation">
        <a href="search.php">← Retour à la recherche</a>
        <a href="challenges.php">🏠 Challenges</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
