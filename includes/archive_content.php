<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Erreur : Utilisateur non connecté']));
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$documents = [];
$categories = [];

try {
    // Récupérer toutes les catégories disponibles
    $stmt = $pdo->query("SELECT category_id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer tous les documents avec le nom de la catégorie
    $stmt = $pdo->query("
        SELECT d.id, d.title, c.name AS category, d.file_path, d.created_at
        FROM documents d
        JOIN categories c ON d.category_id = c.category_id
        ORDER BY d.created_at DESC
    ");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gérer l'ajout d'un document
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
        if ($role === 'admin' || $role === 'user') {
            $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
            $category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT);

            // Vérifier si la catégorie existe
            $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_id = ?");
            $stmt->execute([$category_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Catégorie invalide.']);
                exit();
            }

            // Vérifier si un fichier a été envoyé via $_FILES
            if (empty($_FILES['file']['name'])) {
                echo json_encode(['success' => false, 'message' => 'Aucun fichier sélectionné.']);
                exit();
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement du fichier : ' . $file['error']]);
                exit();
            } elseif ($file['size'] > MAX_FILE_SIZE) {
                echo json_encode(['success' => false, 'message' => 'Le fichier dépasse la taille maximale de 5 Mo.']);
                exit();
            } elseif (!in_array($file['type'], ALLOWED_TYPES)) {
                echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Seuls PDF, JPEG et PNG sont acceptés.']);
                exit();
            } else {
                // Vérifier ou créer le dossier Uploads
                if (!file_exists(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0777, true);
                }
                if (!is_writable(UPLOAD_DIR)) {
                    echo json_encode(['success' => false, 'message' => 'Le dossier Uploads n\'est pas accessible en écriture.']);
                    exit();
                }

                $file_name = basename($file['name']);
                $file_path = UPLOAD_DIR . $file_name;

                // Gérer les doublons
                $i = 1;
                while (file_exists($file_path)) {
                    $file_name = pathinfo($file['name'], PATHINFO_FILENAME) . "_$i." . pathinfo($file['name'], PATHINFO_EXTENSION);
                    $file_path = UPLOAD_DIR . $file_name;
                    $i++;
                }

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO documents (user_id, title, category_id, file_path, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$user_id, $title, $category_id, $file_path]);
                    $new_id = $pdo->lastInsertId();

                    // Enregistrer un log
                    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, "Document ajouté", "Titre: $title"]);

                    // Récupérer le nouveau document pour mise à jour dynamique
                    $stmt = $pdo->prepare("
                        SELECT d.id, d.title, c.name AS category, d.file_path, d.created_at
                        FROM documents d
                        JOIN categories c ON d.category_id = c.category_id
                        WHERE d.id = ?
                    ");
                    $stmt->execute([$new_id]);
                    $new_document = $stmt->fetch(PDO::FETCH_ASSOC);

                    echo json_encode(['success' => true, 'message' => 'Document ajouté avec succès!', 'document' => $new_document]);
                    exit();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Échec du déplacement du fichier.']);
                    exit();
                }
            }
        }
    }

    // Gérer la suppression d'un document (admin uniquement)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $role === 'admin') {
        $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
        $stmt->execute([$doc_id]);
        $doc = $stmt->fetch();
        if ($doc) {
            unlink($doc['file_path']);
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$doc_id]);
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, "Document supprimé", "ID: $doc_id"]);
            echo json_encode(['success' => true, 'message' => 'Document supprimé avec succès!']);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Document non trouvé.']);
            exit();
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()]);
    exit();
}
?>
<div class="animate-slide-in">
    <h2 class="mb-6 text-3xl font-semibold text-white">Gestion des archives</h2>
    <div class="p-6 mb-8 bg-white rounded-lg shadow-md">
        <h3 class="flex items-center mb-4 text-lg font-medium text-black"><ion-icon name="cloud-upload-outline" class="mr-2"></ion-icon> Ajouter un document</h3>
        <form id="uploadForm" enctype="multipart/form-data" method="POST">
            <input type="hidden" name="upload" value="1">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Titre</label>
                <input type="text" name="title" class="w-full p-2 mt-1 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Catégorie</label>
                <select name="category_id" class="w-full p-2 mt-1 border rounded" required>
                    <option value="" disabled selected>Sélectionner une catégorie</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Fichier (PDF, JPEG, PNG, max 5MB)</label>
                <input type="file" name="file" class="w-full p-2 mt-1 border rounded" required>
            </div>
            <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Ajouter</button>
        </form>
    </div>
    <div class="p-6 bg-white rounded-lg shadow-md">
        <h3 class="flex items-center mb-4 text-lg font-medium text-black"><ion-icon name="folder-outline" class="mr-2"></ion-icon> Documents archivés</h3>
        <table id="documentsTable" class="w-full text-left">
            <thead>
                <tr class="text-black">
                    <th class="p-2">Titre</th>
                    <th class="p-2">Catégorie</th>
                    <th class="p-2">Date</th>
                    <th class="p-2">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                    <tr class="transition-colors border-t hover:bg-gray-50">
                        <td class="p-2 text-black"><?php echo htmlspecialchars($doc['title']); ?></td>
                        <td class="p-2 text-red-600"><?php echo htmlspecialchars($doc['category']); ?></td>
                        <td class="p-2 text-black"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></td>
                        <td class="p-2">
<a href="/projetAGC/includes/download.php?file=uploads/<?php echo urlencode(basename($doc['file_path'])); ?>" class="text-blue-600 hover:underline" target="_blank">
    <ion-icon name="download-outline"></ion-icon>
</a>
                            <?php if ($role === 'admin'): ?>
                                <form id="deleteForm_<?php echo $doc['id']; ?>" class="inline">
                                    <input type="hidden" name="delete" value="1">
                                    <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                    <button type="submit" class="ml-2 text-red-600 hover:underline"><ion-icon name="trash-outline"></ion-icon></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
