<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['update_profile'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$new_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

$stmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
$success = $stmt->execute([$new_email, $user_id]);

if ($success) {
    $_SESSION['email'] = $new_email;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur de mise à jour']);
}
?>