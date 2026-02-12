<?php
if (file_exists('includes/db_connect.php')) {
    include 'includes/db_connect.php';
    include 'includes/header.php';
} else {
    include 'db_connect.php';
    include 'header.php';
}

if (!isset($_GET['id']))
    die("Jeu manquant.");
$gid = intval($_GET['id']);
$game = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM games WHERE id = $gid"));

// Gestion Image Jeu
$imgSrc = $game['image_cover'];
if (!filter_var($imgSrc, FILTER_VALIDATE_URL))
    $imgSrc = "uploads/" . $imgSrc;

// POSTER AVIS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['content'])) {
    if (!isset($_SESSION['user_id']))
        header("Location: index.php");
    $uid = $_SESSION['user_id'];
    $content = mysqli_real_escape_string($conn, $_POST['content']); // Contenu pour SQL
    $rec = intval($_POST['recommendation']);
    mysqli_query($conn, "INSERT INTO reviews (game_id, user_id, content, is_recommended) VALUES ($gid, $uid, '$content', $rec)");
}

// RÉCUPÉRATION AVIS + AVATARS (JOIN)
$sql_rev = "SELECT r.content, r.is_recommended, r.posted_at, u.username, u.avatar 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.game_id = $gid 
            ORDER BY r.posted_at DESC";
$res_rev = mysqli_query($conn, $sql_rev);
?>

    <div
            style="background:url('<?php echo $imgSrc; ?>') no-repeat center top; background-size:cover; height:400px; opacity:0.2; position:absolute; width:100%; z-index:-1; mask-image: linear-gradient(to bottom, black, transparent);">
    </div>

    <div class="container" style="margin-top: 40px;">
        <div style="display:flex; gap:30px;">
            <div style="flex:1;"><img src="<?php echo $imgSrc; ?>" style="width:100%;"></div>
            <div style="flex:2;">
                <h1 style="color:white; margin-top:0;"><?php echo htmlspecialchars($game['title']); ?></h1>
                <p style="color:#acb2b8;"><?php echo nl2br(htmlspecialchars($game['description'])); ?></p>
                <div
                        style="background:#00000066; padding:15px; margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:white; font-size:20px;"><?php echo $game['price']; ?> €</span>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form action="buy_action.php" method="POST">
                            <input type="hidden" name="game_id" value="<?php echo $gid; ?>">
                            <input type="hidden" name="price" value="<?php echo $game['price']; ?>">
                            <button class="btn-green">Acheter</button>
                        </form>
                    <?php else: ?>
                        <a href="index.php" class="btn-green" style="background:#333; color:#aaa;">Connexion</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <div class="reviews-section" style="margin-top:50px; background:#00000033; padding:20px;">
            <div
                    style="background: linear-gradient(135deg, rgba(255, 107, 107, 0.2), rgba(238, 90, 111, 0.2)); border-left: 3px solid #ff6b6b; padding: 15px; border-radius: 0 8px 8px 0; margin-bottom: 20px;">
                <strong style="color: #ff6b6b; font-size: 14px;">🎯 CHALLENGE CTF - Community Takeover (par
                    Titouan)</strong><br>
                <span style="color: #acb2b8; font-size: 12px;">Stored XSS + Cookie sans HttpOnly | Difficulté: ★★★★★ | 500
                points</span><br>
                <span style="color: var(--text-muted); font-size: 11px;">💡 Les avis ne sont pas filtrés. Session cookies
                accessibles via JavaScript.</span>

                <div style="margin-top: 15px; border-top: 1px solid rgba(255, 107, 107, 0.3); padding-top: 10px;">
                    <strong style="color: #fff; font-size: 13px;">Objectif :</strong><br>
                    <span style="color: #acb2b8; font-size: 13px;">Trouve le cookie de connexion de l’admin.</span>

                    <br><br>

                    <strong style="color: #fff; font-size: 13px;">Indice :</strong>
                    <ul style="color: #acb2b8; font-size: 13px; padding-left: 20px; margin-top: 5px; margin-bottom: 0;">
                        <li>Les cookies de session de ce site ne sont pas protégés par l'attribut HttpOnly. Tu peux les lire avec document.cookie.</li>
                        <li>Vous pouvez rentrez un script XSS dans les commentaires.</li>
                    </ul>
                </div>
            </div>

            <h3 style="color:white; border-bottom:1px solid #3a4b61;">Avis</h3>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" style="background:#192533; padding:20px; margin-bottom:30px;">
                    <div style="margin-bottom:10px;">
                        <label style="color:#66c0f4; margin-right:15px;"><input type="radio" name="recommendation" value="1"
                                                                                checked> 👍 Oui</label>
                        <label style="color:#b93737;"><input type="radio" name="recommendation" value="0"> 👎 Non</label>
                    </div>
                    <textarea name="content" placeholder="Votre avis... (HTML et scripts autorisés 😈)"
                              style="width:100%; height:80px; background:#222; color:#fff; border:1px solid #000;"></textarea>
                    <button class="btn-steam-login" style="margin-top:10px;">Publier</button>
                </form>
            <?php endif; ?>

            <?php while ($row = mysqli_fetch_assoc($res_rev)):
                $isRec = $row['is_recommended'];
                $border = $isRec ? "border:1px solid #2a475e;" : "border:1px solid #5e2a2a;";
                $bgHead = $isRec ? "background:#1a2e3b;" : "background:#3d1a1a;";
                $thumb = $isRec ? "👍 Recommandé" : "👎 Non recommandé";
                ?>
                <div style="display:flex; background:#16202d; margin-bottom:15px; <?php echo $border; ?>">
                    <div style="width:184px; padding:10px; background:#101822; display:flex; flex-direction:column;">
                        <div style="width:164px; height:164px; border:1px solid #555;">
                            <img src="<?php echo $row['avatar']; ?>" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                        <div style="color:#c1dbf4; font-weight:bold; margin-top:5px;">
                            <?php echo htmlspecialchars($row['username']); ?></div>
                    </div>

                    <div style="flex:1; padding:10px;">
                        <div style="<?php echo $bgHead; ?> padding:5px; margin-bottom:10px; color:#d6d7d8; font-size:16px;">
                            <?php echo $thumb; ?>
                        </div>
                        <div style="color:#acb2b8; white-space:pre-wrap;">
                            <?php
                            // VULNÉRABILITÉ: Stored XSS - Contenu non échappé
                            // Permet l'exécution de JavaScript malveillant
                            echo $row['content'];
                            ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php if (file_exists('includes/footer.php'))
    include 'includes/footer.php';
else
    include 'footer.php'; ?>