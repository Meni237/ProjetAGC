<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - AGC Archives</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
        <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
        .input-field:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            transition: all 0.3s ease;
        }
        .error-message {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .social-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                padding: 1rem;
            }
            .login-card {
                width: 100%;
            }
            .wave { height: 150px; border-bottom-left-radius: 50% 80px; border-bottom-right-radius: 50% 80px; }
        }
        .input-icon {
            position: relative;
        }
        .input-icon ion-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #6b7280;
        }
        .input-icon input {
            padding-left: 40px;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #6b7280;
            cursor: pointer;
        }
    </style>
</head>
<body class="relative flex bg-gray-50">
    <!-- Fond avec vagues -->
    <div class="wave"></div>

    <!-- Image de fond avec superposition -->
    <div class="absolute inset-0 bg-center bg-cover" style="background-image: url('../image/image.webp'); opacity: 0.1;"></div>

    <!-- Conteneur de connexion -->
    <div class="flex items-center justify-center w-full h-screen">
        <div class="relative z-10 flex w-[50%] p-2 bg-white login-container">
            <!-- Conteneur d'image -->
            <div class="hidden object-cover w-1/2 bg-center bg-cover rounded-md md:block" style="background-image: url('../image/famille.webp');">
                <div class="flex flex-col justify-center w-full h-full p-6 text-center text-white bg-black bg-opacity-[50%]">
                    <h2 class="text-3xl font-bold">Vos archives, protégées avec soin</h2>
                    <p class="mt-2 text-gray-300">Rejoignez-nous et accédez à nos services exclusifs.</p>
                </div>
            </div>

            <!-- Carte de connexion -->
            <div class="w-1/2 p-6 space-y-6 shadow-2xl login-card rounded-xl backdrop-blur-sm">
                <!-- Logo ou icône -->
                <div class="flex justify-center mb-4">
                    <img src="../image/logo.jpg" alt="erreur" class="w-10 h-10">
                </div>

                <h2 class="text-3xl font-bold text-center text-gray-800">Connexion à AGC</h2>

                <!-- Onglets -->
                <div class="flex justify-between p-1 text-sm bg-gray-200 rounded-full">
                    <button class="w-full py-2 font-semibold text-gray-700 bg-white rounded-full">Connexion</button>
                </div>

                <!-- Message d'erreur -->
                <div id="error-message" class="hidden w-[85%] bg-red-200 text-red-500 border border-red-500 rounded-sm p-2 text-center mx-auto"></div>

                <!-- Formulaire de connexion -->
                <form id="login-form" method="POST" action="login.php" class="space-y-4">
                    <div class="relative input-icon">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="email" name="email" id="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field" placeholder="Email">
                    </div>
                    <div class="relative input-icon">
                        <input type="password" name="password" id="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field" placeholder="Mot de passe">
                        <ion-icon name="eye-outline" id="toggle-password" class="password-toggle"></ion-icon>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="mr-1 text-blue-600 border-gray-300 rounded focus:ring-blue-600">
                            <span class="text-gray-600">Se souvenir de moi</span>
                        </label>
                        <a href="#" id="forgot-password" class="text-blue-600 hover:underline">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="w-full py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">Connexion</button>
                </form>

                <!-- Séparateur -->
                <div class="text-sm text-center text-gray-400">ou se connecter avec</div>

                <!-- Boutons sociaux -->
                <div class="flex justify-center space-x-4">
                    <button class="flex items-center px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg shadow social-btn hover:shadow-md" data-provider="Google">
                        <img src="https://cdn-icons-png.flaticon.com/512/2991/2991148.png" alt="Google" class="w-4 h-4 mr-2">
                        Google
                    </button>
                    <button class="flex items-center px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg shadow social-btn hover:shadow-md" data-provider="Facebook">
                        <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" alt="Facebook" class="w-4 h-4 mr-2">
                        Facebook
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Basculement de la visibilité du mot de passe
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            togglePassword.name = type === 'password' ? 'eye-outline' : 'eye-off-outline';
        });

        // Soumission du formulaire de connexion
        const loginForm = document.getElementById('login-form');
        const errorMessageDiv = document.getElementById('error-message');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(loginForm);

            try {
                const response = await fetch('../includes/auth.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Rediriger vers le tableau de bord
                    window.location.href = '../pages/dashboard.php';
                } else {
                    errorMessageDiv.textContent = data.message || 'Email ou mot de passe incorrect.';
                    errorMessageDiv.classList.remove('hidden');
                    Swal.fire('Erreur', data.message || 'Email ou mot de passe incorrect.', 'error');
                }
            } catch (error) {
                errorMessageDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
                errorMessageDiv.classList.remove('hidden');
                Swal.fire('Erreur', 'Une erreur est survenue. Veuillez réessayer.', 'error');
            }
        });

        // Gestion du lien "Mot de passe oublié"
        const forgotPasswordLink = document.getElementById('forgot-password');
        forgotPasswordLink.addEventListener('click', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;

            if (!email) {
                Swal.fire('Erreur', 'Veuillez entrer votre adresse email.', 'error');
                return;
            }

            try {
                const response = await fetch('../includes/auth.php?forgot=true', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire('Succès', 'Un lien de réinitialisation a été envoyé à votre email.', 'success');
                } else {
                    Swal.fire('Erreur', data.message || 'Aucun compte trouvé avec cet email.', 'error');
                }
            } catch (error) {
                Swal.fire('Erreur', 'Une erreur est survenue. Veuillez réessayer.', 'error');
            }
        });

        // Gestion des boutons de connexion sociale (Google, Facebook)
        const socialButtons = document.querySelectorAll('.social-btn');
        socialButtons.forEach(button => {
            button.addEventListener('click', () => {
                const provider = button.dataset.provider;
                // Rediriger vers l'authentification OAuth (à implémenter côté backend)
                window.location.href = `/auth/${provider.toLowerCase()}`;
            });
        });
    </script>
</body>
</html>