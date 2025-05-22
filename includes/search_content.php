<?php
session_start();
require_once "config.php";

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$search_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $query = filter_input(INPUT_POST, 'query', FILTER_SANITIZE_STRING);
    
    try {
        // Rechercher parmi tous les documents (sans filtre user_id)
        $stmt = $pdo->prepare("SELECT title, created_at, category, file_path FROM documents 
                               WHERE title LIKE ? OR category LIKE ?");
        $stmt->execute(["%$query%", "%$query%"]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enregistrer la recherche dans la table search
        $stmt = $pdo->prepare("INSERT INTO search (user_id, query, results_count, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $query, count($search_results)]);
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
}
?>
<div class="animate-slide-in">
    <h2 class="text-3xl font-semibold text-black mb-6">Recherche</h2>
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h3 class="text-lg font-medium text-black mb-4 flex items-center"><ion-icon name="search-outline" class="mr-2"></ion-icon> Rechercher des documents</h3>
        <form id="searchForm">
            <div class="mb-4">
                <input type="text" name="query" class="p-2 w-full border rounded" placeholder="Rechercher par titre ou catégorie..." required>
            </div>
            <button type="submit" name="search" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Rechercher</button>
        </form>
    </div>
    <?php if (!empty($search_results)): ?>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-medium text-black mb-4 flex items-center"><ion-icon name="documents-outline" class="mr-2"></ion-icon> Résultats</h3>
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
                    <?php foreach ($search_results as $doc): ?>
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
    <?php endif; ?>
</div>
<script>
    document.getElementById('searchForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('search', '1');
        fetch('../includes/search_content.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(data => {
                document.getElementById('mainContent').innerHTML = data;
                initializeDataTable();
            });
    });
</script>