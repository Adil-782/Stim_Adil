<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['game_id'])) {
    header("Location: store.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$game_id = intval($_POST['game_id']);

// FAILLE : On récupère le prix envoyé par l'utilisateur sans vérifier en BDD !
// Si l'utilisateur envoie un prix négatif, on lui DONNE de l'argent.
$price = floatval($_POST['price']);

// 1. Récupérer infos utilisateur (Solde actuel)
$query_user = mysqli_query($conn, "SELECT wallet_balance FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($query_user);

// 2. Vérifier si déjà acheté
$check_owned = mysqli_query($conn, "SELECT * FROM library WHERE user_id = $user_id AND game_id = $game_id");
if (mysqli_num_rows($check_owned) > 0) {
    header("Location: game.php?id=$game_id&error=owned");
    exit();
}

// 3. Vérifier solde et acheter
// Note : Si le prix est négatif (ex: -500), la condition reste vraie car Solde >= -500
if ($user['wallet_balance'] >= $price) {

    // Soustraction : Si price est -1000 -> Solde - (-1000) = Solde + 1000
    $new_balance = $user['wallet_balance'] - $price;

    // Mise à jour BDD
    mysqli_query($conn, "UPDATE users SET wallet_balance = $new_balance WHERE id = $user_id");
    mysqli_query($conn, "INSERT INTO library (user_id, game_id) VALUES ($user_id, $game_id)");

    // Mise à jour session
    $_SESSION['wallet'] = $new_balance;

    header("Location: profile.php?success=bought");
} else {
    header("Location: game.php?id=$game_id&error=funds");
}
?>
