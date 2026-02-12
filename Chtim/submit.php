<?php
// On inclut la connexion
if (file_exists('includes/db_connect.php')) {
    include 'includes/db_connect.php';
    include 'includes/header.php';
} else {
    include 'db_connect.php';
    include 'header.php';
}

$msg = "";
$msg_class = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Récupération des infos (Titre et Prix)
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $price = floatval($_POST['price']);
    $desc = "Jeu indépendant développé par la communauté."; // Description par défaut

    // 2. Gestion de l'Upload (VULNÉRABLE : Pas de vérification d'extension !)
    // On garde le nom d'origine pour que l'attaquant puisse appeler "shell.php"
    $filename = basename($_FILES["game_image"]["name"]);
    $target_dir = "uploads/";
    $target_file = $target_dir . $filename;

    $uploadOk = 1;

    // Vérification basique (si fichier vide)
    if (empty($filename)) {
        $msg = "Veuillez sélectionner une image.";
        $uploadOk = 0;
    }

    // Tentative d'upload
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["game_image"]["tmp_name"], $target_file)) {

            // 3. INSERTION EN BASE DE DONNÉES (C'est ce qui manquait !)
            // On insère le jeu pour qu'il apparaisse dans le store
            $sql = "INSERT INTO games (title, description, price, image_cover, release_date) 
                    VALUES ('$title', '$desc', $price, '$filename', NOW())";

            if (mysqli_query($conn, $sql)) {
                $msg = "Succès ! Le jeu a été publié sur le magasin.";
                $msg_class = "color: #a4d007;"; // Vert
            } else {
                $msg = "Erreur SQL : " . mysqli_error($conn);
                $msg_class = "color: red;";
            }

        } else {
            $msg = "Erreur lors du transfert du fichier.";
            $msg_class = "color: red;";
        }
    }
}
?>

    <div class="container" style="margin-top: 40px;">

        <h2 style="color: white; border-bottom: 1px solid #3a4b61; padding-bottom: 10px;">Espace Développeurs Steamworks</h2>
        <p style="color: #acb2b8;">Publiez votre jeu indépendamment sur stim. (Attention, zone bêta)</p>

        <?php if($msg): ?>
            <div style="background: rgba(0,0,0,0.3); padding: 15px; border: 1px solid #555; margin-bottom: 20px; <?php echo $msg_class; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="login-wrapper" style="margin: 0; width: 100%; max-width: 800px;">
            <div class="login-left" style="width: 100%; border:none;">

                <form action="submit.php" method="post" enctype="multipart/form-data">

                    <div class="steam-input-group">
                        <label>Titre du jeu</label>
                        <input type="text" name="title" id="gameTitleInput" class="steam-input" required placeholder="Ex: Half-Life 4">
                    </div>

                    <div class="steam-input-group">
                        <label>Prix (€)</label> <input type="number" name="price" class="steam-input" step="0.01" required placeholder="Ex: 19.99">
                    </div>

                    <div class="steam-input-group">
                        <label>Jaquette (Image du magasin)</label>
                        <input type="file" name="game_image" id="imageInput" class="steam-input" style="padding: 5px;">
                        <small style="color:#666;">Formats acceptés : JPG, PNG... (et PHP si vous êtes malins héhé)</small>
                    </div>

                    <button type="submit" class="btn-green" style="width: 100%; margin-top: 20px;">Publier le jeu</button>
                </form>

            </div>
        </div>

        <div id="previewArea" style="display:none; margin-top:40px; background: rgba(0,0,0,0.2); padding: 20px;">
            <h3 style="color: #66c0f4;">Aperçu Magasin : <span id="previewTitle" style="color: white;"></span></h3>
            <div class="game-row" style="background-color: rgba(102, 192, 244, 0.1); cursor: default;">
                <img id="previewImg" src="#" style="width: 120px; height: 55px; object-fit: cover; margin-right: 15px; border: 1px solid #555;">
                <div class="game-info">
                    <div class="game-title" id="previewTitleList" style="font-weight: bold;">Titre du jeu</div>
                    <div class="game-price" style="color: #c7d5e0;">Prix €</div>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Script JS pour la prévisualisation (DOM Manipulation)
        document.getElementById('imageInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('previewArea').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('gameTitleInput').addEventListener('input', function(e) {
            const val = e.target.value;
            document.getElementById('previewTitle').innerText = val;
            document.getElementById('previewTitleList').innerText = val;
        });
    </script>

<?php
if (file_exists('includes/footer.php')) {
    include 'includes/footer.php';
} else {
    include 'footer.php';
}
?>
