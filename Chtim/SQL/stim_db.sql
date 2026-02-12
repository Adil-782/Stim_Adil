-- ========================================================
-- PROJET : stim (Vapor Clone)
-- VERSION : 3.0 (Finale)
-- ========================================================

-- 1. Nettoyage et Création de la base
DROP DATABASE IF EXISTS stim_db;
CREATE DATABASE stim_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stim_db;

-- ========================================================
-- TABLE : USERS
-- ========================================================
CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       username VARCHAR(50) NOT NULL UNIQUE,
                       password VARCHAR(255) NOT NULL, -- Stocké en MD5 (CWE-328)
                       email VARCHAR(100),
                       role ENUM('user', 'admin', 'moderator') DEFAULT 'user',
                       wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
                       ctf_points INT DEFAULT 0, -- Points des challenges CTF
                       avatar VARCHAR(255) DEFAULT 'https://api.dicebear.com/7.x/avataaars/svg?seed=Felix',
                       is_public BOOLEAN DEFAULT TRUE -- Cible pour l'IDOR
);

-- ========================================================
-- TABLE : GAMES
-- ========================================================
CREATE TABLE games (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       title VARCHAR(100) NOT NULL,
                       description TEXT,
                       price DECIMAL(10, 2) NOT NULL,
                       image_cover VARCHAR(255), -- URL ou chemin local
                       release_date DATE
);


-- ========================================================
-- TABLE : REVIEWS
-- Mise à jour : Ajout de la colonne is_recommended
-- ========================================================
CREATE TABLE reviews (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         game_id INT,
                         user_id INT,
                         content TEXT, -- Faille XSS Stored ici
                         is_recommended BOOLEAN DEFAULT TRUE, -- 1 = Pouce Bleu, 0 = Pouce Rouge
                         posted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                         FOREIGN KEY (game_id) REFERENCES games(id),
                         FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================================
-- TABLE : USER_SOLVES (Suivi des flags validés)
-- ========================================================
CREATE TABLE user_solves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    challenge_flag VARCHAR(255) NOT NULL,
    solved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_solve (user_id, challenge_flag)
);


-- ========================================================
-- TABLE : LIBRARY
-- ========================================================
CREATE TABLE library (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         user_id INT,
                         game_id INT,
                         purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                         FOREIGN KEY (user_id) REFERENCES users(id),
                         FOREIGN KEY (game_id) REFERENCES games(id)
);

-- ========================================================
-- TABLE : HIDDEN_KEYS (Pour Injection SQL)
-- ========================================================
CREATE TABLE hidden_keys (
                             id INT AUTO_INCREMENT PRIMARY KEY,
                             key_code VARCHAR(100),
                             service_name VARCHAR(100),
                             dummy_price DECIMAL(10,2),
                             secret_flag VARCHAR(255)   -- LE FLAG EST ICI
);

-- ========================================================
-- TABLE : PASSWORD_RESET_TOKENS (Challenge 1 - Host Header Injection)
-- ========================================================
CREATE TABLE password_reset_tokens (
                                       id INT AUTO_INCREMENT PRIMARY KEY,
                                       user_id INT NOT NULL,
                                       token VARCHAR(64) NOT NULL UNIQUE,
                                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                       expires_at DATETIME,
                                       used BOOLEAN DEFAULT FALSE,
                                       FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================================
-- TABLE : GAME_KEYS (Challenge 2 - SQL Injection + IDOR)
-- ========================================================
CREATE TABLE game_keys (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           user_id INT NOT NULL,
                           game_id INT NOT NULL,
                           key_code VARCHAR(50) NOT NULL,
                           flag VARCHAR(255),
                           created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                           FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                           FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

-- ========================================================
-- INSERTION DES DONNÉES (FIXTURES)
-- ========================================================

-- 1. Utilisateurs (Avec Avatars fixés)
INSERT INTO users (username, password, email, role, wallet_balance, is_public, avatar) VALUES
                                                                                           ('admin', MD5('admin123'), 'gaben@stim.corp', 'admin', 999999.99, 1, 'https://api.dicebear.com/7.x/avataaars/svg?seed=Gabe'),
                                                                                           ('adil_dev', MD5('glados'), 'adil@stim.local', 'admin', 100.00, 1, 'https://api.dicebear.com/7.x/avataaars/svg?seed=adil_dev'),
                                                                                           ('DarkSasuke', MD5('password123'), 'sasuke@konoha.fr', 'user', 15.50, 1, 'https://api.dicebear.com/7.x/avataaars/svg?seed=Sasuke'),
                                                                                           ('NoobSaibot', MD5('toasty'), 'noob@mk.net', 'user', 0.00, 0, 'https://api.dicebear.com/7.x/avataaars/svg?seed=Noob'),
                                                                                           ('HackerMan', MD5('mrrobot'), 'elliot@fsociety.dat', 'user', 50.00, 1, 'https://api.dicebear.com/7.x/avataaars/svg?seed=Hacker');

-- 2. Jeux (Tous les jeux en une seule insertion - 17 jeux au total)
INSERT INTO games (title, description, price, image_cover, release_date) VALUES
-- Jeux initiaux (5)
('Half-Life 3', 'Le mythe devenu réalité. Prenez votre pied de biche et préparez-vous.', 59.99, 'https://upload.wikimedia.org/wikipedia/en/2/25/Half-Life_2_cover.jpg', '2025-04-01'),
('Grand Theft Auto VI', 'Retournez à Vice City. Braquages, néons et trahisons en 8K.', 69.99, 'https://upload.wikimedia.org/wikipedia/en/a/a5/Grand_Theft_Auto_V.png', '2025-12-25'),
('Cyberpunk 2078', 'Le futur est sombre, mais vos implants brillent. (Garantie sans bugs)', 29.99, 'https://upload.wikimedia.org/wikipedia/en/9/9f/Cyberpunk_2077_box_art.jpg', '2024-01-10'),
('Elden Ring: Easy Mode', 'Enfin accessible aux journalistes de jeux vidéo. Appuyez sur X pour gagner.', 49.99, 'https://upload.wikimedia.org/wikipedia/en/b/b9/Elden_Ring_Box_art.jpg', '2023-05-15'),
('Goat Simulator: Space', 'Personne ne vous entendra bêler dans l\'espace.', 15.00, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/265930/header.jpg', '2024-08-20'),

-- Nouveaux jeux AAA (3)
('Starfield: Galactic Edition', 'Explorez l\'univers infini avec des graphismes époustouflants et des bugs de lancement.', 79.99, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1716740/header.jpg', '2024-09-06'),
('Red Dead Redemption 3', 'Retournez dans le Far West pour une dernière chevauchée épique. Arthur est de retour !', 69.99, 'https://upload.wikimedia.org/wikipedia/en/4/44/Red_Dead_Redemption_II.jpg', '2025-10-26'),
('The Witcher 4: Wild Hunt Returns', 'Geralt reprend son épée pour une nouvelle aventure. Toss a coin to your witcher !', 59.99, 'https://upload.wikimedia.org/wikipedia/en/0/0c/Witcher_3_cover_art.jpg', '2025-05-19'),

-- Jeux Indépendants (3)
('Hollow Knight: Silksong', 'Enfin disponible ! L\'aventure de Hornet dans un royaume mystérieux.', 19.99, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/header.jpg', '2024-06-12'),
('Stardew Valley: City Life', 'Quittez la ferme et découvrez les joies de la vie urbaine. Nouveau DLC massif.', 14.99, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/413150/header.jpg', '2024-03-14'),
('Celeste: Mountain Peak', 'Escaladez de nouvelles montagnes avec Madeline dans cette suite tant attendue.', 24.99, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/504230/header.jpg', '2024-01-25'),

-- Simulateurs (2)
('Microsoft Flight Simulator 2026', 'Volez n\'importe où dans le monde avec un réalisme photographique. Carte graphique RTX 6090 recommandée.', 79.99, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1250410/header.jpg', '2026-01-18'),
('PowerWash Simulator: Mars Edition', 'Nettoyez la planète rouge avec votre jet d\'eau haute pression. Relaxant et addictif.', 29.99, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1290000/header.jpg', '2024-07-08'),

-- Jeux Gratuits (2)
('Valorant: Tactical Ops', 'Le FPS tactique gratuit de Riot Games. Améliorations majeures et nouvelle saison.', 0.00, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1172470/header.jpg', '2020-06-02'),
('Apex Legends: Season 25', 'Battle Royale gratuit avec de nouvelles légendes et une nouvelle carte volcanique.', 0.00, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1172470/header.jpg', '2019-02-04'),

-- Horreur (1)
('Resident Evil 9: Umbrella Returns', 'Les morts ne restent jamais enterrés. Survival horror pur et dur.', 64.99, 'https://upload.wikimedia.org/wikipedia/en/3/3e/Resident_Evil_Village.png', '2025-03-24'),

-- RPG (1)
('Baldur\'s Gate 4', 'Retournez dans les Royaumes Oubliés pour une aventure épique. 300+ heures de contenu.', 69.99, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1086940/header.jpg', '2026-08-03');


-- 3. Commentaires (Mise à jour avec Recommandation Oui/Non)
INSERT INTO reviews (game_id, user_id, content, is_recommended) VALUES
(1, 2, 'Incroyable, je ne pensais pas voir ça de mon vivant !', 1),
(3, 3, 'Encore quelques glitchs, mais jouable avec une RTX 5090.', 1),
(1, 1, 'Worth the weight.', 1),
(4, 2, 'Trop facile, aucun challenge. Remboursez !', 0);

-- 4. Bibliothèque (Pour tester l''IDOR)
INSERT INTO library (user_id, game_id) VALUES (2, 1);
INSERT INTO library (user_id, game_id) VALUES (3, 4);

-- 5. Flags (Injection SQL)
INSERT INTO hidden_keys (key_code, service_name, dummy_price, secret_flag) VALUES
('XJ9-ABCD-1234', 'System Admin Access', 0.00, 'FLAG{SQL_INJECTION_MASTER_stim}'),
('FREE-GAME-KEY', 'Half-Life 3 Dev Build', 0.00, 'Pas de flag ici, cherchez encore.');

-- 6. Game Keys (Challenge 2 - SQL Injection + IDOR)
INSERT INTO game_keys (user_id, game_id, key_code, flag) VALUES
(1, 1, 'HL3-GABE-NEWELL-2025', NULL),
(2, 1, 'HL3-DARK-SASUKE-KEY', NULL),
(2, 3, 'CP77-NIGHT-CITY-2078', NULL),
(3, 4, 'ELDR-EASY-MODE-NOOB', NULL),
(4, 5, 'GOAT-SPACE-HACKER-X', 'FLAG{IDOR_AND_SQLi_COMBO_BREAKER}');

-- 7. Admin user for Challenge 3 (XSS Cookie Theft)
INSERT INTO users (username, password, email, role, wallet_balance, is_public, avatar) VALUES
('admin_bot', MD5('super_secret_admin_2025'), 'admin@stim.corp', 'admin', 99999.99, 0, 'https://api.dicebear.com/7.x/avataaars/svg?seed=Admin');

-- ========================================================
-- SCRIPTS UTILITAIRES (Optionnels)
-- ========================================================

-- Réinitialisation des points CTF (à décommenter si nécessaire)
-- UPDATE users SET ctf_points = 0;
-- SELECT username, ctf_points FROM users;
