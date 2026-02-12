<?php
if (file_exists('includes/db_connect.php')) {
    include 'includes/db_connect.php';
    include 'includes/header.php';
} else {
    include 'db_connect.php';
    include 'header.php';
}

if (!isset($_GET['id'])) {
    if (isset($_SESSION['user_id']))
        header("Location: profile.php?id=" . $_SESSION['user_id']);
    else
        header("Location: index.php");
    exit();
}

$pid = intval($_GET['id']);
$sql = "SELECT * FROM users WHERE id = $pid";
$res = mysqli_query($conn, $sql);

if (mysqli_num_rows($res) == 0)
    die("<div class='container'><p>Utilisateur inconnu.</p></div>");
$u = mysqli_fetch_assoc($res);
?>

<div class="container" style="margin-top: 40px;">
    <div class="card" style="background:#1b2838; padding:20px; display:flex; gap:20px;">
        <div style="width: 166px; height: 166px; border: 2px solid #57cbde; padding: 2px;">
            <img src="<?php echo $u['avatar']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <div>
            <h1 style="color:white; margin-top:0;"><?php echo htmlspecialchars($u['username']); ?></h1>
            <p style="color:#8f98a0;">Niveau 13</p>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $pid): ?>
                <div style="background:rgba(0,0,0,0.3); padding:10px; margin-top:10px;">
                    Email: <?php echo htmlspecialchars($u['email']); ?><br>
                    Solde: <span style="color:#a4d007;"><?php echo $u['wallet_balance']; ?> €</span>
                </div>

                <!-- Compteur de Points CTF -->
                <div
                    style="background: linear-gradient(135deg, rgba(29, 209, 161, 0.2) 0%, rgba(72, 219, 251, 0.1) 100%); border: 2px solid rgba(29, 209, 161, 0.4); border-radius: 8px; padding: 15px; margin-top: 15px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div
                                style="font-size: 11px; color: #8f98a0; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 5px;">
                                🚩 Points CTF
                            </div>
                            <div
                                style="font-size: 28px; font-weight: 800; background: linear-gradient(135deg, #1dd1a1 0%, #48dbfb 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                <?php
                                // Lire les points depuis la base de données
                                echo intval($u['ctf_points']);
                                ?> pts
                            </div>
                        </div>
                        <div style="font-size: 40px;">
                            🏆
                        </div>
                    </div>
                    <?php if (intval($u['ctf_points']) > 0 && isset($_SESSION['completed_flags']) && count($_SESSION['completed_flags']) > 0): ?>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                            <div style="font-size: 11px; color: #8f98a0; margin-bottom: 5px;">Challenges complétés :</div>
                            <?php foreach ($_SESSION['completed_flags'] as $flag): ?>
                                <div style="font-size: 12px; color: #1dd1a1; margin: 3px 0;">
                                    ✓ <?php echo htmlspecialchars($flag['name']); ?>
                                    <span style="color: #8f98a0;">(+<?php echo $flag['points']; ?> pts)</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 10px; font-size: 12px; color: #8f98a0;">
                            Aucun challenge complété. <a href="challenges.php"
                                style="color: #48dbfb; text-decoration: underline;">Commencer maintenant</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top:15px;">
                    <a href="change-password.php"
                        style="display:inline-block; padding:10px 20px; background:linear-gradient(135deg, var(--accent-blue-light), var(--accent-blue-dark)); color:white; text-decoration:none; border-radius:5px; font-weight:600; font-size:13px; transition: var(--transition-normal);">
                        🔒 Changer le mot de passe
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <h3 style="color:white; border-bottom:1px solid #3a4b61; margin-top:40px;">Jeux possédés</h3>
    <div class="games-list">
        <?php
        $sql_lib = "SELECT g.title, g.image_cover FROM library l JOIN games g ON l.game_id = g.id WHERE l.user_id = $pid";
        $res_lib = mysqli_query($conn, $sql_lib);
        while ($g = mysqli_fetch_assoc($res_lib)) {
            $img = filter_var($g['image_cover'], FILTER_VALIDATE_URL) ? $g['image_cover'] : "uploads/" . $g['image_cover'];
            echo "<div class='game-row' style='cursor:default;'>
                    <img src='$img' style='width:120px; height:55px; object-fit:cover; margin-right:15px;'>
                    <div class='game-title'>" . htmlspecialchars($g['title']) . "</div>
                  </div>";
        }
        ?>
    </div>
</div>
<?php if (file_exists('includes/footer.php'))
    include 'includes/footer.php';
else
    include 'footer.php'; ?>
