<?php
session_start();
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $doc_id = filter_input(INPUT_POST, 'document_id', FILTER_SANITIZE_NUMBER_INT);
    $target_user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);

    // Vérifier que l'utilisateur est bien propriétaire OU admin
    $stmt = $pdo->prepare("SELECT user_id FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch();

    if (!$doc || ($_SESSION['role'] !== 'admin' && $doc['user_id'] != $_SESSION['user_id'])) {
        die("Vous n'avez pas l'autorisation de partager ce document.");
    }

    // Ajouter le partage
    $stmt = $pdo->prepare("INSERT IGNORE INTO shared_documents (document_id, shared_with_user_id) VALUES (?, ?)");
    $stmt->execute([$doc_id, $target_user_id]);

    header("Location: archive_content.php?shared=success");
    exit();
}
?>