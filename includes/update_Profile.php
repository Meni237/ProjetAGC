<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['update_profile'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$new_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
    $stmt->execute([$new_email, $user_id]);
    $_SESSION['email'] = $new_email;
    echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
} catch (PDOException $e) {
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        echo json_encode(['success' => false, 'message' => 'Désolé, mais cette adresse mail existe déjà']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()]);
    }
}