<?php
session_start();

$message = "";
$messageType = "";

// Dossier d'upload unique à la racine du projet
$uploadDir = dirname(__DIR__) . '/uploads/';

// Création du répertoire s'il n'existe pas
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
    if (!is_dir($uploadDir)) {
        die("❌ ERREUR CRITIQUE: Impossible de créer le dossier d'upload. Vérifiez les permissions du serveur web sur : " . dirname($uploadDir));
    }
}

// Création du fichier flag si inexistant
$secretFile = __DIR__ . '/../var/www/internal/plans_deck2.txt';
$secretDir = dirname($secretFile);

if (!is_dir($secretDir)) {
    @mkdir($secretDir, 0777, true);
}

// Génération du fichier flag
if (!file_exists($secretFile)) {
    file_put_contents($secretFile, "
╔═══════════════════════════════════════════════════════════════════╗
║                     STEAM DECK 2 - CLASSIFIED                       ║
║                          INTERNAL USE ONLY                          ║
╠═══════════════════════════════════════════════════════════════════╣
║                                                                     ║
║  Project: Steam Deck 2 (Codename: APERTURE)                        ║
║  Status: Pre-Production                                             ║
║  Classification: TOP SECRET                                         ║
║                                                                     ║
║  Encryption Key for Technical Specifications:                       ║
║  ──────────────────────────────────────────                        ║
║                                                                     ║
║  🚩 FLAG: STEAM{UPL04D_BYPASS_M4ST3R}                              ║
║                                                                     ║
║  This key grants access to the full Steam Deck 2 blueprints.       ║
║  Do NOT share outside of approved personnel.                        ║
║                                                                     ║
╚═══════════════════════════════════════════════════════════════════╝
");
}

// Le .htaccess n'est plus créé automatiquement
// L'attaquant doit uploader son propre .htaccess pour exploiter la vulnérabilité

// Traitement de la suppression
if (isset($_GET['delete'])) {
    $fileToDelete = basename($_GET['delete']);
    $filePath = $uploadDir . $fileToDelete;

    if (file_exists($filePath) && $fileToDelete[0] !== '.') {
        // Vérifier que c'est bien un fichier et non un dossier
        if (is_file($filePath)) {
            if (unlink($filePath)) {
                $message = "✅ Fichier supprimé avec succès : " . htmlspecialchars($fileToDelete);
                $messageType = "success";
            } else {
                $message = "❌ Erreur lors de la suppression.";
                $messageType = "error";
            }
        } else {
            $message = "❌ Impossible de supprimer un dossier.";
            $messageType = "error";
        }
    } else {
        $message = "❌ Fichier introuvable.";
        $messageType = "error";
    }
}

// Traitement de l'upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $fileName = basename($file['name']);
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];

    // Récupération de l'extension (dernière extension)
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // VULNÉRABILITÉ: Interdit uniquement les fichiers .php
    // Tous les autres fichiers sont autorisés, y compris .htaccess
    if ($fileExt === 'php') {
        $message = "❌ Les fichiers .php sont interdits pour des raisons de sécurité !";
        $messageType = "error";
    } else {
        // VULNÉRABILITÉ 1: Aucune vérification du contenu MIME
        // VULNÉRABILITÉ 2: Pas de renommage du fichier
        // VULNÉRABILITÉ 3: Tous les fichiers sauf .php sont acceptés (.htaccess, .jpg, etc.)
        $targetPath = $uploadDir . $fileName;

        // Debug: vérifier que le dossier existe et est accessible
        if (!is_dir($uploadDir)) {
            $message = "❌ Erreur: Le dossier d'upload n'existe pas : " . htmlspecialchars($uploadDir);
            $messageType = "error";
        } elseif (!is_writable($uploadDir)) {
            $message = "❌ Erreur: Le dossier d'upload n'est pas accessible en écriture : " . htmlspecialchars($uploadDir);
            $messageType = "error";
        } elseif (!is_uploaded_file($fileTmp)) {
            $message = "❌ Erreur: Le fichier temporaire n'est pas valide";
            $messageType = "error";
        } elseif (move_uploaded_file($fileTmp, $targetPath)) {
            $message = "✅ Fichier uploadé avec succès : " . htmlspecialchars($fileName);
            $messageType = "success";
        } else {
            $message = "❌ Erreur lors de l'upload. Chemin: " . htmlspecialchars($targetPath) . " | Erreur: " . error_get_last()['message'];
            $messageType = "error";
        }
    }
}

// Lister les fichiers uploadés
$uploadedFiles = [];
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $f) {
        // Exclure les fichiers cachés (commençant par .) et les répertoires système
        if ($f !== '.' && $f !== '..' && $f[0] !== '.') {
            $uploadedFiles[] = $f;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Avatar - Steam Deck 2 Server</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0f1419;
            --bg-secondary: #16202d;
            --bg-card: #1c2938;
            --bg-hover: #243447;
            --accent-blue: #00d4ff;
            --accent-orange: #ff6b35;
            --accent-green: #1dd1a1;
            --text-primary: #e4e4e4;
            --text-muted: #8892a0;
            --border: rgba(255, 255, 255, 0.1);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #1a2332 0%, #0f1419 100%);
            border-bottom: 2px solid var(--accent-orange);
            padding: 20px 40px;
            box-shadow: var(--shadow);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--accent-orange) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo span {
            display: block;
            font-size: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 4px;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-card);
            padding: 10px 18px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-orange));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .page-title {
            grid-column: 1 / -1;
            margin-bottom: 20px;
        }

        .page-title h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .page-title p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .warning-banner {
            grid-column: 1 / -1;
            background: rgba(255, 107, 53, 0.1);
            border-left: 4px solid var(--accent-orange);
            padding: 20px;
            border-radius: var(--radius-md);
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .warning-banner .icon {
            font-size: 24px;
        }

        .warning-banner h4 {
            color: var(--accent-orange);
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .warning-banner p {
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .warning-banner code {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Consolas', monospace;
            color: var(--accent-blue);
        }

        .upload-section,
        .files-section {
            background: var(--bg-card);
            padding: 30px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .section-title h3 {
            font-size: 18px;
        }

        .upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius-md);
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: rgba(0, 212, 255, 0.02);
        }

        .upload-area:hover {
            border-color: var(--accent-blue);
            background: rgba(0, 212, 255, 0.05);
        }

        .upload-area .icon {
            font-size: 64px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .upload-area p {
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .upload-area .formats {
            font-size: 12px;
            color: var(--text-muted);
            opacity: 0.7;
        }

        input[type="file"] {
            display: none;
        }

        .btn {
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            background: linear-gradient(135deg, var(--accent-blue), #00a8cc);
            border: none;
            border-radius: var(--radius-md);
            color: white;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.3);
        }

        .alert {
            padding: 15px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
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

        .alert-success {
            background: rgba(29, 209, 161, 0.15);
            border: 1px solid rgba(29, 209, 161, 0.4);
            color: var(--accent-green);
        }

        .alert-error {
            background: rgba(255, 71, 87, 0.15);
            border: 1px solid rgba(255, 71, 87, 0.3);
            color: #ff4757;
        }

        .file-list {
            list-style: none;
        }

        .file-item {
            padding: 12px 16px;
            background: var(--bg-hover);
            border-radius: var(--radius-sm);
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .file-item:hover {
            background: rgba(0, 212, 255, 0.1);
        }

        .file-item a {
            color: var(--text-primary);
            text-decoration: none;
            font-size: 13px;
            font-family: 'Consolas', monospace;
        }

        .file-item a:hover {
            color: var(--accent-blue);
        }

        .security-hint {
            background: rgba(0, 0, 0, 0.3);
            border-left: 3px solid var(--accent-green);
            padding: 15px;
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            margin-top: 20px;
        }

        .security-hint h4 {
            color: var(--accent-green);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .security-hint p {
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .security-hint code {
            background: rgba(0, 212, 255, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--accent-blue);
            font-size: 12px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--accent-blue);
        }

        #termOutput::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <h1>🎮 Steam Deck 2 Server</h1>
                <span>PRE-PRODUCTION</span>
            </div>
            <div class="user-badge">
                <div class="avatar">👤</div>
                <div>
                    <div style="font-weight: 600; font-size: 13px;">adil_dev</div>
                    <div style="font-size: 10px; color: var(--text-muted);">Employee</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-title">
            <h2>📸 Modifier l'Avatar du Profil</h2>
            <p>Uploadez une nouvelle image pour votre profil employé.</p>
        </div>

        <div class="warning-banner">
            <div class="icon">⚠️</div>
            <div>
                <h4>Serveur de Pré-Production</h4>
                <p>Ce serveur contient des données sensibles. Les fichiers uploadés sont stockés dans
                    <code>/uploads/</code>
                </p>
                <p style="margin-top: 8px; font-size: 12px;">💡 <strong>Note de sécurité:</strong> Le serveur bloque les
                    fichiers <code>.php</code> pour des raisons de sécurité. Tous les autres fichiers sont autorisés...
                </p>
                <p style="margin-top: 8px; font-size: 12px; color: var(--accent-orange);">🚩 <strong>Hint:</strong> Le
                    fichier secret se trouve dans : <code>/var/www/internal/plans_deck2.txt</code></p>
                <p style="margin-top: 8px; font-size: 12px; color: var(--text-muted);">💡 <strong>Astuce:</strong>
                    Modifiez l'URL pour accéder aux fichiers uploadés et ouvrir le terminal.</p>
            </div>
        </div>

        <div class="upload-section">
            <div class="section-title">
                <span>📁</span>
                <h3>Upload d'Avatar</h3>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
                <?php
            endif; ?>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <label for="fileInput">
                    <div class="upload-area" id="uploadArea">
                        <div class="icon">🖼️</div>
                        <p id="fileName" style="font-weight: 600; color: var(--text-primary);">Cliquez ou glissez une
                            image ici</p>
                        <p class="formats">Formats acceptés : JPG, PNG, GIF</p>
                    </div>
                </label>
                <input type="file" name="avatar" id="fileInput" accept="image/*" required onchange="updateFileName()">
                <button type="submit" class="btn">UPLOADER L'AVATAR</button>
            </form>

            <a href="../challenges.php" class="back-link">← Retour aux challenges</a>
        </div>

        <div class="files-section">
            <div class="section-title">
                <span>📂</span>
                <h3>Fichiers Uploadés</h3>
            </div>

            <?php if (empty($uploadedFiles)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">Aucun fichier uploadé</p>
                <?php
            else: ?>
                <ul class="file-list">
                    <?php foreach ($uploadedFiles as $file): ?>
                        <li class="file-item">
                            <a href="../uploads/<?php echo htmlspecialchars($file); ?>" target="_blank">
                                <?php echo htmlspecialchars($file); ?>
                            </a>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <a href="?delete=<?php echo urlencode($file); ?>"
                                    onclick="return confirm('Supprimer <?php echo htmlspecialchars($file); ?> ?');"
                                    style="color: var(--accent-orange); font-size: 12px; text-decoration: none; cursor: pointer;">
                                    Supprimer</a>
                            </div>
                        </li>
                        <?php
                    endforeach; ?>
                </ul>
                <?php
            endif; ?>
        </div>
    </div>

    <!-- Terminal Modal -->
    <div id="terminalModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999; justify-content:center; align-items:center;">
        <div
            style="width:800px; max-width:95%; max-height:90vh; background:#0c0c0c; border:1px solid #444; overflow:hidden; display:flex; flex-direction:column;">
            <div
                style="padding:6px 12px; background:#0c0c0c; border-bottom:1px solid #333; display:flex; justify-content:space-between; align-items:center;">
                <span id="termTitle"
                    style="color:#ccc; font-size:12px; font-family:'Consolas',monospace;">root@stim</span>
                <span onclick="closeTerminal()"
                    style="color:#ccc; cursor:pointer; font-size:16px; padding:0 4px;">&times;</span>
            </div>
            <div id="termOutput"
                style="padding:10px; font-family:'Consolas','Courier New',monospace; font-size:14px; color:#ccc; overflow-y:auto; flex:1; min-height:350px; max-height:60vh; line-height:1.5; white-space:pre-wrap; word-break:break-all; background:#0c0c0c; scrollbar-width:none; -ms-overflow-style:none;">
            </div>
            <div
                style="padding:8px 10px; border-top:1px solid #333; display:flex; align-items:center; gap:6px; background:#0c0c0c;">
                <span id="termPrompt"
                    style="color:#ccc; font-family:'Consolas',monospace; font-size:14px; white-space:nowrap;">root@stim:<span
                        id="promptDir">~/uploads</span>$</span>
                <input id="termInput" type="text" autocomplete="off" spellcheck="false"
                    style="flex:1; background:transparent; border:none; outline:none; color:#ccc; font-family:'Consolas','Courier New',monospace; font-size:14px; caret-color:#ccc;">
            </div>
        </div>
    </div>

    <script>
        function updateFileName() {
            const input = document.getElementById('fileInput');
            const fileName = document.getElementById('fileName');
            if (input.files.length > 0) {
                fileName.textContent = '✅ ' + input.files[0].name;
                fileName.style.color = 'var(--accent-green)';
            }
        }

        // Drag & Drop
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = 'var(--accent-blue)';
            uploadArea.style.background = 'rgba(0, 212, 255, 0.1)';
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = 'var(--border)';
            uploadArea.style.background = 'rgba(0, 212, 255, 0.02)';
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                updateFileName();
            }
            uploadArea.style.borderColor = 'var(--border)';
            uploadArea.style.background = 'rgba(0, 212, 255, 0.02)';
        });

        // ===== Interactive Terminal =====
        let shellUrl = '';
        const baseDir = '<?php echo str_replace("\\", "/", __DIR__); ?>/uploads/<?php echo $sessionId; ?>';
        let currentDir = baseDir;
        let commandHistory = [];
        let historyIndex = -1;

        function openTerminal(fileUrl) {
            shellUrl = fileUrl;
            document.getElementById('terminalModal').style.display = 'flex';
            document.getElementById('termTitle').textContent = fileUrl;
            document.getElementById('termInput').focus();
            updatePromptDir();
        }

        function closeTerminal() {
            document.getElementById('terminalModal').style.display = 'none';
            currentDir = baseDir;
        }

        function updatePromptDir() {
            let displayDir = currentDir.replace(baseDir, '~/uploads');
            if (displayDir === '') displayDir = '/';
            document.getElementById('promptDir').textContent = displayDir;
        }

        function appendOutput(html) {
            const out = document.getElementById('termOutput');
            out.innerHTML += html;
            out.scrollTop = out.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function executeCommand(cmd) {
            cmd = cmd.trim();
            if (!cmd) return;

            // Show the prompt + command
            let displayDir = currentDir.replace(baseDir, '~/uploads');
            if (displayDir === '') displayDir = '/';
            appendOutput(`root@stim:${escapeHtml(displayDir)}$ ${escapeHtml(cmd)}\n`);

            // Blacklist de commandes dangereuses
            const blockedCmds = ['rm', 'rmdir', 'mkdir', 'mv', 'cp', 'chmod', 'chown', 'chgrp', 'wget', 'curl',
                'apt', 'apt-get', 'yum', 'dnf', 'pip', 'npm', 'shutdown', 'reboot',
                'poweroff', 'halt', 'init', 'systemctl', 'service', 'kill', 'killall',
                'pkill', 'dd', 'mkfs', 'fdisk', 'mount', 'umount', 'useradd', 'userdel',
                'passwd', 'su', 'sudo', 'crontab', 'at', 'nc', 'ncat', 'netcat',
                'python', 'python3', 'perl', 'ruby', 'gcc', 'make', 'vi', 'vim', 'nano',
                'sed', 'awk', 'tee', 'truncate', 'shred', 'unlink', 'touch', 'ln'];
            const firstWord = cmd.split(/[\s;|&]/)[0].trim();
            if (blockedCmds.includes(firstWord)) {
                appendOutput(`bash: ${escapeHtml(firstWord)}: command not found\n`);
                return;
            }

            // Built-in commands
            if (cmd === 'clear') {
                document.getElementById('termOutput').innerHTML = '';
                return;
            }


            // Build the actual command with cd prefix for directory tracking
            let realCmd = `cd ${currentDir} && ${cmd}`;

            // Special handling for cd commands
            const cdMatch = cmd.match(/^cd\s+(.*)/);
            if (cdMatch) {
                // Use cd + pwd to get the new directory
                realCmd = `cd ${currentDir} && cd ${cdMatch[1]} && pwd`;
            }

            try {
                const response = await fetch(`${shellUrl}?cmd=${encodeURIComponent(realCmd)}`);
                const text = await response.text();

                // Extract content between <pre> tags
                const match = text.match(/<pre>([\s\S]*?)<\/pre>/);
                const output = match ? match[1].trim() : text.trim();

                if (cdMatch) {
                    // If cd was successful, update the current directory
                    if (output && !output.includes('No such file') && !output.includes('Not a directory')) {
                        const newDir = output.trim();
                        // Restreindre la navigation: ne pas aller au-dessus du répertoire stim (Chtim)
                        // baseDir exemple: /var/www/SAEPenTest/Chtim/challenges/uploads/SESSION
                        // On veut autoriser jusqu'à /var/www/SAEPenTest/Chtim
                        const stimDirIndex = baseDir.indexOf('/Chtim');
                        const stimDir = stimDirIndex !== -1 ? baseDir.substring(0, stimDirIndex + 6) : baseDir.substring(0, baseDir.indexOf('/challenges'));

                        if (newDir.startsWith(stimDir)) {
                            currentDir = newDir;
                            updatePromptDir();
                        } else {
                            appendOutput(`bash: cd: ${escapeHtml(cdMatch[1])}: Permission denied\\n`);
                        }
                    } else {
                        appendOutput(`bash: cd: ${escapeHtml(cdMatch[1])}: No such file or directory\n`);
                    }
                } else {
                    if (output) {
                        appendOutput(escapeHtml(output) + '\n');
                    }
                }
            } catch (error) {
                appendOutput(`Error: ${escapeHtml(error.message)}\n`);
            }
        }

        // Handle input
        document.getElementById('termInput').addEventListener('keydown', async (e) => {
            if (e.key === 'Enter') {
                const input = e.target;
                const cmd = input.value;
                input.value = '';

                if (cmd.trim()) {
                    commandHistory.push(cmd);
                    historyIndex = commandHistory.length;
                }

                await executeCommand(cmd);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (historyIndex > 0) {
                    historyIndex--;
                    e.target.value = commandHistory[historyIndex];
                }
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (historyIndex < commandHistory.length - 1) {
                    historyIndex++;
                    e.target.value = commandHistory[historyIndex];
                } else {
                    historyIndex = commandHistory.length;
                    e.target.value = '';
                }
            }
        });

        // Close modal on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeTerminal();
        });

        // Click outside to close
        document.getElementById('terminalModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('terminalModal')) closeTerminal();
        });
    </script>
</body>

</html>
