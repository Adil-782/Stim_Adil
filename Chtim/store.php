<?php
session_start();
include 'includes/db_connect.php';
include 'includes/header.php';

$search = "";
$sql = "SELECT * FROM games";

// ---------------------------------------------------------
// FAILLE : SQL INJECTION (VULNÉRABILITÉ OBLIGATOIRE)
// ---------------------------------------------------------
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    // Pas de requête préparée ici = Faille SQLi
    $sql = "SELECT * FROM games WHERE title LIKE '%$search%'";
}
// ---------------------------------------------------------

$result = mysqli_query($conn, $sql);

// Requête pour les jeux tendances (les 8 plus récents)
$trendingSql = "SELECT * FROM games ORDER BY release_date DESC LIMIT 8";
$trendingResult = mysqli_query($conn, $trendingSql);
$trendingGames = [];
if ($trendingResult && mysqli_num_rows($trendingResult) > 0) {
    while($row = mysqli_fetch_assoc($trendingResult)) {
        $trendingGames[] = $row;
    }
}
?>

    <!-- Section Populaires et Recommandés (Style Steam) -->
    <div class="trending-section" style="margin-bottom: 40px;">
        <h2 style="color:white; padding-bottom:10px; margin-bottom:20px; font-size: 18px; font-weight: 400;">
            Populaires et recommandés
        </h2>
        
        <div class="steam-carousel" style="background: linear-gradient(135deg, #1b2838 0%, #2a475e 100%); border-radius: 8px; overflow: hidden; position: relative; height: 400px;">
            <!-- Grande image featured à gauche -->
            <div class="featured-game" id="featuredGame" style="position: absolute; left: 0; top: 0; width: 65%; height: 100%; background-size: cover; background-position: center; transition: background-image 0.5s;">
                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.9)); padding: 30px;">
                    <h3 id="featuredTitle" style="color: white; font-size: 28px; margin-bottom: 10px; font-weight: 300;"></h3>
                    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                        <span id="featuredTag1" style="background: rgba(0, 212, 170, 0.9); color: white; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: 600;"></span>
                        <span id="featuredTag2" style="background: rgba(103, 193, 245, 0.9); color: white; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: 600;"></span>
                    </div>
                    <div id="featuredPrice" style="color: #00d4aa; font-size: 24px; font-weight: 700;"></div>
                </div>
            </div>
            
            <!-- Colonne de droite avec miniatures et infos -->
            <div style="position: absolute; right: 0; top: 0; width: 35%; height: 100%; background: rgba(22, 29, 42, 0.95); padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h4 id="sideTitle" style="color: white; font-size: 20px; margin-bottom: 15px; font-weight: 300;"></h4>
                    
                    <!-- Grille de miniatures 2x2 -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 20px;">
                        <div class="thumbnail" data-index="0" onclick="changeGame(0)" style="cursor: pointer; border-radius: 4px; overflow: hidden; aspect-ratio: 16/9; background-size: cover; background-position: center; transition: all 0.3s; border: 2px solid transparent;"></div>
                        <div class="thumbnail" data-index="1" onclick="changeGame(1)" style="cursor: pointer; border-radius: 4px; overflow: hidden; aspect-ratio: 16/9; background-size: cover; background-position: center; transition: all 0.3s; border: 2px solid transparent;"></div>
                        <div class="thumbnail" data-index="2" onclick="changeGame(2)" style="cursor: pointer; border-radius: 4px; overflow: hidden; aspect-ratio: 16/9; background-size: cover; background-position: center; transition: all 0.3s; border: 2px solid transparent;"></div>
                        <div class="thumbnail" data-index="3" onclick="changeGame(3)" style="cursor: pointer; border-radius: 4px; overflow: hidden; aspect-ratio: 16/9; background-size: cover; background-position: center; transition: all 0.3s; border: 2px solid transparent;"></div>
                    </div>
                    
                    <div id="gameStatus" style="background: rgba(0, 0, 0, 0.5); padding: 8px 12px; border-radius: 4px; color: #b8b6b4; font-size: 13px; margin-bottom: 10px;"></div>
                </div>
                
                <div>
                    <a id="viewGameLink" href="#" class="btn-view-game" style="display: block; text-align: center; background: rgba(0, 212, 170, 0.9); color: white; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s;">
                        Voir le jeu →
                    </a>
                </div>
            </div>
            
            <!-- Flèches de navigation -->
            <button onclick="prevGame()" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: rgba(0, 0, 0, 0.7); border: none; color: white; width: 50px; height: 50px; border-radius: 4px; cursor: pointer; font-size: 24px; z-index: 10; transition: all 0.3s;">
                ‹
            </button>
            <button onclick="nextGame()" style="position: absolute; right: 37%; top: 50%; transform: translateY(-50%); background: rgba(0, 0, 0, 0.7); border: none; color: white; width: 50px; height: 50px; border-radius: 4px; cursor: pointer; font-size: 24px; z-index: 10; transition: all 0.3s;">
                ›
            </button>
            
            <!-- Indicateurs de pagination (points) -->
            <div id="pagination" style="position: absolute; bottom: 20px; left: 20px; display: flex; gap: 8px; z-index: 10;"></div>
        </div>
    </div>

    <style>
        .thumbnail:hover {
            border-color: #00d4aa !important;
            transform: scale(1.05);
        }
        
        .thumbnail.active {
            border-color: #00d4aa !important;
        }
        
        .btn-view-game:hover {
            background: rgba(0, 212, 170, 1) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 212, 170, 0.4);
        }
        
        button:hover {
            background: rgba(0, 212, 170, 0.9) !important;
        }
    </style>

    <script>
        const games = <?php echo json_encode($trendingGames); ?>;
        let currentGameIndex = 0;
        
        // Fonction pour changer de jeu
        function changeGame(index) {
            currentGameIndex = index;
            updateCarousel();
        }
        
        function nextGame() {
            currentGameIndex = (currentGameIndex + 1) % games.length;
            updateCarousel();
        }
        
        function prevGame() {
            currentGameIndex = (currentGameIndex - 1 + games.length) % games.length;
            updateCarousel();
        }
        
        function updateCarousel() {
            if (games.length === 0) return;
            
            const game = games[currentGameIndex];
            
            // Gestion de l'image
            let imgSrc = game.image_cover;
            if (!imgSrc.startsWith('http')) {
                imgSrc = 'uploads/' + imgSrc;
            }
            
            // Mise à jour du jeu featured
            document.getElementById('featuredGame').style.backgroundImage = `url('${imgSrc}')`;
            document.getElementById('featuredTitle').textContent = game.title;
            document.getElementById('sideTitle').textContent = game.title;
            
            // Prix
            const priceDisplay = game.price == 0 ? "Gratuit" : parseFloat(game.price).toFixed(2) + " €";
            document.getElementById('featuredPrice').textContent = priceDisplay;
            
            // Tags
            const isFree = game.price == 0;
            document.getElementById('featuredTag1').textContent = isFree ? "Free-to-play" : "Disponible";
            document.getElementById('featuredTag2').textContent = parseFloat(game.price) > 60 ? "AAA" : "Meilleures ventes";
            
            // Status
            document.getElementById('gameStatus').textContent = isFree ? "Free-to-play" : "Disponible maintenant";
            
            // Lien
            document.getElementById('viewGameLink').href = 'game.php?id=' + game.id;
            
            // Mise à jour des miniatures
            const thumbnails = document.querySelectorAll('.thumbnail');
            thumbnails.forEach((thumb, idx) => {
                if (games[idx]) {
                    let thumbImg = games[idx].image_cover;
                    if (!thumbImg.startsWith('http')) {
                        thumbImg = 'uploads/' + thumbImg;
                    }
                    thumb.style.backgroundImage = `url('${thumbImg}')`;
                    thumb.classList.toggle('active', idx === currentGameIndex);
                }
            });
            
            // Pagination
            updatePagination();
        }
        
        function updatePagination() {
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            games.forEach((_, idx) => {
                const dot = document.createElement('div');
                dot.style.width = '10px';
                dot.style.height = '10px';
                dot.style.borderRadius = '50%';
                dot.style.background = idx === currentGameIndex ? '#00d4aa' : 'rgba(255,255,255,0.3)';
                dot.style.cursor = 'pointer';
                dot.style.transition = 'all 0.3s';
                dot.onclick = () => changeGame(idx);
                pagination.appendChild(dot);
            });
        }
        
        // Auto-play
        setInterval(nextGame, 5000);
        
        // Initialisation
        updateCarousel();
    </script>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2 style="color:white; border-bottom:1px solid #3a4b61; padding-bottom:10px; width:70%;">Jeux à la une</h2>

        <div class="search-area" style="width:28%;">
            <form method="GET" action="store.php" style="display:flex;">
                <input type="text" name="search" placeholder="recherche..." value="<?php echo htmlspecialchars($search); ?>" style="width:150px; margin-right:5px;">
                <button type="submit" style="background:none; border:none; cursor:pointer;">🔍</button>
            </form>
        </div>
    </div>

    <div class="games-list">
        <?php
        if (!$result) {
            echo "<p class='alert'>Erreur SQL : " . mysqli_error($conn) . "</p>";
        } else {
            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    // Lien cliquable vers la page du jeu
                    echo "<a href='game.php?id=" . $row['id'] . "' class='game-row'>";

                    // -----------------------------------------------------
                    // GESTION INTELLIGENTE DES IMAGES
                    // -----------------------------------------------------
                    $imgSrc = $row['image_cover'];
                    $isUrl = filter_var($imgSrc, FILTER_VALIDATE_URL);

                    // Si ce n'est pas une URL, on regarde dans le dossier uploads
                    if (!$isUrl) {
                        if (!empty($imgSrc) && file_exists("uploads/".$imgSrc)) {
                            $imgSrc = "uploads/" . $imgSrc;
                        } else {
                            // Image par défaut si fichier introuvable
                            $imgSrc = "https://via.placeholder.com/120x45/333/ccc?text=NO+IMAGE";
                        }
                    }

                    // Affichage de l'image
                    echo "<img src='$imgSrc' class='game-img' style='width:120px; height:55px; object-fit:cover; margin-right:15px;'>";

                    // Info du jeu
                    echo "<div class='game-info'>";
                    echo "<div class='game-title'>" . htmlspecialchars($row['title']) . "</div>";

                    // Gestion du prix (Gratuit ou Montant)
                    $priceDisplay = ($row['price'] == 0) ? "Gratuit" : $row['price'] . " €";
                    echo "<div class='game-price'>" . $priceDisplay . "</div>";
                    echo "</div>";

                    echo "</a>";
                }
            } else {
                echo "<p style='padding:20px;'>Aucun jeu trouvé.</p>";
            }
        }
        ?>
    </div>

    <div style="margin-top: 40px; background: #000; padding: 20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h3 style="color:#66c0f4; margin:0;">OFFRE DU WEEK-END</h3>
            <p style="margin:5px 0;">Économisez jusqu'à 0% sur nos jeux gratuits !</p>
        </div>
        <div class="btn-green" style="cursor:default;">En savoir plus</div>
    </div>

<?php include 'includes/footer.php'; ?>
