<?php
session_start();
require_once "config.php";

// Définir les en-têtes de sécurité
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer-when-downgrade");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net;");

// Fonction pour enregistrer les erreurs dans la table logs et dans un fichier
function logError($pdo, $user_id, $error_code, $message, $details = '') {
    // Log dans un fichier
    $log_dir = __DIR__ . '/logs/';
    $log_file = $log_dir . 'errors.log';
    $fallback_log_file = '/tmp/projetAGC_errors.log'; // Dossier de secours

    $log_message = date('Y-m-d H:i:s') . " [ERROR $error_code] User ID: " . ($user_id ?? 'N/A') . " - $message - Details: $details\n";

    // Vérifier si le dossier logs existe et est accessible
    if (!file_exists($log_dir) || !is_writable($log_dir)) {
        // Utiliser le dossier de secours
        file_put_contents($fallback_log_file, $log_message, FILE_APPEND);
    } else {
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }

    // Log dans la base de données
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id ?? null, "Erreur $error_code", "$message - $details"]);
    } catch (PDOException $e) {
        // Log de secours dans le fichier
        $error_log = file_exists($log_dir) && is_writable($log_dir) ? $log_file : $fallback_log_file;
        file_put_contents($error_log, date('Y-m-d H:i:s') . " [DB LOG ERROR] " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Fonction pour afficher une page d'erreur moderne
function displayErrorPage($code, $title, $message, $details = '') {
    http_response_code($code);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $code . ' - ' . htmlspecialchars($title); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="w-full max-w-md p-8 text-center bg-white rounded-lg shadow-lg">
            <h1 class="mb-4 text-5xl font-bold text-red-600"><?php echo $code; ?></h1>
            <h2 class="mb-4 text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($title); ?></h2>
            <p class="mb-6 text-gray-600"><?php echo htmlspecialchars($message); ?></p>
            <?php if ($details): ?>
                <p class="mb-6 text-sm text-gray-500"><?php echo htmlspecialchars($details); ?></p>
            <?php endif; ?>
            <a href="/projetAGC/dashboard.php" class="inline-block px-6 py-3 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">Retour au tableau de bord</a>
        </div>
        <script>
            Swal.fire({
                icon: '<?php echo $code === 404 ? "warning" : "error"; ?>',
                title: '<?php echo htmlspecialchars($title); ?>',
                text: '<?php echo htmlspecialchars($message); ?>',
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
    // Vérifier la présence du paramètre file et de la session utilisateur
    if (!isset($_GET['file']) || !isset($_SESSION['user_id'])) {
        logError($pdo, $_SESSION['user_id'] ?? null, 403, "Accès non autorisé", "Tentative d'accès sans fichier ou session");
        displayErrorPage(403, "Accès interdit", "Vous n'êtes pas autorisé à accéder à ce fichier.", "Veuillez vous connecter et réessayer.");
    }

    $file_path = filter_var($_GET['file'], FILTER_SANITIZE_STRING);
    if (empty($file_path) || !preg_match('/^Uploads\/[a-zA-Z0-9_\-\.]+$/', $file_path)) {
        logError($pdo, $_SESSION['user_id'], 400, "Requête invalide", "Paramètre 'file' vide ou format non valide: $file_path");
        displayErrorPage(400, "Requête invalide", "Le paramètre de fichier est manquant ou non valide.", "Vérifiez l'URL et réessayez.");
    }

    $full_path = UPLOAD_DIR . basename($file_path);

    // Vérifier l'existence du fichier
    if (!file_exists($full_path)) {
        logError($pdo, $_SESSION['user_id'], 404, "Fichier non trouvé", "Chemin: $file_path");
        displayErrorPage(404, "Fichier non trouvé", "Le fichier demandé n'existe pas sur le serveur.", "Vérifiez le lien ou contactez l'administrateur.");
    }

    // Vérifier l'accès au document
    $stmt = $pdo->prepare("SELECT user_id FROM documents WHERE file_path = ?");
    $stmt->execute([$file_path]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document || ($_SESSION['role'] !== 'admin' && $document['user_id'] != $_SESSION['user_id'])) {
        logError($pdo, $_SESSION['user_id'], 403, "Accès non autorisé", "Utilisateur non autorisé pour le fichier: $file_path");
        displayErrorPage(403, "Accès interdit", "Vous n'avez pas la permission d'accéder à ce fichier.", "Seuls les administrateurs ou le propriétaire du fichier peuvent y accéder.");
    }

    // Servir le fichier
    header('Content-Type: ' . mime_content_type($full_path));
    header('Content-Disposition: attachment; filename="' . basename($full_path) . '"');
    header('Content-Length: ' . filesize($full_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    readfile($full_path);
    exit();

} catch (Exception $e) {
    // Gérer les erreurs inattendues
    logError($pdo, $_SESSION['user_id'] ?? null, 500, "Erreur interne", $e->getMessage());
    displayErrorPage(500, "Erreur interne du serveur", "Une erreur inattendue s'est produite.", "Veuillez réessayer plus tard ou contacter l'administrateur.");
}
?>