<?php
session_start();

// Vérifier l'email de l'utilisateur depuis la session
$currentUserEmail = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

if (empty($currentUserEmail)) {
    // Afficher une alerte en HTML et rediriger vers auth.php après un délai
    echo "<script>alert('Vous devez être connecté pour voir vos événements.'); window.location.href = 'auth.php';</script>";
    exit;
}

// Connexion à la base de données avec mysqli
$host = 'localhost';
$dbname = 'tpweb';
$user = 'root';
$password = '';

// Créer une connexion à la base de données
$conn = new mysqli($host, $user, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Vérifier si l'utilisateur a cliqué sur "Se désinscrire"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && !empty($_POST['event_id'])) {
    $eventId = $_POST['event_id'];

    // Supprimer l'entrée de la table event_user
    $sql = "DELETE FROM event_user WHERE event_id = ? AND email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $eventId, $currentUserEmail);

    if ($stmt->execute()) {
        $successMessage = "Vous vous êtes désinscrit avec succès.";

        // Décrémenter la popularité de l'événement
        $sqlDecrement = "UPDATE evenements SET  popularite = popularite - 1  WHERE id = ?";
        $stmtDecrement = $conn->prepare($sqlDecrement);
        $stmtDecrement->bind_param('i', $eventId);
        
        if ($stmtDecrement->execute()) {
            // Décrémentation réussie
        } else {
            $errorMessage = "Une erreur est survenue lors de la mise à jour de la popularité.";
        }
    } else {
        $errorMessage = "Une erreur est survenue lors de la désinscription.";
    }
}

// Requête SQL pour obtenir les événements de l'utilisateur connecté
$sql = "
    SELECT 
        e.id AS event_id, e.eventTitle, e.eventDate, e.eventTime,
        e.lieu, e.organisation, e.categorie, e.image, e.description, eu.status
    FROM evenements e
    JOIN event_user eu ON e.id = eu.event_id
    WHERE eu.email = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $currentUserEmail);
$stmt->execute();

$result = $stmt->get_result();
$evenements = $result->fetch_all(MYSQLI_ASSOC);

// Affichage si aucun événement n'est trouvé
// if (empty($evenements)) {
//     echo "<div class='alert alert-no-events'>Aucun événement trouvé pour cet utilisateur.</div>";
//     echo "<p>Peut-être que vous pouvez <a href='events.php' class='btn-link'>consulter nos prochains événements</a> et vous y inscrire.</p>";
//     exit;
// }
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>

    <title>Mes Événements</title>
    <style>
    /* Styles pour l'alerte d'absence d'événements */
    .alert-no-events {
    background-color: #9b4d96; /* Violet Pink */
    color: #fff;
    padding: 20px;
    border-radius: 10px;
    font-size: 18px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
}


.btn-link {
    color: #d6a7f7; /* Violet clair */
    text-decoration: none;
    font-weight: bold;
}

.btn-link:hover {
    color: #9b4d96; /* Violet Pink */
    text-decoration: underline;
}

/* Effet de survol pour l'alerte */
.alert-no-events:hover {
    background-color: #8a3b80; /* Violet plus foncé */
    cursor: pointer;
}

/* Responsive pour écrans plus petits */
@media (max-width: 768px) {
    .alert-no-events {
        font-size: 16px;
    }
}


    body {
        font-family: 'Roboto', Arial, sans-serif;
        background-color: #f7e9f2;
        margin: 0;
        padding: 0;
        color: #333;
    }
      /* Conteneur pour Lottie en tant que fond */
      #lottie-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1; /* Derrière tout le contenu */
            overflow: hidden;
            background-color: lightgray; /* Couleur de fond de secours */
        }


    .container {
        z-index: 1; 
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    h1 {
        text-align: center;
        color:#d63384; 
        margin-bottom: 30px;
        font-size: 2.5rem;
        font-weight: 700;
    }

    .event-card {
        position: relative;
        background-size: cover;
        background-position: center;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
        color: #fff;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .event-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }

    .event-content {
        background: rgba(0, 0, 0, 0.7);
        padding: 20px;
    }

    .event-content h2 {
        font-size: 1.8rem;
        margin-bottom: 15px;
        color:magenta;
        font-weight: 600;
    }

    .event-content p {
        margin: 5px 0;
        font-size: 1rem;
        line-height: 1.5;
    }

    .event-content strong {
        font-size: 1.1rem;
        color:#d63384;
    }

    .status {
    position: absolute;
    bottom: 10px; /* Position en bas avec un léger espace */
    left: 50%; /* Centrer horizontalement */
    transform: translateX(-50%); /* Ajuster pour centrer parfaitement */
    font-weight: bold;
    font-size: 1rem;
    padding: 5px 15px;
    border-radius: 5px;
    display: inline-block;
    text-align: center;
}

.status.pending {
    background-color: orange;
}

.status.rejected {
    background-color: red;
    font-weight: bold;
}

.status.registered {
    background-color: green;
    font-weight: bold;
}



    .btn-unsubscribe {
        display: inline-block;
        margin-top: 15px;
        padding: 12px 25px;
        background-color: #d32f2f;
        color: #fff;
        text-decoration: none;
        border-radius: 30px;
        font-weight: 600;
        text-align: center;
        font-size: 1rem;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .btn-unsubscribe:hover {
        background-color: #b71c1c;
        transform: scale(1.05);
    }

    @media (max-width: 768px) {
        h1 {
            font-size: 2rem;
        }

        .event-content h2 {
            font-size: 1.5rem;
        }

        .btn-unsubscribe {
            padding: 10px 20px;
            font-size: 0.9rem;
        }
    }
    .lottie-background {
    background-size: cover;
    position:absolute;
    top: 1%;
    left: 15%;
    width: 100%;
    height: 100%;
    z-index: -1;
    overflow: hidden;
    }
        
    .lottie-background dotlottie-player {
        width: 100%;
        height: 100%;
        pointer-events: none;
    }
    .back-button {
    display: block;
    margin: 20px auto;
    padding: 10px 20px;
    background-color: #d63384;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
    
}
.back-button a{
    text-decoration: none;
    color: white;
}

.back-button:hover {
    background-color: #a0196f;
}
</style>

</head>
<body>
<div class="lottie-background"> <dotlottie-player src="https://lottie.host/cb9f74d8-330a-476b-ad2e-7d03426f974c/46PbrRG5xv.lottie" background="transparent" speed="1" loop autoplay></dotlottie-player>
</div>
<div class="container">
    <h1>Mes Événements</h1> <!-- Titre de la section -->
    <?php if (empty($evenements)) { ?>
       <div class="alert-no-events">Aucun événement trouvé pour cet utilisateur.</div>
       <p>Peut-être que vous pouvez <a href="events_search.php" class="btn-link">Consulter nos prochains événements</a>
       et vous y inscrire.</p>
   <?php } ?>

    <?php foreach ($evenements as $event): ?>
        <div class="event-card" style="background-image: url('<?php echo htmlspecialchars($event['image']); ?>');">
            <div class="event-content">
                <h2><?php echo htmlspecialchars($event['eventTitle']); ?></h2>
                <p><strong>Date :</strong> <?php echo htmlspecialchars($event['eventDate']); ?></p>
                <p><strong>Heure :</strong> <?php echo htmlspecialchars($event['eventTime']); ?></p>
                <p><strong>Lieu :</strong> <?php echo htmlspecialchars($event['lieu']); ?></p>
                <p><strong>Organisation :</strong> <?php echo htmlspecialchars($event['organisation']); ?></p>
                <p><strong>Catégorie :</strong> <?php echo htmlspecialchars($event['categorie']); ?></p>
                <p><?php echo htmlspecialchars($event['description']); ?></p>
                <p class="status 
                <?php 
                if ($event['status'] === 'En Attente') { 
                    echo 'pending'; 
                } elseif ($event['status'] === 'Rejeté') { 
                    echo 'rejected'; 
                } else { 
                    echo 'registered'; 
                } 
                ?>">
                <?php
                if ($event['status'] === 'En Attente') {
                    echo "Votre demande est en attente.";
                } elseif ($event['status'] === 'Rejeté') {
                    echo "Malheureusement, vous êtes inaccepté à cet événement à cause de certaines contraintes. Vous pouvez contacter le créateur de l'événement pour vérifier.";
                } else {
                    echo "Vous êtes inscrit. Merci de ne pas rater cet événement !";
                }
                ?>
            </p>

        <?php if ($event['status'] !== 'Rejeté'): ?>
            <form method="POST">
                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['event_id']); ?>">
                <button class="btn-unsubscribe" type="submit">Se désinscrire</button>
            </form>
        <?php endif; ?>

            </div>
        </div>
    <?php endforeach; ?>
</div>
 <!-- Bouton de retour -->
 <button class="back-button" ><a href="aceuil.php">Retour</a></button>
</body>
</html>
