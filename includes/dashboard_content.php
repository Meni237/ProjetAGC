<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Utilisateur non connecté']));
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

// Gérer l'ajout d'une nouvelle catégorie (admin uniquement)
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_category'])) {
    $new_category = filter_input(INPUT_POST, 'new_category', FILTER_SANITIZE_STRING);
    
    if (!empty($new_category)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$new_category]);
            echo json_encode(['success' => true, 'message' => 'Catégorie ajoutée avec succès.']);
            exit();
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de la catégorie : ' . $e->getMessage()]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Le nom de la catégorie ne peut pas être vide.']);
        exit();
    }
}

// Gérer la suppression d'une catégorie (admin uniquement)
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    $category_id = filter_input(INPUT_POST, 'delete_category_id', FILTER_VALIDATE_INT);
    
    if ($category_id) {
        try {
            // Vérifier si des documents sont associés à cette catégorie
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM documents WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $doc_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($doc_count > 0) {
                echo json_encode(['success' => false, 'message' => 'Impossible de supprimer : des documents sont associés à cette catégorie']);
                exit();
            }

            // Supprimer la catégorie
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->execute([$category_id]);
            
            echo json_encode(['success' => true, 'message' => 'Catégorie supprimée avec succès']);
            exit();
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de catégorie invalide']);
        exit();
    }
}

$totalDocuments = 0;
$recentDocuments = [];
$labels = [];
$data_added = [];
$categoriesData = [];

try {
    // Compter tous les documents (sans filtre user_id)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM documents");
    $totalDocuments = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $stmt1 = $pdo->query("SELECT COUNT(*) as totaluser FROM users");
    $totalUsers = $stmt1->fetch(PDO::FETCH_ASSOC)['totaluser'] ?? 0;

    // Récupérer les derniers documents avec le nom de la catégorie
    $stmt = $pdo->query("
        SELECT d.title, d.created_at, c.name AS category, d.file_path
        FROM documents d
        JOIN categories c ON d.category_id = c.category_id
        ORDER BY d.created_at DESC
        LIMIT 10
    ");
    $recentDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Activité ce mois (sans filtre user_id)
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM documents 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $activityData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($activityData as $row) {
        $labels[] = date('d M', strtotime($row['date']));
        $data_added[] = $row['count'];
    }

    // Nombre de documents par catégorie
    $stmt = $pdo->query("
        SELECT c.category_id, c.name AS category, COUNT(d.id) AS count
        FROM categories c
        LEFT JOIN documents d ON c.category_id = d.category_id
        GROUP BY c.category_id, c.name
    ");
    $categoriesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="p-6 animate-slide-in">
        <h2 class="mb-6 text-3xl font-semibold text-gray-900 dark:text-white">Tableau de bord</h2>

        <!-- Cartes pour le total, documents récents et activité -->
        <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-4">
            <!-- Total des documents -->
            <div class="p-6 text-indigo-600 transition-transform duration-300 transform bg-indigo-100 border border-indigo-300 rounded-lg shadow-md dark:bg-indigo-800 dark:border-indigo-600 dark:text-indigo-200 hover:scale-105">
                <h3 class="flex items-center text-lg font-medium"><ion-icon name="document-outline" class="mr-2"></ion-icon> Total des documents</h3>
                <p class="text-3xl font-bold"><?php echo $totalDocuments; ?></p>
            </div>

            <!-- Documents récents -->
            <div class="p-6 transition-transform duration-300 transform border rounded-lg shadow-md bg-emerald-100 dark:bg-emerald-800 border-emerald-300 dark:border-emerald-600 text-emerald-600 dark:text-emerald-200 hover:scale-105">
                <h3 class="flex items-center text-lg font-medium"><ion-icon name="time-outline" class="mr-2"></ion-icon> Documents récents</h3>
                <p class="text-3xl font-bold"><?php echo count($recentDocuments); ?></p>
            </div>

            <!-- Activité ce mois -->
            <div class="p-6 transition-transform duration-300 transform border rounded-lg shadow-md bg-rose-100 dark:bg-rose-800 border-rose-300 dark:border-rose-600 text-rose-600 dark:text-rose-200 hover:scale-105">
                <h3 class="flex items-center text-lg font-medium"><ion-icon name="pulse-outline" class="mr-2"></ion-icon> Activité ce mois</h3>
                <p class="text-3xl font-bold"><?php echo array_sum($data_added); ?></p>
            </div>

            <!-- Nombre d'utilisateurs -->
            <div class="p-6 text-yellow-600 transition-transform duration-300 transform bg-yellow-100 border border-yellow-300 rounded-lg shadow-md dark:bg-yellow-800 dark:border-yellow-600 dark:text-yellow-200 hover:scale-105">
                <h3 class="flex items-center text-lg font-medium"><ion-icon name="person-outline" class="mr-2"></ion-icon> Nombre d'utilisateurs</h3>
                <p class="text-3xl font-bold"><?php echo $totalUsers; ?></p>
            </div>
        </div>

        <!-- Cartes pour les documents par catégorie -->
        <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($categoriesData as $category): ?>
                <?php
                    $textColor = 'text-gray-800';
                    $borderColor = 'border-gray-300';
                    switch (strtolower($category['category'])) {
                        case 'factures':
                            $textColor = 'text-red-600';
                            $borderColor = 'border-red-300';
                            break;
                        case 'rapports':
                            $textColor = 'text-blue-600';
                            $borderColor = 'border-blue-300';
                            break;
                        case 'contrat':
                            $textColor = 'text-green-600';
                            $borderColor = 'border-green-300';
                            break;
                    }
                ?>
                <div class="p-6 transition-transform duration-300 transform bg-white border rounded-lg shadow-md hover:scale-105 <?php echo $borderColor; ?>">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium <?php echo $textColor; ?>">
                                <?php echo htmlspecialchars($category['category']); ?>
                            </h3>
                            <p class="text-2xl font-bold <?php echo $textColor; ?>">
                                <?php echo $category['count']; ?> documents
                            </p>
                        </div>
                        <?php if ($role === 'admin' && $category['count'] == 0): ?>
                            <button 
                                class="text-red-600 delete-category-btn hover:text-red-800"
                                data-category-id="<?php echo $category['category_id']; ?>"
                                title="Supprimer la catégorie"
                            >
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bouton pour ajouter une catégorie (admin uniquement) -->
        <?php if ($role === 'admin'): ?>
            <div class="mb-6">
                <button id="addCategoryBtn" class="flex items-center px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">
                    <ion-icon name="add-circle-outline" class="mr-2"></ion-icon> Ajouter une catégorie
                </button>
            </div>
        <?php endif; ?>

        <!-- Diagramme en bandes pour les documents par catégorie -->
        <div class="p-6 mb-8 bg-white rounded-lg shadow-md">
            <h3 class="flex items-center mb-4 text-lg font-medium text-black"><ion-icon name="bar-chart-outline" class="mr-2"></ion-icon> Documents par catégorie</h3>
            <canvas id="categoryChart" height="100" data-categories='<?php echo json_encode(array_column($categoriesData, 'category')); ?>' data-counts='<?php echo json_encode(array_column($categoriesData, 'count')); ?>'></canvas>
        </div>

        <!-- Tableau des derniers documents -->
       <div class="p-6 bg-white rounded-lg shadow-md">
    <div class="flex items-center justify-between mb-4">
        <h3 class="flex items-center text-lg font-medium text-black"><ion-icon name="documents-outline" class="mr-2"></ion-icon> Derniers documents</h3>
        <a href="#" data-section="archive" class="flex items-center px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700"><ion-icon name="add-circle-outline" class="mr-2"></ion-icon> Ajouter un document</a>
    </div>
    <table id="documentsTable" class="w-full text-left">
        <thead>
            <tr class="text-black">
                <th class="p-2">Titre</th>
                <th class="p-2">Date</th>
                <th class="p-2">Catégorie</th>
                <th class="p-2">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentDocuments as $doc): ?>
                <tr class="transition-colors border-t hover:bg-gray-50">
                    <td class="p-2 text-black"><?php echo htmlspecialchars($doc['title']); ?></td>
                    <td class="p-2 text-black"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></td>
                    <td class="p-2 text-red-600"><?php echo htmlspecialchars($doc['category']); ?></td>
                    <td class="p-2"><a href="/projetAGC/includes/download.php?file=uploads/<?php echo urlencode(basename($doc['file_path'])); ?>" class="text-blue-600 hover:underline" target="_blank"><ion-icon name="download-outline"></ion-icon></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

   
</body>
</html>