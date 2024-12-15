<?php
// Connexion à la base de données
$server = 'localhost';
$user = 'root';
$password = '';
$base = 'tpweb';

$connexion = new mysqli($server, $user, $password, $base);

if ($connexion->connect_error) {
    die("Échec de la connexion : " . $connexion->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)$_POST['id'];
    $eventTitle = $connexion->real_escape_string($_POST['eventTitle']);
    $eventDate = $connexion->real_escape_string($_POST['eventDate']);
    $eventTime = $connexion->real_escape_string($_POST['eventTime']);
    $participants = (int)$_POST['participants'];
    $lieu = $connexion->real_escape_string($_POST['lieu']);
    $organisation = $connexion->real_escape_string($_POST['organisation']);
    $categorie = $connexion->real_escape_string($_POST['categorie']);
    $genre = $connexion->real_escape_string($_POST['genre']);
    $description = $connexion->real_escape_string($_POST['description']);

    $updateQuery = $connexion->prepare("UPDATE evenements SET eventTitle = ?, eventDate = ?, eventTime = ?, participants = ?, lieu = ?, organisation = ?, categorie = ?, genre = ?, description = ? WHERE id = ?");
    $updateQuery->bind_param("sssisssssi", $eventTitle, $eventDate, $eventTime, $participants, $lieu, $organisation, $categorie, $genre, $description, $id);

    if ($updateQuery->execute()) {
        echo "<script>
        alert('Événement mis à jour avec succès.');
        window.location.href = 'events_search.php'; // Remplacez par la page de redirection
      </script>";
    } else {
        echo "Erreur lors de la mise à jour de l'événement : " . $updateQuery->error;
    }

    $updateQuery->close();
}

$connexion->close();
?>
