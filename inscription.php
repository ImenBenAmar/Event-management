<?php
// Connexion à la base de données
$server = 'localhost';  // Remplacez par votre serveur
$user = 'root';         // Remplacez par votre utilisateur
$password = '';         // Remplacez par votre mot de passe
$base = 'tpweb';

// Connexion au serveur
$connexion = mysqli_connect($server, $user, $password);

// Vérifiez la connexion
if (!$connexion) {
    die("Échec de la connexion : " . mysqli_connect_error());
}

// Sélection de la base de données
if (!mysqli_select_db($connexion, $base)) {
    die("Sélection de la base de données échouée : " . mysqli_error($connexion));
}

// Initialise une variable pour les messages d'erreur
$errorMessage = "";
$emailError = false; // Variable pour l'erreur d'email

// Vérifie si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = mysqli_real_escape_string($connexion, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($connexion, $_POST['lastName']);
    $email = mysqli_real_escape_string($connexion, $_POST['email']);
    $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);  // Hash du mot de passe
    $role = $_POST['role'];  // Récupération du rôle sélectionné
    
    // Vérifie si le rôle est vide
    if (empty($role)) {
        $errorMessage = "Veuillez sélectionner un rôle.";
    } else {
        if ($role === 'admin') {
            $query = "SELECT * FROM admins WHERE email = '$email'";
        } else {
            $query = "SELECT * FROM users WHERE email = '$email'";
        }
        // Vérifie si l'email existe déjà dans la base de données
        $result = mysqli_query($connexion, $query);

        if (mysqli_num_rows($result) > 0) {
            $emailError = true; // Indique que l'email existe déjà
            $errorMessage = "Cet email est déjà utilisé.";
        } else {
            // Détermine la table en fonction du rôle
            $table = ($role === 'admin') ? 'admins' : 'users';

            // Préparation de la requête d'insertion
            $sql = "INSERT INTO $table (firstname, lastname, email, password)
                    VALUES ('$firstName', '$lastName', '$email', '$passwordHash')";
            
            // Exécution de la requête
            if (mysqli_query($connexion, $sql)) {
                echo "<script>alert('Inscription réussie en tant que " . ucfirst($role) . " !');
                window.location.href = 'auth.php';
                </script>";
            } else {
                $errorMessage = "Erreur lors de l'inscription : " . mysqli_error($connexion);
            }
        }
    }
}

// Fermer la connexion
mysqli_close($connexion);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="style.css" rel="stylesheet">
    <style>
        .error {
            border: 1px solid red; /* Bordure rouge pour le champ d'erreur */
        }
        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
            font-size: large;
            background-color: rgba(255, 0, 0, 0.1);
            padding: 10px;
            border-radius: 5px;
        }
        
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            background: linear-gradient(135deg, rgba(0, 0, 50, 0.5), rgba(0, 50, 100, 0.5)), url('https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSdtCKH3KuQ6oUh0xELNFk-kOhXdZBQMkxthmqQe8S5bjBWmlI8HW1ODAwfToDs3j3HJRw&usqp=CAU') no-repeat center center/cover;
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
            opacity: 0;
            animation: fadeIn 1s forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .left-side {
            width: 50%;
            padding: 30px;
            color: #ffffff;
        }
        .left-side p {
            margin-top: 10%;
            align-items: center;
            color: #0c0b0c;
            font-size: large;
            margin-bottom: 1.5rem;
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
            margin-bottom: 1.5rem;
        }
        .form-container .form-control {
            background-color: rgba(255, 255, 255, 0.5);
            border: none;
            color: #1a1414;
            border-radius: 7px;
            width: 90%;
            text-align: center;
            margin: 5%;
        }
        .form-container .btn {
            width: 90%;
            background-color: #490f3a;
            color: white;
            border: none;
            border-radius: 7px;
        }
        .form-container .btn:hover {
            background-color: #d62191;
        }
        .form-container p {
            color: #171414;
        }
        .form-container a {
            color: #a62949;
        }
        .role-btn.admin {
            background-color: #66616d;
            color: white;
        }
        .role-btn.user {
            background-color: #66616d;
            color: white;
        }
        .role-btn.active {
            background-color: #8b226c !important;
        }
        .role-btn {
            width: 48%;
            padding: 12px;
            font-size: 1.1rem;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
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
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="left-side">
            <h1>Inscription</h1>
            <p>Veuillez remplir le formulaire pour créer un compte.</p>
            <p>Veuillez choisir votre rôle :</p>
            <!-- Boutons de sélection du rôle -->
            <div class="btn-group">
                <button id="admin-btn" class="role-btn admin" onclick="selectRole('admin')">Admin</button>
                <button id="user-btn" class="role-btn user" onclick="selectRole('user')">Utilisateur</button>
            </div>
        </div>
        <div class="right-side">
            <div class="form-container">
                <?php if (!empty($errorMessage)): ?>
                <div class="error-message">
                    <?php echo $errorMessage; ?>
                </div>
                <?php endif; ?>
                <form method="post" action="">
                    <input type="text" name="firstName" placeholder="Prénom" required class="form-control">
                    <input type="text" name="lastName" placeholder="Nom" required class="form-control">
                    <input type="email" name="email" placeholder="Email" required class="form-control <?php echo $emailError ? 'error' : ''; ?>">
                    <div class="password-wrapper">
                        <input type="password" name="password" placeholder="Mot de passe" required class="form-control">
                        <span class="toggle-password" onclick="togglePassword()">👁️</span>
                    </div>
                    <input type="hidden" name="role" id="role" value="">
                    <button type="submit" class="btn">S'inscrire</button>
                    <p>Vous avez déjà un compte ? <a href="auth.php">Connectez-vous ici</a></p>
                </form>
            </div>
        </div>
    </div>

    <script>
        let selectedRole = '';

        function selectRole(role) {
            selectedRole = role;
            document.getElementById('role').value = selectedRole;

            // Mettre à jour l'apparence des boutons
            const adminBtn = document.getElementById('admin-btn');
            const userBtn = document.getElementById('user-btn');

            adminBtn.classList.remove('active');
            userBtn.classList.remove('active');

            if (role === 'admin') {
                adminBtn.classList.add('active');
            } else {
                userBtn.classList.add('active');
            }
        }

        function togglePassword() {
            const passwordInput = document.querySelector('input[name="password"]');
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
