<?php
session_start();

// Connexion à la base de données
$host = 'localhost';
$dbname = 'tpweb';
$user = 'root';
$password = '';
$connection = mysqli_connect($host, $user, $password, $dbname);

if (!$connection) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
error_log(print_r($input, true)); // Log les données reçues

// Récupérer les données de la requête
$comment = $input['comment'] ?? null;
$eventId = $input['event_id'] ?? null;
$userId = $_SESSION['user_idd'] ?? null; // Utilisateur connecté

// Log the received data
error_log("Received data: " . print_r($input, true));

if (!$comment || !$eventId || !$userId) {
    echo json_encode(['success' => false, 'error' => 'Champs requis manquants']);
    exit;
}

// Appeler l'API Flask pour prédire le sentiment et enregistrer le commentaire
$flaskUrl = 'http://127.0.0.1:5000/predict';
$data = json_encode(['comment' => $comment, 'event_id' => $eventId, 'user_id' => $userId]);

$options = [
    'http' => [
        'header' => "Content-Type: application/json\r\n",
        'method' => 'POST',
        'content' => $data,
    ],
];

$context = stream_context_create($options);
$response = file_get_contents($flaskUrl, false, $context);

if ($response === FALSE) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l’appel à l’API Flask']);
    exit;
}

$responseData = json_decode($response, true);
$prediction = $responseData['prediction'] ?? null;

if (!$prediction) {
    echo json_encode(['success' => false, 'error' => 'Erreur de prédiction']);
    exit;
}

// Retourner la réponse de l'API Flask
echo json_encode(['success' => true, 'prediction' => $prediction]);
?>
