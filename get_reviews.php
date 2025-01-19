<?php
session_start();

// Connexion à la base de données
$host = 'localhost';
$dbname = 'tpweb';  // Remplacez par le nom de votre base
$user = 'root';     // Nom d'utilisateur de la base de données
$password = '';     // Mot de passe de la base de données
// Créer une connexion
$connection = mysqli_connect($host, $user, $password, $dbname);

// Vérifier la connexion
if (!$connection) {
    die("Erreur de connexion à la base de données : " . mysqli_connect_error());
}

// Vérifier si l'ID de l'événement est fourni
if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);

    // Requête pour récupérer les avis de l'événement avec les informations de l'utilisateur
    $query = "
        SELECT c.content, c.predict, u.firstname, u.lastname
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.event_id = ?
    ";
    $stmt = $connection->prepare($query);
    if ($stmt === false) {
        error_log("Erreur de préparation de la requête : " . $connection->error);
        echo json_encode(['error' => 'Erreur de préparation de la requête']);
        exit;
    }
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        error_log("Erreur d'exécution de la requête : " . $stmt->error);
        echo json_encode(['error' => 'Erreur d\'exécution de la requête']);
        exit;
    }

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    // Retourner les avis au format JSON
    echo json_encode(['reviews' => $reviews]);

    $stmt->close();
} else {
    echo json_encode(['error' => 'ID de l\'événement non fourni']);
}
// Fermer la connexion
mysqli_close($connection);
?>
