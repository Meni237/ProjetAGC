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
            background-color: #2563eb; /* Blue */
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
    <div class="flex flex-col h-screen">
        <!-- Navbar -->
        <header class="bg-black text-white p-4 flex justify-between items-center shadow-md">
            <h1 class="text-2xl font-bold text-blue-400">AGC Archiv' Secure</h1>
            <div class="relative">
                <button id="profileBtn" class="flex items-center space-x-2 hover:bg-blue-700 p-2 rounded">
                    <ion-icon name="person-circle-outline" class="text-2xl"></ion-icon>
                    <span><?php echo htmlspecialchars($email); ?></span>
                    <ion-icon name="chevron-down-outline" class="text-xl"></ion-icon>
                </button>
                <div id="profileDropdown" class="absolute right-0 mt-2 w-48 bg-white text-black rounded-lg shadow-lg hidden animate-slide-in">
                    <a href="#" id="editProfileBtn" class="block px-4 py-2 hover:bg-gray-100 flex items-center"><ion-icon name="create-outline" class="mr-2"></ion-icon> Modifier le profil</a>
                    <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100 flex items-center text-red-600"><ion-icon name="log-out-outline" class="mr-2"></ion-icon> Déconnexion</a>
                </div>
            </div>
        </header>

        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside class="w-64 bg-black text-white shadow-md">
    <nav class="mt-6">
        <ul>
            <li><a href="#" data-section="dashboard" class="nav-link flex items-center px-4 py-2 text-white bg-blue-600 hover:bg-blue-700"><ion-icon name="home-outline" class="mr-2"></ion-icon> Tableau de bord</a></li>
            <li><a href="#" data-section="archive" class="nav-link flex items-center px-4 py-2 text-white hover:bg-blue-700"><ion-icon name="folder-outline" class="mr-2"></ion-icon> Gestion des archives</a></li>
            <li><a href="#" data-section="search" class="nav-link flex items-center px-4 py-2 text-white hover:bg-blue-700"><ion-icon name="search-outline" class="mr-2"></ion-icon> Recherche</a></li>
            <?php if ($role === 'admin'): ?>
                <li><a href="#" data-section="users" class="nav-link flex items-center px-4 py-2 text-white hover:bg-blue-700"><ion-icon name="people-outline" class="mr-2"></ion-icon> Gestion des utilisateurs</a></li>
                <li><a href="#" data-section="logs" class="nav-link flex items-center px-4 py-2 text-white hover:bg-blue-700"><ion-icon name="document-text-outline" class="mr-2"></ion-icon> Journal des actions</a></li>
            <?php endif; ?>
            <li><a href="../dist/logout.php" class="flex items-center px-4 py-2 text-red-500 hover:bg-red-700 hover:text-white"><ion-icon name="log-out-outline" class="mr-2"></ion-icon> Déconnexion</a></li>
        </ul>
    </nav>
</aside>

            <!-- Main Content -->
            <main id="mainContent" class="flex-1 p-8 overflow-auto">
                <!-- Content will be loaded dynamically here -->
            </main>
        </div>

        <!-- Profile Edit Modal -->
        <div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center modal modal-hidden">
            <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                <h3 class="text-lg font-medium mb-4 flex items-center"><ion-icon name="create-outline" class="mr-2"></ion-icon> Modifier le profil</h3>
                <form id="profileForm">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="mt-1 p-2 w-full border rounded" required>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="closeModalBtn" class="bg-gray-300 text-black px-4 py-2 rounded hover:bg-gray-400">Annuler</button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Load content dynamically
        function loadSection(section) {
            fetch(`../includes/${section}_content.php`) // Chemin corrigé
                .then(response => response.text())
                .then(data => {
                    document.getElementById('mainContent').innerHTML = data;
                    if (section === 'dashboard') {
                        initializeDashboard();
                    } else if (section === 'archive' || section === 'users' || section === 'logs') {
                        initializeDataTable();
                        if (section === 'archive') {
                            initializeArchiveJS();
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

        // Initialize DataTable
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

        // Initialize Dashboard Charts
        function initializeDashboard() {
            const ctx = document.getElementById('activityChart')?.getContext('2d');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: JSON.parse(document.getElementById('activityChart').dataset.labels || '[]'),
                        datasets: [{
                            label: 'Documents ajoutés',
                            data: JSON.parse(document.getElementById('activityChart').dataset.data || '[]'),
                            borderColor: '#1e40af', // Blue
                            backgroundColor: 'rgba(30, 64, 175, 0.1)',
                            pointBackgroundColor: '#dc2626', // Red
                            pointBorderColor: '#dc2626',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, title: { display: true, text: 'Nombre de documents', color: '#000000' }, ticks: { color: '#000000' } },
                            x: { title: { display: true, text: 'Date', color: '#000000' }, ticks: { color: '#000000' } }
                        },
                        plugins: {
                            legend: { labels: { color: '#000000' } },
                            tooltip: { mode: 'index', intersect: false }
                        },
                        interaction: { mode: 'nearest', axis: 'x', intersect: false }
                    }
                });
            }
            initializeDataTable();
        }

        // Profile dropdown toggle
        document.getElementById('profileBtn').addEventListener('click', () => {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        });

        // Profile modal toggle
        const profileModal = document.getElementById('profileModal');
        document.getElementById('editProfileBtn').addEventListener('click', () => {
            profileModal.classList.remove('modal-hidden');
            profileModal.classList.add('modal-visible');
        });
        document.getElementById('closeModalBtn').addEventListener('click', () => {
            profileModal.classList.remove('modal-visible');
            profileModal.classList.add('modal-hidden');
        });

        // Profile form submission
        document.getElementById('profileForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            fetch('../includes/update_profile.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload(); // Reload to update email display
                    } else {
                        alert('Erreur lors de la mise à jour du profil');
                    }
                });
        });

        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                loadSection(link.dataset.section);
            });
        });

        // Load dashboard by default
        loadSection('dashboard');

        function initializeArchiveJS() {
            const isAdmin = <?php echo json_encode($role === 'admin'); ?>;
            const form = document.getElementById('uploadForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);

                    Swal.fire({
                        title: 'Chargement...',
                        text: 'Veuillez patienter pendant le téléchargement.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('../includes/archive_content.php', {
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
                                document.getElementById('uploadForm').reset();
                                // Optionally reload the archive section
                                loadSection('archive');
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
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Une erreur est survenue. Veuillez réessayer.'
                        });
                    });
                });

                // Gestion des formulaires de suppression
                document.querySelectorAll('[id^="deleteForm_"]').forEach(form => {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const docId = this.querySelector('input[name="doc_id"]').value;

                        Swal.fire({
                            title: 'Chargement...',
                            text: 'Suppression en cours.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch('../includes/archive_content.php', {
                            method: 'POST',
                            body: new FormData(this)
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
                                    this.closest('tr').remove();
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
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: 'Une erreur est survenue. Veuillez réessayer.'
                            });
                        });
                    });
                });
            }
        }
    </script>
</body>
</html>