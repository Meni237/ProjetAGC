<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Accès interdit']));
}

$user_id = $_SESSION['user_id'];
$users = [];

try {
    // Gérer l'ajout d'un nouvel utilisateur
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email invalide.']);
            exit();
        }
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.']);
            exit();
        }
        if (!in_array($role, ['admin', 'user'])) {
            echo json_encode(['success' => false, 'message' => 'Rôle invalide.']);
            exit();
        }

        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé.']);
            exit();
        }

        // Hacher le mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insérer l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$email, $hashed_password, $role]);
        $new_user_id = $pdo->lastInsertId();

        // Récupérer les données de l'utilisateur ajouté pour mise à jour dynamique
        $stmt = $pdo->prepare("SELECT user_id, email, role, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$new_user_id]);
        $new_user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Enregistrer un log
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, "Utilisateur ajouté", "Email: $email, Rôle: $role"]);

        echo json_encode(['success' => true, 'message' => 'Utilisateur ajouté avec succès !', 'user' => $new_user]);
        exit();
    }

    // Gérer la modification du rôle d'un utilisateur
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
        $user_id_to_update = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
        $new_role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

        if (!in_array($new_role, ['admin', 'user'])) {
            echo json_encode(['success' => false, 'message' => 'Rôle invalide.']);
            exit();
        }

        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT email, role, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$user_id_to_update]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé.']);
            exit();
        }

        // Empêcher l'administrateur de modifier son propre rôle
        if ($user_id_to_update == $user_id) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas modifier votre propre rôle.']);
            exit();
        }

        // Mettre à jour le rôle
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$new_role, $user_id_to_update]);

        // Enregistrer un log
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, "Rôle modifié", "Email: {$user['email']}, Nouveau rôle: $new_role"]);

        echo json_encode(['success' => true, 'message' => 'Rôle mis à jour avec succès !', 'user' => [
            'user_id' => $user_id_to_update,
            'email' => $user['email'],
            'role' => $new_role,
            'created_at' => $user['created_at']
        ]]);
        exit();
    }

    // Récupérer la liste des utilisateurs
    $stmt = $pdo->query("SELECT user_id, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()]);
    exit();
}
?>
<div class="animate-slide-in">
    <h2 class="mb-6 text-3xl font-semibold text-black">Gestion des utilisateurs</h2>

    <!-- Formulaire pour ajouter un utilisateur -->
    <div class="p-6 mb-8 bg-white rounded-lg shadow-md">
        <h3 class="flex items-center mb-4 text-lg font-medium text-black"><ion-icon name="person-add-outline" class="mr-2"></ion-icon> Ajouter un utilisateur</h3>
        <form id="addUserForm" method="POST">
            <input type="hidden" name="add_user" value="1">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" class="w-full p-2 mt-1 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
                <input type="password" name="password" class="w-full p-2 mt-1 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Rôle</label>
                <select name="role" class="w-full p-2 mt-1 border rounded" required>
                    <option value="user">Utilisateur</option>
                    <option value="admin">Administrateur</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Ajouter</button>
        </form>
    </div>

    <!-- Tableau des utilisateurs -->
    <div class="p-6 bg-white rounded-lg shadow-md">
        <h3 class="flex items-center mb-4 text-lg font-medium text-black"><ion-icon name="people-outline" class="mr-2"></ion-icon> Liste des utilisateurs</h3>
        <table id="usersTable" class="w-full text-left">
            <thead>
                <tr class="text-black">
                    <th class="p-2">Email</th>
                    <th class="p-2">Rôle</th>
                    <th class="p-2">Date de création</th>
                    <th class="p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="transition-colors border-t hover:bg-gray-50">
                        <td class="p-2 text-black"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="p-2 text-black"><?php echo htmlspecialchars($user['role'] == 'admin' ? 'Administrateur' : 'Utilisateur'); ?></td>
                        <td class="p-2 text-black"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                        <td class="p-2">
                            <button class="text-blue-600 edit-role-btn hover:underline" data-user-id="<?php echo $user['user_id']; ?>" data-email="<?php echo htmlspecialchars($user['email']); ?>" data-role="<?php echo $user['role']; ?>">
                                <ion-icon name="create-outline"></ion-icon>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal pour modifier le rôle -->
<div id="editRoleModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 modal modal-hidden">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
        <h3 class="flex items-center mb-4 text-lg font-medium"><ion-icon name="create-outline" class="mr-2"></ion-icon> Modifier le rôle</h3>
        <form id="editRoleForm">
            <input type="hidden" name="update_role" value="1">
            <input type="hidden" name="user_id" id="editUserId">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="text" id="editEmail" class="w-full p-2 mt-1 border rounded" disabled>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Rôle</label>
                <select name="role" id="editRole" class="w-full p-2 mt-1 border rounded" required>
                    <option value="user">Utilisateur</option>
                    <option value="admin">Administrateur</option>
                </select>
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" id="closeEditRoleModalBtn" class="px-4 py-2 text-black bg-gray-300 rounded hover:bg-gray-400">Annuler</button>
                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
    function initializeUsersSection() {
        console.log('Initialisation de la section utilisateurs');

        // Initialiser DataTable
        if ($.fn.DataTable.isDataTable('#usersTable')) {
            $('#usersTable').DataTable().destroy();
        }
        $('#usersTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            pageLength: 10,
            // language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' }
        });

        // Gestion du formulaire d'ajout d'utilisateur
        const addUserForm = document.getElementById('addUserForm');
        if (addUserForm) {
            addUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Soumission du formulaire d\'ajout');
                const formData = new FormData(this);

                Swal.fire({
                    title: 'Chargement...',
                    text: 'Ajout de l\'utilisateur en cours.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('../includes/users_content.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Réponse fetch:', response);
                    return response.json();
                })
                .then(data => {
                    Swal.close();
                    console.log('Données reçues:', data);
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: data.message
                        }).then(() => {
                            // Ajouter l'utilisateur au tableau
                            const tbody = document.querySelector('#usersTable tbody');
                            const row = document.createElement('tr');
                            row.className = 'border-t hover:bg-gray-50 transition-colors';
                            row.innerHTML = `
                                <td class="p-2 text-black">${data.user.email}</td>
                                <td class="p-2 text-black">${data.user.role == 'admin' ? 'Administrateur' : 'Utilisateur'}</td>
                                <td class="p-2 text-black">${new Date(data.user.created_at).toLocaleDateString('fr-FR')}</td>
                                <td class="p-2">
                                    <button class="text-blue-600 edit-role-btn hover:underline" data-user-id="${data.user.user_id}" data-email="${data.user.email}" data-role="${data.user.role}">
                                        <ion-icon name="create-outline"></ion-icon>
                                    </button>
                                </td>
                            `;
                            tbody.insertBefore(row, tbody.firstChild);
                            // Réinitialiser le formulaire
                            this.reset();
                            // Ajouter l'événement pour le nouveau bouton
                            row.querySelector('.edit-role-btn').addEventListener('click', openEditRoleModal);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Erreur fetch:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Une erreur est survenue. Veuillez réessayer.'
                    });
                });
            });
        }

        // Fonction pour ouvrir le modal de modification du rôle
        function openEditRoleModal(e) {
            console.log('Clic sur edit-role-btn');
            const button = e.target.closest('.edit-role-btn');
            if (!button) {
                console.error('Bouton edit-role-btn non trouvé');
                return;
            }
            const userId = button.dataset.userId;
            const email = button.dataset.email;
            const role = button.dataset.role;

            console.log('Ouverture modal pour userId:', userId, 'email:', email, 'role:', role);

            document.getElementById('editUserId').value = userId;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRole').value = role;

            const modal = document.getElementById('editRoleModal');
            modal.classList.remove('modal-hidden');
            modal.classList.add('modal-visible');
        }

        // Attacher les écouteurs d'événements aux boutons de modification
        document.querySelectorAll('.edit-role-btn').forEach(button => {
            button.removeEventListener('click', openEditRoleModal); // Éviter les doublons
            button.addEventListener('click', openEditRoleModal);
            console.log('Écouteur ajouté pour bouton:', button.dataset.userId);
        });

        // Fermer le modal de modification
        const closeEditRoleModalBtn = document.getElementById('closeEditRoleModalBtn');
        if (closeEditRoleModalBtn) {
            closeEditRoleModalBtn.addEventListener('click', () => {
                console.log('Fermeture du modal');
                const modal = document.getElementById('editRoleModal');
                modal.classList.remove('modal-visible');
                modal.classList.add('modal-hidden');
            });
        }

        // Gestion du formulaire de modification du rôle
        const editRoleForm = document.getElementById('editRoleForm');
        if (editRoleForm) {
            editRoleForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Soumission du formulaire de modification');
                const formData = new FormData(this);

                Swal.fire({
                    title: 'Chargement...',
                    text: 'Mise à jour du rôle en cours.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('../includes/users_content.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Réponse fetch modification:', response);
                    return response.json();
                })
                .then(data => {
                    Swal.close();
                    console.log('Données reçues modification:', data);
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: data.message
                        }).then(() => {
                            // Mettre à jour la ligne dans le tableau
                            const row = document.querySelector(`tr td button[data-user-id="${data.user.user_id}"]`).closest('tr');
                            row.cells[1].textContent = data.user.role == 'admin' ? 'Administrateur' : 'Utilisateur';
                            row.cells[3].querySelector('.edit-role-btn').dataset.role = data.user.role;

                            // Fermer le modal
                            const modal = document.getElementById('editRoleModal');
                            modal.classList.remove('modal-visible');
                            modal.classList.add('modal-hidden');
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Erreur fetch modification:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Une erreur est survenue. Veuillez réessayer.'
                    });
                });
            });
        }
    }

    // Exécuter l'initialisation
    initializeUsersSection();
</script>
