<?php
header('Content-Type: application/json'); // Définir le type de contenu comme JSON
include 'config.php'; // Inclure la configuration PDO

session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gestion de la connexion
    if (!isset($_GET['forgot']) || $_GET['forgot'] !== 'true') {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

        // Vérification des identifiants
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role']; // Assumer que la colonne 'role' existe

            // Gestion "Se souvenir de moi"
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expiry = time() + (30 * 24 * 3600); // 30 jours
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE user_id = ?");
                $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $user['user_id']]);
                setcookie('remember_me', $token, $expiry, '/', '', true, true);
            } else {
                setcookie('remember_me', '', time() - 3600, '/', '', true, true);
            }

            $response = ['success' => true];
        } else {
            $response = ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
        }
    }

    // Gestion "Mot de passe oublié"
    if (isset($_GET['forgot']) && $_GET['forgot'] === 'true') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);

        if ($email) {
            $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $reset_token = bin2hex(random_bytes(32));
                $expiry = time() + 3600; // 1 heure
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
                $stmt->execute([$reset_token, date('Y-m-d H:i:s', $expiry), $email]);

                // Simuler envoi email (à remplacer par une vraie fonction d'envoi)
                $reset_link = "http://localhost/reset.php?token=" . $reset_token;
                $response = ['success' => true, 'message' => 'Un lien de réinitialisation a été envoyé à votre email.'];
            } else {
                $response = ['success' => false, 'message' => 'Aucun compte trouvé avec cet email.'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Veuillez fournir un email valide.'];
        }
    }
}

echo json_encode($response);
exit();
?>