<?php
// Challenge 2: SQL Injection (Union/Blind) + IDOR
// Créateur: Nathan
// Difficulté: ★★★★☆
// Partie 1: Catalogue & Recherche avec SQL Injection

include 'includes/db_connect.php';
include 'includes/header.php';

$search_query = isset($_GET['q']) ? $_GET['q'] : '';
$results = [];

if (!empty($search_query)) {
    // VULNÉRABILITÉ: Concaténation directe sans préparation
    // Permet l'injection SQL Union et Blind
    $sql = "SELECT id, title, description, price, image_cover FROM games WHERE title LIKE '%" . $search_query . "%' OR description LIKE '%" . $search_query . "%'";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }
    } else {
        $error_msg = mysqli_error($conn);
    }
}
?>

<style>
    .search-container {
        max-width: 1200px;
        margin: 40px auto;
    }

    .search-header {
        background: linear-gradient(135deg, rgba(15, 20, 30, 0.95) 0%, rgba(20, 30, 50, 0.9) 100%);
        padding: 40px;
        border-radius: var(--radius-lg);
        margin-bottom: 30px;
        border: 1px solid rgba(102, 192, 244, 0.2);
        text-align: center;
    }

    .search-header h1 {
        font-size: 32px;
        background: linear-gradient(135deg, #fff 0%, var(--accent-cyan) 50%, #54a0ff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 10px;
    }

    .challenge-badge {
        display: inline-block;
        background: rgba(255, 107, 107, 0.2);
        color: #ff6b6b;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 10px;
    }

    .search-box {
        max-width: 700px;
        margin: 30px auto;
        position: relative;
    }

    .search-box input {
        width: 100%;
        padding: 18px 25px;
        padding-right: 140px;
        background: var(--bg-card);
        border: 2px solid rgba(102, 192, 244, 0.3);
        border-radius: var(--radius-lg);
        color: var(--text-primary);
        font-size: 16px;
        transition: var(--transition-normal);
    }

    .search-box input:focus {
        outline: none;
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(102, 192, 244, 0.1);
    }

    .search-box button {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        padding: 12px 30px;
        background: linear-gradient(135deg, var(--accent-blue-light), var(--accent-blue-dark));
        border: none;
        border-radius: var(--radius-md);
        color: white;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition-normal);
    }

    .search-box button:hover {
        box-shadow: 0 5px 15px rgba(102, 192, 244, 0.4);
    }

    .hint-section {
        background: rgba(0, 0, 0, 0.3);
        border-left: 3px solid var(--accent-green-light);
        padding: 20px;
        border-radius: 0 8px 8px 0;
        margin: 20px 0;
    }

    .hint-section h3 {
        color: var(--accent-green-light);
        font-size: 16px;
        margin-bottom: 12px;
    }

    .hint-section code {
        background: rgba(102, 192, 244, 0.1);
        padding: 2px 8px;
        border-radius: 4px;
        color: var(--accent-cyan);
        font-family: 'Courier New', monospace;
        font-size: 13px;
    }

    .results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    .game-card {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: var(--transition-normal);
    }

    .game-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        border-color: rgba(102, 192, 244, 0.3);
    }

    .game-card img {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }

    .game-card-body {
        padding: 20px;
    }

    .game-card h3 {
        font-size: 18px;
        margin-bottom: 10px;
        color: var(--text-primary);
    }

    .game-card p {
        color: var(--text-muted);
        font-size: 13px;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .game-price {
        font-size: 22px;
        font-weight: 800;
        color: var(--accent-green-light);
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

    .error-box {
        background: rgba(255, 107, 107, 0.1);
        border-left: 3px solid #ff6b6b;
        padding: 15px;
        border-radius: 0 8px 8px 0;
        margin: 20px 0;
        color: #ff6b6b;
        font-family: 'Courier New', monospace;
        font-size: 13px;
    }

    .link-box {
        text-align: center;
        margin-top: 30px;
    }

    .link-box a {
        display: inline-block;
        padding: 12px 30px;
        background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
        border-radius: var(--radius-md);
        color: white;
        text-decoration: none;
        font-weight: 700;
        transition: var(--transition-normal);
    }

    .link-box a:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(255, 107, 107, 0.4);
    }
</style>

<div class="search-container">
    <div class="search-header">
        <h1>💎 Database Treasure Hunt</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Challenge créé par Nathan</p>
        <div class="challenge-badge">SQL Injection + IDOR | ★★★★☆ | 400 pts</div>
    </div>

    <div class="search-box">
        <form method="GET" action="">
            <input type="text" name="q" placeholder="Rechercher un jeu..."
                value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit">🔍 Rechercher</button>
        </form>
    </div>

    <div class="hint-section">
        <h3>💡 Indices pour le challenge</h3>
        <ul style="color: var(--text-muted); font-size: 14px; line-height: 1.8;">
            <li>La recherche est vulnérable à l'<strong>injection SQL</strong></li>
            <li>Essayez des payloads comme: <code>' OR 1=1--</code> ou <code>' UNION SELECT ...</code></li>
            <li>La base contient une table <code>game_keys</code> avec des clés et des flags</li>
            <li>Nombre de colonnes dans la requête: <strong>5</strong></li>
            <li>Une fois les clés trouvées, allez sur <a href="my-games.php" style="color: var(--accent-cyan);">My
                    Games</a> pour exploiter l'IDOR</li>
        </ul>
    </div>

    <?php if (isset($error_msg)): ?>
        <div class="error-box">
            <strong>⚠️ Erreur SQL:</strong><br>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($results)): ?>
        <div class="results-grid">
            <?php foreach ($results as $game): ?>
                <div class="game-card">
                    <?php if (!empty($game['image_cover'])): ?>
                        <img src="<?php echo htmlspecialchars($game['image_cover']); ?>"
                            alt="<?php echo htmlspecialchars($game['title']); ?>">
                    <?php endif; ?>
                    <div class="game-card-body">
                        <h3>
                            <?php echo htmlspecialchars($game['title']); ?>
                        </h3>
                        <p>
                            <?php echo htmlspecialchars(substr($game['description'], 0, 100)); ?>...
                        </p>
                        <div class="game-price">
                            <?php echo number_format($game['price'], 2); ?> €
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (!empty($search_query)): ?>
        <div class="empty-state">
            <div class="icon">🔍</div>
            <h3>Aucun résultat</h3>
            <p>Essayez une injection SQL pour révéler plus de données...</p>
        </div>
    <?php endif; ?>

    <div class="link-box">
        <a href="my-games.php">🎮 Voir Mes Clés de Jeux →</a>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="challenges.php" style="color: var(--accent-blue); text-decoration: none;">← Retour aux challenges</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
