<?php
session_start();
require_once "config.php";

// Définir les en-têtes de sécurité
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer-when-downgrade");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net;");

// Fonction pour enregistrer les erreurs
function logError($pdo, $user_id, $error_code, $message, $details = '') {
    $log_dir = __DIR__ . '/logs/';
    $log_file = $log_dir . 'errors.log';

    // Créer le répertoire logs/ s'il n'existe pas
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }

    $log_message = date('Y-m-d H:i:s') . " [ERROR $error_code] User ID: " . ($user_id ?? 'N/A') . " - $message - Details: $details\n";

    if (is_writable($log_dir)) {
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id ?? null, "Erreur $error_code", "$message - $details"]);
    } catch (PDOException $e) {
        if (is_writable($log_dir)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " [DB LOG ERROR] " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

// Fonction d'affichage d'erreur
function displayErrorPage($code, $title, $message, $details = '') {
    http_response_code($code);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title><?= $code . ' - ' . htmlspecialchars($title) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="w-full max-w-md p-8 text-center bg-white rounded-lg shadow-lg">
            <h1 class="mb-4 text-5xl font-bold text-red-600"><?= $code ?></h1>
            <h2 class="mb-4 text-2xl font-semibold text-gray-800"><?= htmlspecialchars($title) ?></h2>
            <p class="mb-6 text-gray-600"><?= htmlspecialchars($message) ?></p>
            <?php if ($details): ?>
                <p class="mb-6 text-sm text-gray-500"><?= htmlspecialchars($details) ?></p>
            <?php endif; ?>
            <a href="/projetAGC/pages/dashboard.php" class="inline-block px-6 py-3 text-white bg-blue-600 rounded hover:bg-blue-700">Retour au tableau de bord</a>
        </div>
        <script>
            Swal.fire({
                icon: '<?= $code === 404 ? "warning" : "error" ?>',
                title: '<?= htmlspecialchars($title) ?>',
                text: '<?= htmlspecialchars($message) ?>',
                confirmButtonText: 'OK',
                timer: 5000,
                timerProgressBar: true
            }).then(() => {
                window.location.href = '/projetAGC/pages/dashboard.php';
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

// Logique principale
try {
    if (!isset($_GET['file']) || !isset($_SESSION['user_id'])) {
        logError($pdo, $_SESSION['user_id'] ?? null, 403, "Accès non autorisé", "Paramètre manquant ou session invalide");
        displayErrorPage(403, "Accès interdit", "Vous n'êtes pas autorisé à accéder à ce fichier.");
    }

    $file_param = filter_var($_GET['file'], FILTER_SANITIZE_STRING);
    $filename = basename(urldecode($file_param)); // Décoder l'URL pour gérer les caractères spéciaux

    // Regex permissive pour autoriser les caractères spéciaux comme é, è, apostrophe, espaces, etc.
    if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.\s\'éèêëàáâãäåçìíîïòóôõöùúûüýÿ]+$/u', $filename)) {
        logError($pdo, $_SESSION['user_id'], 400, "Requête invalide", "Nom de fichier non valide: $file_param, Décodé: $filename");
        displayErrorPage(400, "Requête invalide", "Le paramètre de fichier est manquant ou non valide. Nom: $filename");
    }

    // Construire le chemin complet
    $full_path = UPLOAD_DIR . $filename;

    // Normaliser le chemin pour résoudre les "../"
    $full_path = realpath($full_path);

    // Vérifier si le fichier existe physiquement
    if (!$full_path || !file_exists($full_path)) {
        logError($pdo, $_SESSION['user_id'], 404, "Fichier non trouvé sur le serveur", "Chemin: $full_path, File param: $file_param");
        displayErrorPage(404, "Fichier introuvable", "Le fichier n'existe pas sur le serveur.");
    }

    // Vérification des droits d'accès
    // Rechercher dans la base avec le nom du fichier ou le chemin relatif
    $db_file_path = 'C:\xampp\htdocs\ProjetAgc\includes/../uploads/' . $filename; // Adapter selon ce qui est stocké dans file_path
    $stmt = $pdo->prepare("SELECT id, user_id FROM documents WHERE file_path = ? OR file_path = ?");
    $stmt->execute([$filename, $db_file_path]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    // Document introuvable dans la base de données
    if (!$document) {
        logError($pdo, $_SESSION['user_id'], 404, "Fichier non trouvé dans la base", "Nom: $filename, Chemin DB attendu: $db_file_path");
        displayErrorPage(404, "Fichier introuvable", "Ce fichier n’est pas enregistré dans la base de données.");
    }

    // Si non admin et non propriétaire, on vérifie les partages
    if ($_SESSION['role'] !== 'admin' && $document['user_id'] != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("SELECT 1 FROM shared_documents WHERE document_id = ? AND shared_with_user_id = ?");
        $stmt->execute([$document['id'], $_SESSION['user_id']]);
        $hasAccess = $stmt->fetchColumn();

        if (!$hasAccess) {
            logError($pdo, $_SESSION['user_id'], 403, "Accès refusé", "Fichier: $filename");
            displayErrorPage(403, "Accès interdit", "Vous n'avez pas la permission d'accéder à ce fichier.");
        }
    }

    // Servir le fichier
    header('Content-Type: ' . mime_content_type($full_path));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($full_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    readfile($full_path);
    exit();

} catch (Exception $e) {
    logError($pdo, $_SESSION['user_id'] ?? null, 500, "Erreur interne", $e->getMessage());
    displayErrorPage(500, "Erreur serveur", "Une erreur inattendue est survenue.");
}
?>