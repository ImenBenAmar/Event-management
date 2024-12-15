<?php
session_start();

// Connexion à la base de données
$server = 'localhost';
$user = 'root';
$password = '';
$database = 'tpweb';

$connexion = mysqli_connect($server, $user, $password, $database);

if (!$connexion) {
    die("Connexion échouée : " . mysqli_connect_error());
}

// Vérifier si les données sont envoyées via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    $user_email = isset($_POST['user_email']) ? mysqli_real_escape_string($connexion, $_POST['user_email']) : '';

    // Vérifier que les champs ne sont pas vides
    if ($event_id > 0 && !empty($user_email)) {
        // Vérifier si l'utilisateur est déjà inscrit
        $checkQuery = "SELECT * FROM event_user WHERE event_id = $event_id AND email = '$user_email'";
        $checkResult = mysqli_query($connexion, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {
            echo "<script>alert('Vous êtes déjà inscrit à cet événement.'); window.location.href = 'events_search.php';</script>";
        } else {
            // Insérer l'utilisateur dans la table event_user
            $insertQuery = "INSERT INTO event_user (event_id, email, status, created_at) VALUES ($event_id, '$user_email', 'En Attente', NOW())";

            if (mysqli_query($connexion, $insertQuery)) {
                echo "<script>alert('Demande d\'inscription réussie!'); window.location.href = 'events_search.php';</script>";
            } else {
                echo "<script>alert('Erreur lors de l\'inscription : " . mysqli_error($connexion) . "'); window.location.href = 'events_search.php';</script>";
            }
        }
    } else {
        echo "<script>alert('Données invalides. Veuillez réessayer.'); window.location.href = 'events_search.php';</script>";
    }
} else {
    echo "<script>alert('Requête invalide.'); window.location.href = 'events_search.php';</script>";
}

mysqli_close($connexion);
?>
