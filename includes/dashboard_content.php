<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    die("Erreur : Utilisateur non connecté");
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user'; // Récupérer le rôle de l'utilisateur

$totalDocuments = 0;
$recentDocuments = [];
$labels = [];
$data_added = [];

try {
    // Compter tous les documents (sans filtre user_id)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM documents");
    $totalDocuments = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Récupérer les derniers documents (sans filtre user_id)
    $stmt = $pdo->query("SELECT title, created_at, category, file_path FROM documents ORDER BY created_at DESC LIMIT 10");
    $recentDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Activité ce mois (sans filtre user_id)
    $stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                         FROM documents 
                         WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
                         GROUP BY DATE(created_at)
                         ORDER BY date ASC");
    $activityData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($activityData as $row) {
        $labels[] = date('d M', strtotime($row['date']));
        $data_added[] = $row['count'];
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
<div class="animate-slide-in">
    <h2 class="text-3xl font-semibold text-black mb-6">Tableau de bord</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-blue-200 text-blue-400 p-6 rounded-lg shadow-md transform hover:scale-105 transition-transform duration-300 border border-blue-400">
            <h3 class="text-lg font-medium flex items-center"><ion-icon name="document-outline" class="mr-2"></ion-icon> Total des documents</h3>
            <p class="text-3xl font-bold"><?php echo $totalDocuments; ?></p>
        </div>
        <div class="bg-green-200 text-green-400 p-6 rounded-lg shadow-md transform hover:scale-105 transition-transform duration-300 border border-green-400">
            <h3 class="text-lg font-medium flex items-center"><ion-icon name="time-outline" class="mr-2"></ion-icon> Documents récents</h3>
            <p class="text-3xl font-bold"><?php echo count($recentDocuments); ?></p>
        </div>
        <div class="bg-red-200 text-red-400 p-6 rounded-lg shadow-md transform hover:scale-105 transition-transform duration-300 border border-red-400">
            <h3 class="text-lg font-medium flex items-center"><ion-icon name="pulse-outline" class="mr-2"></ion-icon> Activité ce mois</h3>
            <p class="text-3xl font-bold"><?php echo array_sum($data_added); ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h3 class="text-lg font-medium text-black mb-4 flex items-center"><ion-icon name="bar-chart-outline" class="mr-2"></ion-icon> Activité des documents ce mois</h3>
        <canvas id="activityChart" height="100" data-labels='<?php echo json_encode($labels); ?>' data-data='<?php echo json_encode($data_added); ?>'></canvas>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-black flex items-center"><ion-icon name="documents-outline" class="mr-2"></ion-icon> Derniers documents</h3>
            <a href="#" data-section="archives" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center"><ion-icon name="add-circle-outline" class="mr-2"></ion-icon> Ajouter un document</a>
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
                    <tr class="border-t hover:bg-gray-50 transition-colors">
                        <td class="p-2 text-black"><?php echo htmlspecialchars($doc['title']); ?></td>
                        <td class="p-2 text-black"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></td>
                        <td class="p-2 text-red-600"><?php echo htmlspecialchars($doc['category']); ?></td>
                        <td class="p-2"><a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="text-blue-600 hover:underline" target="_blank"><ion-icon name="download-outline"></ion-icon></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>