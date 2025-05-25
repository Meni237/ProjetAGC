<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGC Archiv' Secure - Tableau de bord</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
        }
        .wave {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 200px;
            background: #3b82f6;
            border-bottom-left-radius: 50% 100px;
            border-bottom-right-radius: 50% 100px;
            z-index: 0;
            overflow: hidden;
        }
        .animate-slide-in {
            animation: slideIn 0.5s ease-in-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .modal-hidden {
            opacity: 0;
            transform: translateY(-100px);
            pointer-events: none;
        }
        .modal-visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .nav-link.active {
            background-color: #2563eb;
        }
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 20;
            background: #000;
        }
        aside {
            position: fixed;
            top: 0;
            bottom: 0;
            width: 16rem;
            z-index: 10;
            background: #000;
            overflow-y: auto;
        }
        main {
            margin-left: 16rem;
            margin-top: 4rem;
            padding: 2rem;
            overflow-y: auto;
            height: calc(100vh - 4rem);
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100">
    <?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../dist/index.php");
        exit();
    }

    require_once "../includes/config.php";

    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT email, role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = $user['email'] ?? 'email@example.com';
    $role = $user['role'] ?? 'user';
    ?>

    <!-- Fond avec vagues -->
    <div class="wave"></div>

    <!-- Image de fond avec superposition -->
    <div class="absolute inset-0 bg-center bg-cover" style="background-image: url('../image/image.webp'); opacity: 0.1;"></div>

    <div class="relative z-10">
        <!-- Navbar -->
        <header class="flex items-center justify-between p-4 text-white bg-black shadow-md">
            <h1 class="text-2xl font-bold text-blue-400">AGC Archiv' Secure</h1>
            <div class="relative">
                <button id="profileBtn" class="flex items-center p-2 space-x-2 rounded hover:bg-blue-700">
                    <ion-icon name="person-circle-outline" class="text-2xl"></ion-icon>
                    <span><?php echo htmlspecialchars($email); ?></span>
                    <ion-icon name="chevron-down-outline" class="text-xl"></ion-icon>
                </button>
                <div id="profileDropdown" class="absolute right-0 hidden w-48 mt-2 text-black bg-white rounded-lg shadow-lg animate-slide-in">
                    <a href="#" id="editProfileBtn" class="flex items-center block px-4 py-2 hover:bg-gray-100"><ion-icon name="create-outline" class="mr-2"></ion-icon> Modifier le profil</a>
            </div>
        </header>

        <div class="flex">
            <!-- Sidebar -->
            <aside class="w-64 text-white bg-black shadow-md">
                <nav class="mt-24">
                    <ul>
                        <li><a href="#" data-section="dashboard" class="flex items-center px-4 py-2 text-white bg-blue-600 nav-link hover:bg-blue-700"><ion-icon name="home-outline" class="mr-2"></ion-icon> Tableau de bord</a></li>
                        <li><a href="#" data-section="archive" class="flex items-center px-4 py-2 text-white nav-link hover:bg-blue-700"><ion-icon name="folder-outline" class="mr-2"></ion-icon> Gestion des archives</a></li>
                        <li><a href="#" data-section="search" class="flex items-center px-4 py-2 text-white nav-link hover:bg-blue-700"><ion-icon name="search-outline" class="mr-2"></ion-icon> Recherche</a></li>
                        <?php if ($role === 'admin'): ?>
                            <li><a href="#" data-section="users" class="flex items-center px-4 py-2 text-white nav-link hover:bg-blue-700"><ion-icon name="people-outline" class="mr-2"></ion-icon> Gestion des utilisateurs</a></li>
                            <li><a href="#" data-section="logs" class="flex items-center px-4 py-2 text-white nav-link hover:bg-blue-700"><ion-icon name="document-text-outline" class="mr-2"></ion-icon> Journal des actions</a></li>
                        <?php endif; ?>
<a href="../dist/logout.php" id="logoutBtn" class="flex items-center block px-4 py-2 text-red-600 hover:bg-gray-100"><ion-icon name="log-out-outline" class="mr-2"></ion-icon> Déconnexion</a>                    </ul>
                </nav>
            </aside>

            <!-- Main Content -->
            <main id="mainContent" class="flex-1 overflow-auto">
                <!-- Content will be loaded dynamically here -->
            </main>
        </div>

        <!-- Profile Edit Modal -->
        <div id="profileModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 modal modal-hidden">
            <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
                <h3 class="flex items-center mb-4 text-lg font-medium"><ion-icon name="create-outline" class="mr-2"></ion-icon> Modifier le profil</h3>
                <form id="profileForm">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full p-2 mt-1 border rounded" required>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="closeModalBtn" class="px-4 py-2 text-black bg-gray-300 rounded hover:bg-gray-400">Annuler</button>
                        <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script>
 function handleFormSubmission(form, phpFile, successCallback) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        Swal.fire({
            title: 'Chargement...',
            text: 'Opération en cours.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(phpFile, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: data.message
                }).then(() => {
                    successCallback();
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
            console.error('Erreur AJAX:', error);
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Une erreur est survenue. Veuillez réessayer.'
            });
        });
    });
}

function handleDeleteAction(deleteId, fieldName, phpFile, successCallback) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: 'Voulez-vous vraiment supprimer cet élément ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append(fieldName, deleteId);

            Swal.fire({
                title: 'Chargement...',
                text: 'Suppression en cours.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(phpFile, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: data.message
                    }).then(() => {
                        successCallback();
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
                console.error('Erreur AJAX:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Une erreur est survenue. Veuillez réessayer.'
                });
            });
        }
    });
}

function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#documentsTable') || $.fn.DataTable.isDataTable('#usersTable') || $.fn.DataTable.isDataTable('#logsTable')) {
        $('#documentsTable, #usersTable, #logsTable').DataTable().destroy();
    }
    $('#documentsTable, #usersTable, #logsTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        pageLength: 5,
        // language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' }
    });
}

function initializeDashboard() {
    const activityChartCanvas = document.getElementById('activityChart')?.getContext('2d');
    if (activityChartCanvas) {
        new Chart(activityChartCanvas, {
            type: 'line',
            data: {
                labels: JSON.parse(document.getElementById('activityChart').dataset.labels || '[]'),
                datasets: [{
                    label: 'Documents ajoutés',
                    data: JSON.parse(document.getElementById('activityChart').dataset.data || '[]'),
                    borderColor: '#1e40af',
                    backgroundColor: 'rgba(30, 64, 175, 0.1)',
                    pointBackgroundColor: '#dc2626',
                    pointBorderColor: '#dc2626',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Nombre de documents' } },
                    x: { title: { display: true, text: 'Date' } }
                },
                plugins: {
                    legend: { display: true },
                    tooltip: { mode: 'index', intersect: false }
                }
            }
        });
    }

    const categoryChartCanvas = document.getElementById('categoryChart')?.getContext('2d');
    if (categoryChartCanvas) {
        new Chart(categoryChartCanvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(document.getElementById('categoryChart').dataset.categories || '[]'),
                datasets: [{
                    label: 'Nombre de documents',
                    data: JSON.parse(document.getElementById('categoryChart').dataset.counts || '[]'),
                    backgroundColor: 'rgba(30, 64, 175, 0.6)',
                    borderColor: '#1e40af',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Nombre de documents' } },
                    x: { title: { display: true, text: 'Catégorie' } }
                },
                plugins: {
                    legend: { display: true },
                    tooltip: { mode: 'index', intersect: false }
                }
            }
        });
    }
}

function initializeArchiveJS() {
    console.log('Initialisation de la section archive');
    const isAdmin = <?php echo json_encode(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'); ?>;
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        handleFormSubmission(uploadForm, '../includes/archive_content.php', () => {
            uploadForm.reset();
            loadSection('archive');
        });
    }

    document.querySelectorAll('[id^="deleteForm_"]').forEach(form => {
        handleFormSubmission(form, '../includes/archive_content.php', () => {
            form.closest('tr').remove();
        });
    });
}

function initializeUsersSection() {
    const isAdmin = <?php echo json_encode(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'); ?>;
    if (!isAdmin) {
        console.log('Accès non autorisé : utilisateur non administrateur');
        return;
    }

    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        handleFormSubmission(addUserForm, '../includes/users_content.php', () => {
            addUserForm.reset();
            loadSection('users');
        });
    } else {
        console.log('Formulaire addUserForm non trouvé');
    }

    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.addEventListener('click', function(e) {
            const button = e.target.closest('.edit-role-btn');
            if (button) {
                console.log('Clic sur edit-role-btn:', button.dataset);
                const userId = button.dataset.userId;
                const email = button.dataset.email;
                const role = button.dataset.role;

                const editUserId = document.getElementById('editUserId');
                const editEmail = document.getElementById('editEmail');
                const editRole = document.getElementById('editRole');
                if (editUserId && editEmail && editRole) {
                    editUserId.value = userId;
                    editEmail.value = email;
                    editRole.value = role;

                    const modal = document.getElementById('editRoleModal');
                    if (modal) {
                        modal.classList.remove('modal-hidden');
                        modal.classList.add('modal-visible');
                    }
                }
            }
        });
    }

    const closeEditRoleModalBtn = document.getElementById('closeEditRoleModalBtn');
    if (closeEditRoleModalBtn) {
        closeEditRoleModalBtn.addEventListener('click', () => {
            console.log('Clic sur fermer modal');
            const modal = document.getElementById('editRoleModal');
            if (modal) {
                modal.classList.remove('modal-visible');
                modal.classList.add('modal-hidden');
            }
        });
    }

    const editRoleForm = document.getElementById('editRoleForm');
    if (editRoleForm) {
        handleFormSubmission(editRoleForm, '../includes/users_content.php', () => {
            const modal = document.getElementById('editRoleModal');
            if (modal) {
                modal.classList.remove('modal-visible');
                modal.classList.add('modal-hidden');
            }
            loadSection('users');
        });
    } else {
        console.log('Formulaire editRoleForm non trouvé');
    }
}

function initializeCategories() {
    console.log('Initialisation de la gestion des catégories');

    const isAdmin = <?php echo json_encode(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'); ?>;
    if (!isAdmin) {
        console.log('Accès non autorisé : utilisateur non administrateur');
        return;
    }

    const addCategoryBtn = document.getElementById('addCategoryBtn');
    if (addCategoryBtn) {
        console.log('Bouton addCategoryBtn trouvé');
        addCategoryBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Ajouter une nouvelle catégorie',
                input: 'text',
                inputLabel: 'Nom de la catégorie',
                inputPlaceholder: 'Entrez le nom de la catégorie',
                showCancelButton: true,
                confirmButtonText: 'Ajouter',
                cancelButtonText: 'Annuler',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Le nom de la catégorie ne peut pas être vide !';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('new_category', result.value);

                    Swal.fire({
                        title: 'Chargement...',
                        text: 'Ajout de la catégorie en cours.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('../includes/dashboard_content.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Statut de la réponse:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Succès',
                                text: data.message
                            }).then(() => {
                                loadSection('dashboard');
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
                        console.error('Erreur AJAX:', error);
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Une erreur est survenue. Veuillez réessayer.'
                        });
                    });
                }
            });
        });
    } else {
        console.log('Bouton addCategoryBtn non trouvé');
    }

    document.querySelectorAll('.delete-category-btn').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            handleDeleteAction(categoryId, 'delete_category_id', '../includes/dashboard_content.php', () => loadSection('dashboard'));
        });
    });
}

function loadSection(section) {
    fetch(`../includes/${section}_content.php`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('mainContent').innerHTML = data;
            if (section === 'dashboard') {
                initializeDashboard();
                initializeCategories();
            } else if (section === 'archive' || section === 'users' || section === 'logs') {
                initializeDataTable();
                if (section === 'archive') {
                    initializeArchiveJS();
                } else if (section === 'users') {
                    initializeUsersSection();
                }
            }
        })
        .catch(error => console.error('Erreur chargement section:', error));

    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.dataset.section === section) {
            link.classList.add('active');
        }
    });
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.dataset.section;
            loadSection(section);
        });
    });
});
document.getElementById('profileBtn').addEventListener('click', () => {
    document.getElementById('profileDropdown').classList.toggle('hidden');
});

const profileModal = document.getElementById('profileModal');
document.getElementById('editProfileBtn').addEventListener('click', () => {
    profileModal.classList.remove('modal-hidden');
    profileModal.classList.add('modal-visible');
});
document.getElementById('closeModalBtn').addEventListener('click', () => {
    profileModal.classList.remove('modal-visible');
    profileModal.classList.add('modal-hidden');
});

document.getElementById('profileForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    Swal.fire({
        title: 'Chargement...',
        text: 'Mise à jour du profil en cours.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('../includes/update_Profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Statut de la réponse:', response.status);
        return response.json();
    })
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: data.message || 'Profil mis à jour avec succès.'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: data.message || 'Erreur lors de la mise à jour du profil.'
            });
        }
    })
    .catch(error => {
        console.error('Erreur AJAX:', error);
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Une erreur est survenue. Veuillez réessayer.'
        });
    });
});

document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: 'Vous allez être déconnecté.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, déconnecter',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../dist/logout.php';
        }
    });
});


document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire de recherche
    const searchForm = document.getElementById('searchForm');
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('search', '1');

        // Validation côté client
        const query = formData.get('query');
        const category = formData.get('category');
        const dateFrom = formData.get('date_from');
        const dateTo = formData.get('date_to');
        if (!query && !category && !dateFrom && !dateTo) {
            Swal.fire({
                icon: 'warning',
                title: 'Erreur',
                text: 'Veuillez entrer au moins un critère de recherche.'
            });
            return;
        }

        Swal.fire({
            title: 'Recherche en cours...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('../includes/search_content.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}`);
            }
            return response.text();
        })
        .then(data => {
            Swal.close();
            document.getElementById('mainContent').innerHTML = data;
            initializeDataTables();
            // Vérifier si aucun résultat n'est trouvé
            if (!document.querySelector('#documentsTable tbody tr') &&
                !document.querySelector('#usersTable tbody tr') &&
                !document.querySelector('#logsTable tbody tr')) {
                Swal.fire({
                    icon: 'info',
                    title: 'Aucun résultat',
                    text: 'Aucun résultat trouvé pour votre recherche.'
                });
            }
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Une erreur s\'est produite lors de la recherche. Veuillez réessayer.'
            });
        });
    });

    // Initialiser DataTables pour chaque table
    function initializeDataTables() {
        const tables = [
            { id: 'documentsTable', columns: [0, 1, 2, 3] },
            { id: 'usersTable', columns: [0, 1] },
            { id: 'logsTable', columns: [0, 1, 2, 3] }
        ];

        tables.forEach(table => {
            const tableElement = document.getElementById(table.id);
            if (tableElement && tableElement.querySelector('tbody')) {
                if ($.fn.DataTable.isDataTable(`#${table.id}`)) {
                    $(`#${table.id}`).DataTable().destroy();
                }
                $(`#${table.id}`).DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    pageLength: 10,
                    order: [[0, 'asc']],
                    columns: table.columns.map(col => ({ searchable: true, orderable: true })),
                    language: {
                       // url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
                    }
                });
            }
        });
    }

    // Initialiser DataTables si des résultats sont déjà présents
    initializeDataTables();
});
loadSection('dashboard');
</script>

</body>
</html>