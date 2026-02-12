<?php
session_start();
include 'includes/db_connect.php';

// Vérification admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Actions CRUD
$message = "";
$messageType = "";

// Supprimer un utilisateur
if (isset($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    if ($uid !== $_SESSION['user_id']) { // Ne pas se supprimer soi-même
        mysqli_query($conn, "DELETE FROM users WHERE id = $uid");
        $message = "Utilisateur supprimé avec succès.";
        $messageType = "success";
    }
}

// Supprimer un jeu
if (isset($_GET['delete_game'])) {
    $gid = intval($_GET['delete_game']);
    mysqli_query($conn, "DELETE FROM games WHERE id = $gid");
    $message = "Jeu supprimé avec succès.";
    $messageType = "success";
}

// Supprimer un avis
if (isset($_GET['delete_review'])) {
    $rid = intval($_GET['delete_review']);
    mysqli_query($conn, "DELETE FROM reviews WHERE id = $rid");
    $message = "Avis supprimé avec succès.";
    $messageType = "success";
}

// Changer le rôle d'un utilisateur
if (isset($_POST['change_role'])) {
    $uid = intval($_POST['user_id']);
    $newRole = mysqli_real_escape_string($conn, $_POST['new_role']);
    mysqli_query($conn, "UPDATE users SET role = '$newRole' WHERE id = $uid");
    $message = "Rôle mis à jour avec succès.";
    $messageType = "success";
}

// Ajouter du solde à un utilisateur
if (isset($_POST['add_balance'])) {
    $uid = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $amount WHERE id = $uid");
    $message = "Solde ajouté avec succès.";
    $messageType = "success";
}

// Réinitialiser les points CTF d'un utilisateur
if (isset($_POST['reset_user_ctf'])) {
    $uid = intval($_POST['user_id']);
    mysqli_query($conn, "UPDATE users SET ctf_points = 0 WHERE id = $uid");
    mysqli_query($conn, "DELETE FROM user_solves WHERE user_id = $uid");
    $message = "Points CTF réinitialisés avec succès.";
    $messageType = "success";
}

// Ajouter un jeu
if (isset($_POST['add_game'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $image = mysqli_real_escape_string($conn, $_POST['image_cover']);
    mysqli_query($conn, "INSERT INTO games (title, description, price, image_cover) VALUES ('$title', '$desc', $price, '$image')");
    $message = "Jeu ajouté avec succès.";
    $messageType = "success";
}

// Réinitialiser les points CTF
if (isset($_POST['reset_ctf_points'])) {
    unset($_SESSION['completed_flags']);
    $_SESSION['completed_flags'] = [];
    $message = "Points CTF réinitialisés avec succès.";
    $messageType = "success";
}

// Stats
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
$totalGames = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM games"))['c'];
$totalReviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reviews"))['c'];
$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(g.price) as total FROM library l JOIN games g ON l.game_id = g.id"))['total'] ?? 0;

// Données
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
$games = mysqli_query($conn, "SELECT * FROM games ORDER BY id DESC");
$reviews = mysqli_query($conn, "SELECT r.*, u.username, g.title as game_title FROM reviews r JOIN users u ON r.user_id = u.id JOIN games g ON r.game_id = g.id ORDER BY r.posted_at DESC LIMIT 20");

// Tab actif
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Administration - stim</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #0d1117 0%, #161b22 100%);
            padding: 25px 0;
            border-right: 1px solid rgba(102, 192, 244, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-logo {
            text-align: center;
            padding: 0 25px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 20px;
        }

        .admin-logo h1 {
            font-size: 24px;
            background: linear-gradient(135deg, #fff, var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .admin-logo span {
            font-size: 11px;
            color: var(--accent-blue);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .admin-nav {
            padding: 0 15px;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: var(--text-muted);
            border-radius: var(--radius-md);
            margin-bottom: 5px;
            transition: var(--transition-normal);
            font-weight: 500;
        }

        .admin-nav a:hover {
            background: rgba(102, 192, 244, 0.1);
            color: var(--text-primary);
        }

        .admin-nav a.active {
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--accent-blue-dark) 100%);
            color: white;
        }

        .admin-nav a .icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .nav-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.05);
            margin: 20px 15px;
        }

        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
            background: var(--bg-dark);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .admin-header h2 {
            font-size: 28px;
            color: var(--text-primary);
            margin: 0;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--bg-card);
            padding: 10px 20px;
            border-radius: var(--radius-lg);
        }

        .admin-user img {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            border: 2px solid var(--accent-cyan);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(145deg, var(--bg-card) 0%, rgba(22, 32, 45, 0.9) 100%);
            padding: 25px;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-card.blue::before {
            background: linear-gradient(90deg, #47bfff, #1a44c2);
        }

        .stat-card.green::before {
            background: linear-gradient(90deg, #1dd1a1, #10ac84);
        }

        .stat-card.purple::before {
            background: linear-gradient(90deg, #a55eea, #8854d0);
        }

        .stat-card.orange::before {
            background: linear-gradient(90deg, #feca57, #ff9f43);
        }

        .stat-card .icon {
            font-size: 40px;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.2;
        }

        .stat-card .label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
        }

        /* Tables */
        .admin-table {
            width: 100%;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .admin-table th {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px 20px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .admin-table td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            color: var(--text-secondary);
        }

        .admin-table tr:hover td {
            background: rgba(102, 192, 244, 0.05);
        }

        .admin-table .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-table .user-cell img {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--accent-cyan);
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .role-admin {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .role-moderator {
            background: rgba(254, 202, 87, 0.2);
            color: #feca57;
        }

        .role-user {
            background: rgba(102, 192, 244, 0.2);
            color: #66c0f4;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .btn-edit {
            background: rgba(102, 192, 244, 0.2);
            color: var(--accent-blue);
        }

        .btn-delete {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .btn-sm:hover {
            transform: scale(1.05);
        }

        /* Section Title */
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title h3 {
            font-size: 18px;
            color: var(--text-primary);
            margin: 0;
        }

        /* Forms */
        .admin-form {
            background: var(--bg-card);
            padding: 25px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            color: var(--accent-blue);
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: var(--bg-input);
            border: 2px solid transparent;
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 14px;
            transition: var(--transition-normal);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--accent-blue);
            outline: none;
        }

        /* Alert */
        .admin-alert {
            padding: 15px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-alert.success {
            background: rgba(29, 209, 161, 0.15);
            border: 1px solid rgba(29, 209, 161, 0.3);
            color: #1dd1a1;
        }

        .admin-alert.error {
            background: rgba(255, 107, 107, 0.15);
            border: 1px solid rgba(255, 107, 107, 0.3);
            color: #ff6b6b;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            padding: 30px;
            border-radius: var(--radius-lg);
            width: 400px;
            max-width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 900px) {
            .admin-sidebar {
                display: none;
            }

            .admin-main {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <h1>stim</h1>
                <span>Administration</span>
            </div>

            <nav class="admin-nav">
                <a href="?tab=dashboard" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
                    <span class="icon">📊</span> Dashboard
                </a>
                <a href="?tab=users" class="<?php echo $activeTab === 'users' ? 'active' : ''; ?>">
                    <span class="icon">👥</span> Utilisateurs
                </a>
                <a href="?tab=games" class="<?php echo $activeTab === 'games' ? 'active' : ''; ?>">
                    <span class="icon">🎮</span> Jeux
                </a>
                <a href="?tab=reviews" class="<?php echo $activeTab === 'reviews' ? 'active' : ''; ?>">
                    <span class="icon">💬</span> Avis
                </a>

                <div class="nav-divider"></div>

                <a href="store.php">
                    <span class="icon">🏪</span> Retour au site
                </a>
                <a href="index.php?logout=true">
                    <span class="icon">🚪</span> Déconnexion
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h2>
                    <?php
$titles = ['dashboard' => 'Tableau de bord', 'users' => 'Gestion des utilisateurs', 'games' => 'Gestion des jeux', 'reviews' => 'Modération des avis'];
echo $titles[$activeTab] ?? 'Administration';
?>
                </h2>
                <div class="admin-user">
                    <img src="<?php echo $_SESSION['avatar']; ?>" alt="avatar">
                    <div>
                        <div style="font-weight: 600; color: var(--text-primary);">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </div>
                        <div style="font-size: 11px; color: var(--accent-blue);">ADMINISTRATEUR</div>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="admin-alert <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php
endif; ?>

            <?php if ($activeTab === 'dashboard'): ?>
                <div class="admin-alert success" style="border: 1px solid #ffd700; background: rgba(255, 215, 0, 0.1); color: #ffd700;">
                    <h3 style="margin-top: 0;">🍪 Cookie Stealer Challenge</h3>
                    <p>Bravo ! Si vous voyez ceci, c'est que vous avez réussi à voler la session de l'admin.</p>
                    <p>Voici votre flag : <strong>STEAM{XSS_C00K13_ST34L3R}</strong></p>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="icon">👥</div>
                        <div class="label">Utilisateurs</div>
                        <div class="value">
                            <?php echo $totalUsers; ?>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="icon">🎮</div>
                        <div class="label">Jeux</div>
                        <div class="value">
                            <?php echo $totalGames; ?>
                        </div>
                    </div>
                    <div class="stat-card purple">
                        <div class="icon">💬</div>
                        <div class="label">Avis</div>
                        <div class="value">
                            <?php echo $totalReviews; ?>
                        </div>
                    </div>
                    <div class="stat-card orange">
                        <div class="icon">💰</div>
                        <div class="label">Revenus</div>
                        <div class="value">
                            <?php echo number_format($totalRevenue, 2); ?> €
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="section-title">
                    <h3>Derniers avis</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Jeu</th>
                            <th>Avis</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
    mysqli_data_seek($reviews, 0);
    $count = 0;
    while ($r = mysqli_fetch_assoc($reviews)):
        if ($count++ >= 5)
            break;
?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($r['username']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['game_title']); ?>
                                </td>
                                <td>
                                    <?php echo substr(htmlspecialchars($r['content']), 0, 50) . '...'; ?>
                                </td>
                                <td>
                                    <?php echo $r['posted_at']; ?>
                                </td>
                            </tr>
                        <?php
    endwhile; ?>
                    </tbody>
                </table>

                <!-- CTF Challenges Management -->
                <div class="section-title" style="margin-top: 40px;">
                    <h3>🚩 Gestion des Challenges CTF</h3>
                </div>

                <div class="admin-form">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h4 style="margin: 0; color: var(--text-primary);">Points CTF Actuels</h4>
                            <p style="margin: 5px 0 0; color: var(--text-muted); font-size: 13px;">
                                Gérer les points des challenges de sécurité
                            </p>
                        </div>
                        <div style="text-align: center;">
                            <div
                                style="font-size: 36px; font-weight: 800; background: linear-gradient(135deg, #1dd1a1 0%, #48dbfb 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                <?php
    // Calculer les points totaux depuis la base de données
    $totalCTFPoints = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(ctf_points) as total FROM users"))['total'] ?? 0;
    $ctfUsersCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE ctf_points > 0"))['count'] ?? 0;
    echo intval($totalCTFPoints);
?> pts
                            </div>
                            <div
                                style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">
                                <?php echo $ctfUsersCount; ?> utilisateurs avec des points
                            </div>
                        </div>
                    </div>

                    <?php
    $ctfUsers = mysqli_query($conn, "SELECT id, username, ctf_points FROM users WHERE ctf_points > 0 ORDER BY ctf_points DESC LIMIT 10");
    if (mysqli_num_rows($ctfUsers) > 0):
?>
                        <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px; font-weight: 600;">
                                🏆 TOP UTILISATEURS CTF</div>
                            <?php while ($ctfUser = mysqli_fetch_assoc($ctfUsers)): ?>
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                    <div style="color: #1dd1a1; font-size: 13px;">
                                        ✓ <?php echo htmlspecialchars($ctfUser['username']); ?>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 12px;">
                                        <?php echo $ctfUser['ctf_points']; ?> pts
                                    </div>
                                </div>
                            <?php
        endwhile; ?>
                        </div>

                        <div
                            style="text-align: center; margin-top: 15px; padding: 15px; background: rgba(29, 209, 161, 0.1); border-radius: 8px; border: 1px solid rgba(29, 209, 161, 0.2);">
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">💡 Astuce</div>
                            <p style="margin: 0; font-size: 13px; color: var(--text-secondary);">
                                Utilisez le bouton <strong style="color: #1dd1a1;">🚩</strong> dans la gestion des
                                utilisateurs pour réinitialiser les points CTF individuellement.
                            </p>
                        </div>
                    <?php
    else: ?>
                        <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                            <div style="font-size: 48px; opacity: 0.3; margin-bottom: 10px;">🏆</div>
                            <p>Aucun challenge complété pour le moment</p>
                        </div>
                    <?php
    endif; ?>
                </div>

            <?php
elseif ($activeTab === 'users'): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Solde</th>
                            <th>🚩 Points CTF</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td>#
                                    <?php echo $u['id']; ?>
                                </td>
                                <td>
                                    <div class="user-cell">
                                        <img src="<?php echo $u['avatar']; ?>" alt="">
                                        <?php echo htmlspecialchars($u['username']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($u['email']); ?>
                                </td>
                                <td><span class="role-badge role-<?php echo $u['role']; ?>">
                                        <?php echo $u['role']; ?>
                                    </span></td>
                                <td style="color: var(--accent-green-light);">
                                    <?php echo $u['wallet_balance']; ?> €
                                </td>
                                <td>
                                    <span
                                        style="font-weight: 700; background: linear-gradient(135deg, #1dd1a1 0%, #48dbfb 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                        <?php echo intval($u['ctf_points']); ?> pts
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-sm btn-edit"
                                            onclick="openRoleModal(<?php echo $u['id']; ?>, '<?php echo $u['role']; ?>')">Rôle</button>
                                        <button class="btn-sm btn-edit"
                                            onclick="openBalanceModal(<?php echo $u['id']; ?>)">💰</button>
                                        <button class="btn-sm btn-edit"
                                            style="background: rgba(29, 209, 161, 0.2); color: #1dd1a1;"
                                            onclick="openCTFModal(<?php echo $u['id']; ?>)">🚩</button>
                                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                            <a href="?tab=users&delete_user=<?php echo $u['id']; ?>" class="btn-sm btn-delete"
                                                onclick="return confirm('Supprimer cet utilisateur ?')">🗑️</a>
                                        <?php
        endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php
    endwhile; ?>
                    </tbody>
                </table>

            <?php
elseif ($activeTab === 'games'): ?>
                <!-- Formulaire ajout -->
                <div class="admin-form">
                    <h4 style="margin-top: 0; color: var(--text-primary);">➕ Ajouter un jeu</h4>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Titre</label>
                                <input type="text" name="title" required>
                            </div>
                            <div class="form-group">
                                <label>Prix (€)</label>
                                <input type="number" step="0.01" name="price" required>
                            </div>
                            <div class="form-group">
                                <label>Image (URL)</label>
                                <input type="text" name="image_cover" placeholder="https://...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" name="add_game" class="btn-green">Ajouter le jeu</button>
                    </form>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Titre</th>
                            <th>Prix</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($g = mysqli_fetch_assoc($games)):
        $img = filter_var($g['image_cover'], FILTER_VALIDATE_URL) ? $g['image_cover'] : "uploads/" . $g['image_cover'];
?>
                            <tr>
                                <td>#
                                    <?php echo $g['id']; ?>
                                </td>
                                <td><img src="<?php echo $img; ?>"
                                        style="width: 80px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                                <td>
                                    <?php echo htmlspecialchars($g['title']); ?>
                                </td>
                                <td style="color: var(--accent-green-light);">
                                    <?php echo $g['price']; ?> €
                                </td>
                                <td>
                                    <a href="?tab=games&delete_game=<?php echo $g['id']; ?>" class="btn-sm btn-delete"
                                        onclick="return confirm('Supprimer ce jeu ?')">🗑️ Supprimer</a>
                                </td>
                            </tr>
                        <?php
    endwhile; ?>
                    </tbody>
                </table>

            <?php
elseif ($activeTab === 'reviews'): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Jeu</th>
                            <th>Contenu</th>
                            <th>Recommandé</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
    mysqli_data_seek($reviews, 0);
    while ($r = mysqli_fetch_assoc($reviews)):
?>
                            <tr>
                                <td>#
                                    <?php echo $r['id']; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['username']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['game_title']); ?>
                                </td>
                                <td style="max-width: 300px;">
                                    <?php echo htmlspecialchars(substr($r['content'], 0, 100)); ?>...
                                </td>
                                <td>
                                    <?php echo $r['is_recommended'] ? '👍' : '👎'; ?>
                                </td>
                                <td>
                                    <a href="?tab=reviews&delete_review=<?php echo $r['id']; ?>" class="btn-sm btn-delete"
                                        onclick="return confirm('Supprimer cet avis ?')">🗑️</a>
                                </td>
                            </tr>
                        <?php
    endwhile; ?>
                    </tbody>
                </table>
            <?php
endif; ?>
        </main>
    </div>

    <!-- Modal Rôle -->
    <div class="modal" id="roleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Changer le rôle</h3>
                <button class="modal-close" onclick="closeModal('roleModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="roleUserId">
                <div class="form-group">
                    <label>Nouveau rôle</label>
                    <select name="new_role" id="roleSelect">
                        <option value="user">Utilisateur</option>
                        <option value="moderator">Modérateur</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
                <button type="submit" name="change_role" class="btn-green" style="width: 100%; margin-top: 15px;">Mettre
                    à jour</button>
            </form>
        </div>
    </div>

    <!-- Modal Balance -->
    <div class="modal" id="balanceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter du solde</h3>
                <button class="modal-close" onclick="closeModal('balanceModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="balanceUserId">
                <div class="form-group">
                    <label>Montant (€)</label>
                    <input type="number" step="0.01" name="amount" placeholder="50.00">
                </div>
                <button type="submit" name="add_balance" class="btn-green"
                    style="width: 100%; margin-top: 15px;">Ajouter</button>
            </form>
        </div>
    </div>

    <!-- Modal CTF -->
    <div class="modal" id="ctfModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🚩 Réinitialiser Points CTF</h3>
                <button class="modal-close" onclick="closeModal('ctfModal')">&times;</button>
            </div>
            <form method="POST"
                onsubmit="return confirm('Voulez-vous vraiment réinitialiser les points CTF de cet utilisateur ?');">
                <input type="hidden" name="user_id" id="ctfUserId">
                <div style="padding: 20px; text-align: center; color: var(--text-muted);">
                    <div style="font-size: 48px; margin-bottom: 10px;">⚠️</div>
                    <p>Cette action va remettre les points CTF de l'utilisateur à <strong
                            style="color: var(--text-primary);">0</strong>.</p>
                    <p style="font-size: 12px; margin-top: 15px;">Les challenges complétés en session resteront visibles
                        jusqu'à la prochaine déconnexion.</p>
                </div>
                <button type="submit" name="reset_user_ctf" class="btn-sm btn-delete"
                    style="width: 100%; padding: 12px; font-size: 14px;">🔄 Réinitialiser les points</button>
            </form>
        </div>
    </div>

    <script>
        function openRoleModal(userId, currentRole) {
            document.getElementById('roleUserId').value = userId;
            document.getElementById('roleSelect').value = currentRole;
            document.getElementById('roleModal').classList.add('active');
        }

        function openBalanceModal(userId) {
            document.getElementById('balanceUserId').value = userId;
            document.getElementById('balanceModal').classList.add('active');
        }

        function openCTFModal(userId) {
            document.getElementById('ctfUserId').value = userId;
            document.getElementById('ctfModal').classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // Fermer modal au clic extérieur
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) closeModal(this.id);
            });
        });
    </script>
</body>

</html>
