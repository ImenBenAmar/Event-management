<?php


// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    // Connexion à la base de données
    $server = 'localhost'; // Remplacez par votre serveur
    $user = 'root';        // Remplacez par votre utilisateur
    $password = '';        // Remplacez par votre mot de passe
    $base = 'tpweb';

    // Connexion au serveur
    $connexion = mysqli_connect($server, $user, $password, $base);

    // Vérifiez la connexion
    if (!$connexion) {
        die("Échec de la connexion : " . mysqli_connect_error());
    }

    // Initialise une variable pour les messages d'erreur
    $errorMessage = "";

    // Vérifie si le formulaire est soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = mysqli_real_escape_string($connexion, filter_input(INPUT_POST, 'email'));
        $password = filter_input(INPUT_POST, 'password');
        $role = filter_input(INPUT_POST, 'role'); // Récupérer le rôle sélectionné

        // Vérifier que le rôle est valide
        if ($role !== 'admin' && $role !== 'user') {
            $_SESSION['error_message'] = "Rôle invalide.";
            header("Location: auth.php");
            exit();
        }

        // Déterminer la table à interroger en fonction du rôle
        $table = ($role === 'admin') ? 'admins' : 'users';
        $query = "SELECT * FROM $table WHERE email = '$email'";

        // Exécuter la requête
        $result = mysqli_query($connexion, $query);

        if (!$result) {
            die("Erreur de requête SQL : " . mysqli_error($connexion));
        }

        if (mysqli_num_rows($result) > 0) {
            // Email trouvé, vérifier le mot de passe
            $row = mysqli_fetch_assoc($result);

            // On suppose que le mot de passe est stocké sous forme de hachage
            if (password_verify($password, $row['Password'])) {
                // Authentification réussie
                $_SESSION['user_idd'] = $row['id']; // Stocker l'ID de l'utilisateur dans la session
                $_SESSION['user_id'] = $row['email'];  // Utilisez l'email comme identifiant
                $_SESSION['user_role'] = $role;  // Enregistrer le rôle

                // Rediriger en fonction du rôle
                if ($role === 'user') {
                    header("Location: aceuil.php");
                } else {
                    header("Location: admin.php");
                }
                exit();
            } else {
                // Mot de passe incorrect
                $_SESSION['error_message'] = "Mot de passe incorrect.";
            }
        } else {
            // Email non trouvé
            $_SESSION['error_message'] = "Cet email n'existe pas.";
        }
    }

    // Fermer la connexion
    mysqli_close($connexion);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'authentification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
           body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, rgba(0, 0, 50, 0.5), rgba(0, 50, 100, 0.5)), 
                        url('https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSdtCKH3KuQ6oUh0xELNFk-kOhXdZBQMkxthmqQe8S5bjBWmlI8HW1ODAwfToDs3j3HJRw&usqp=CAU') no-repeat center center/cover;
            backdrop-filter: blur(5px);
        }
        .auth-wrapper {
            display: flex;
            width: 80%;
            max-width: 1200px;
            background-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(110, 89, 89, 0.2);
            border-radius: 15px;
            overflow: hidden;
            padding: 40px;
        }
        .left-side {
            width: 50%;
            padding: 30px;
            color: rgb(255, 255, 255);
        }
        .left-side h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .left-side p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            color: #0c0b0c;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .role-btn {
            width: 48%;
            padding: 12px;
            font-size: 1.1rem;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .role-btn.admin {
            background-color: #66616d;
            color: white;
        }
        .role-btn.user {
            background-color:  #66616d;
            color: white;
        }
        .role-btn.active {
            background-color: #8b226c !important;
        }
        .right-side {
            width: 50%;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            width: 80%;
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            text-align: center;
        }
        .form-container h2 {
            font-size: 2rem;
            color: #6c2451;
            text-align: center;
            margin-bottom: 1.5rem;
            
        }
        .form-container .form-group {
            margin-bottom: 20px;
            
        }
        .form-container .form-control {
            background-color: rgba(255, 255, 255, 0.5);
            border: none;
            color: rgb(26, 20, 20);
            border-radius: 7px;
            width: 90%;
            text-align: center;
            margin: 5%;
            
        }
        .form-container .btn {
            width: 90%;
            background-color: #5d1148;
            color: white; 
            border-radius: 7px;
           
        }
        .form-container .btn:hover {
            background-color: #d62191;
        }
        .form-container p {
            text-align: center;
            color: rgb(13, 13, 13);
        }
        .form-container a {
            color: #a62949;
        }
        .password-wrapper {
            position: relative;
            margin: 5% auto;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .toggle-password:hover {
            color: #8b226c;
        }
        .error-message {
            color: red;
            margin: 10px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="left-side">
            <h1>Bienvenue sur notre site web</h1>
            <p>Veuillez choisir votre rôle :</p>

            <!-- Boutons de sélection du rôle -->
            <div class="btn-group">
                <button id="admin-btn" class="role-btn admin" onclick="selectRole('admin')">Admin</button>
                <button id="user-btn" class="role-btn user" onclick="selectRole('user')">Utilisateur</button>
            </div>
        </div>

        <div class="right-side">
            <div class="form-container">
                <h2>Authentification</h2>
                
                <?php
                // Afficher le message d'erreur si disponible
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="error-message">' . $_SESSION['error_message'] . '</div>';
                    // Effacer le message d'erreur de la session
                    unset($_SESSION['error_message']);
                }
                ?>
                
                <form id="authForm" method="POST" action="auth.php">
                    <div class="form-group">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group password-wrapper">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                        <span class="toggle-password" onclick="togglePassword()">
                            👁️
                        </span>
                    </div>

                    <!-- Champ caché pour envoyer le rôle -->
                    <input type="hidden" id="role" name="role" value="">

                    <button type="submit" class="btn">Se connecter</button>
                    <p class="mt-3">Vous n'avez pas de compte? <a href="inscription.php">Inscrivez-vous</a></p>
                </form>
            </div>
        </div>
    </div>

    <script>
        function selectRole(role) {
            // Réinitialiser les styles des boutons
            document.getElementById('admin-btn').classList.remove('active');
            document.getElementById('user-btn').classList.remove('active');

            // Ajouter la classe active au bouton sélectionné
            document.getElementById(`${role}-btn`).classList.add('active');

            // Mettre à jour le champ caché avec le rôle sélectionné
            document.getElementById('role').value = role;
        }
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        }
    </script>
</body>
</html>
