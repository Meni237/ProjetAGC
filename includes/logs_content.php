<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Accès interdit");
}

$logs = [];
try {
    $stmt = $pdo->query("SELECT l.id, l.user_id, l.action, l.details, l.created_at, u.email 
                         FROM logs l 
                         JOIN users u ON l.user_id = u.user_id 
                         ORDER BY l.created_at DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
<div class="animate-slide-in">
    <h2 class="mb-6 text-3xl font-semibold text-white">Journal des actions</h2>
    <div class="p-6 bg-white rounded-lg shadow-md">
        <h3 class="flex items-center mb-4 text-lg font-medium text-black"><ion-icon name="document-text-outline" class="mr-2"></ion-icon> Logs</h3>
        <table id="logsTable" class="w-full text-left">
            <thead>
                <tr class="text-black">
                    <th class="p-2">Utilisateur</th>
                    <th class="p-2">Action</th>
                    <th class="p-2">Détails</th>
                    <th class="p-2">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr class="transition-colors border-t hover:bg-gray-50">
                        <td class="p-2 text-black"><?php echo htmlspecialchars($log['email']); ?></td>
                        <td class="p-2 text-black"><?php echo htmlspecialchars($log['action']); ?></td>
                        <td class="p-2 text-black"><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                        <td class="p-2 text-black"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>