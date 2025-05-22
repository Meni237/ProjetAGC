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

try {
    // Récupérer tous les documents
    $stmt = $pdo->query("SELECT id, title, category, file_path, created_at FROM documents ORDER BY created_at DESC");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
        if ($role === 'admin' || $role === 'user') {
            $title = filter_input(INPUT_POST, 'title', FILTER_DEFAULT);
            $category = filter_input(INPUT_POST, 'category', FILTER_DEFAULT);

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

                $file_name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($file['name']));
                $file_path = UPLOAD_DIR . $file_name;

                // Gérer les doublons
                $i = 1;
                while (file_exists($file_path)) {
                    $file_name = pathinfo($file['name'], PATHINFO_FILENAME) . "_$i." . pathinfo($file['name'], PATHINFO_EXTENSION);
                    $file_path = UPLOAD_DIR . $file_name;
                    $i++;
                }

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $stmt = $pdo->prepare("INSERT INTO documents (user_id, title, category, file_path, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $title, $category, $file_path]);
                    $new_id = $pdo->lastInsertId();

                    // Enregistrer un log
                    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, "Document ajouté", "Titre: $title"]);

                    // Récupérer le nouveau document pour mise à jour dynamique
                    $stmt = $pdo->prepare("SELECT id, title, category, file_path, created_at FROM documents WHERE id = ?");
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $role === 'admin') {
        $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_DEFAULT);
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
    <h2 class="text-3xl font-semibold text-black mb-6">Gestion des archives</h2>
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h3 class="text-lg font-medium text-black mb-4 flex items-center"><ion-icon name="cloud-upload-outline" class="mr-2"></ion-icon> Ajouter un document</h3>
        <form id="uploadForm" enctype="multipart/form-data" method="POST">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Titre</label>
                <input type="text" name="title" class="mt-1 p-2 w-full border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Catégorie</label>
                <input type="text" name="category" class="mt-1 p-2 w-full border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Fichier (PDF, JPEG, PNG, max 5MB)</label>
                <input type="file" name="file" class="mt-1 p-2 w-full border rounded" required>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" >Ajouter</button>
        </form>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-medium text-black mb-4 flex items-center"><ion-icon name="folder-outline" class="mr-2"></ion-icon> Documents archivés</h3>
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
                    <tr class="border-t hover:bg-gray-50 transition-colors">
                        <td class="p-2 text-black"><?php echo htmlspecialchars($doc['title']); ?></td>
                        <td class="p-2 text-red-600"><?php echo htmlspecialchars($doc['category']); ?></td>
                        <td class="p-2 text-black"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></td>
                        <td class="p-2">
                            <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="text-blue-600 hover:underline" target="_blank"><ion-icon name="download-outline"></ion-icon></a>
                            <?php if ($role === 'admin'): ?>
                                <form id="deleteForm_<?php echo $doc['id']; ?>" class="inline">
                                    <input type="hidden" name="delete" value="1">
                                    <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:underline ml-2"><ion-icon name="trash-outline"></ion-icon></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
