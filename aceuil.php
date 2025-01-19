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

// Gestion des demandes d'inscription
// Gestion des demandes d'inscription
if (isset($_POST['action']) && isset($_POST['event_id']) && isset($_POST['email'])) {
    $event_id = $_POST['event_id'];
    $email = $_POST['email'];
    $action = $_POST['action'];

    $status = ($action == 'accept') ? 'Accepté' : 'Rejeté';

    // Mettre à jour le statut
    $stmtUpdate = $connection->prepare("UPDATE event_user SET status = ? WHERE event_id = ? AND email = ?");
    $stmtUpdate->bind_param("sis", $status, $event_id, $email);
    $stmtUpdate->execute();
     
    $stmtUpdate->close();

    // Incrémenter la popularité si l'action est 'accept'
    if ($action == 'accept') {
        // Vérifier si l'event_id est valide avant de procéder à l'incrémentation
        $stmtCheckEvent = $connection->prepare("SELECT id FROM evenements WHERE id = ?");
        $stmtCheckEvent->bind_param("i", $event_id);
        $stmtCheckEvent->execute();
        $result = $stmtCheckEvent->get_result();

        if ($result->num_rows > 0) {
            // L'événement existe, procéder à l'incrémentation de la popularité
            $stmtIncrement = $connection->prepare("UPDATE evenements SET popularite = popularite + 1 WHERE id = ?");
            $stmtIncrement->bind_param("i", $event_id);
            $stmtIncrement->execute();
            
            $stmtIncrement->close();
        }

        $stmtCheckEvent->close();
    }
}

// Récupérer les événements approuvés et populaires
$sql = "SELECT nom, email, eventTitle, eventDate, eventTime, participants, lieu, organisation, categorie, genre, image, description
        FROM evenements 
        WHERE statuts = 'approuvé' AND popularite >= 3";

// Vérifier si le formulaire de recherche a été soumis
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = mysqli_real_escape_string($connection, trim($_GET['search']));
    $sql .= " AND (nom LIKE '%$search%' OR organisation LIKE '%$search%' OR lieu LIKE '%$search%' OR eventTitle LIKE '%$search%')";
}

$result = mysqli_query($connection, $sql);
if (!$result) {
    die("Erreur dans la requête : " . mysqli_error($connection));
}

$evenements = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Vérifiez si l'utilisateur est connecté
$currentUserEmail = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

// Gestion des notifications
$notificationCount = 0;
$notifications = [];
if (!empty($currentUserEmail)) {
    // Compter les demandes en attente
    $queryCount = "
        SELECT COUNT(*) AS notification_count
        FROM event_user eu
        INNER JOIN evenements e ON eu.event_id = e.id
        WHERE eu.status = 'En Attente'
          AND e.email = '$currentUserEmail'
    ";
    $resultCount = mysqli_query($connection, $queryCount);
    $rowCount = mysqli_fetch_assoc($resultCount);
    $notificationCount = $rowCount['notification_count'] ?? 0;

    // Récupérer les détails des notifications
    $queryNotifications = "
    SELECT eu.event_id, eu.email AS user_email, e.eventTitle, u.lastname AS user_name , u.firstname As user_surname
    FROM event_user eu
    INNER JOIN evenements e ON eu.event_id = e.id
    INNER JOIN users u ON eu.email = u.email
    WHERE eu.status = 'En Attente'
      AND e.email = '$currentUserEmail'
    ";

    $resultNotifications = mysqli_query($connection, $queryNotifications);
    $notifications = mysqli_fetch_all($resultNotifications, MYSQLI_ASSOC);
}
// Requête pour récupérer le prénom et l'avatar
$sql = "SELECT firstname FROM users WHERE email = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("s", $currentUserEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $firstname = $user['firstname'];
    $avatar = "https://static-00.iconduck.com/assets.00/user-avatar-icon-512x512-vufpcmdn.png";
}
// Fermer la connexion
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Page d'accueil pour trouver et créer des événements.">
    <meta name="keywords" content="événements, créer, trouver, billets">
    <title>Event Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background-size: cover;
            backdrop-filter: blur(5px);
            color: #333;
        }
        h3{
            text-decoration:wavy;
            color: black;
        }

        /* Full video background */
        .video-background {
            position: relative;
            left: 0;
            width: 100%;
            height: calc(100vh - 80px);
            overflow: hidden;
            z-index: 1;
        }

        /* Video style */
        .video-background video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Card section styling */
        .card-section {
            position: relative;
            bottom: 80px;
            left: 0;
            right: 0;
            z-index: 2;
            padding: 40px 15px;
        }

        .card {
            position: relative;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: inherit;
        background-size: cover;
        background-position: center;
        filter: blur(4px); /* Applique le flou */
        z-index: 1;
        }
        .card-body {
            background-color: rgba(255, 182, 193, 0.2);
            position: relative;
            z-index: 2;
            padding: 20px;
            border-radius: 10px;
            color: white;
        }

        .card:hover {
            transform: translateY(-5px);
        }

            /* Footer styles */
        /* Footer styles */
        footer {
            background-color: #cc98be;
            padding: 40px 20px;
            border-top: 1px solid #be1da1;
            color: #2f3d72;
        }

        .footer-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            width: 100%;
            max-width: 1200px;
            padding: 20px 0;
            border-bottom: 1px solid #be1da1;
        }

        .footer-section {
            flex: 1;
        }

        .developer-info {
            display: flex;
            flex-direction: column;
            align-items:center;
        }

        .developer {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .developer-img {
            width: 250px;
            height: 190px;
            border-radius: 60%;
            margin-right: 20px;
            border: 2px solid #2f3d72;
        }

        .developer-details {
            display: flex;
            flex-direction: column;
        }
        .developer-details p {
            color: purple;
            font-weight: bold;
        }
        .social-icons a {
            margin-left: 15px;
            transition: color 0.3s;

        } 
        .footer-section a {
            color: #2f3d72;
        }

        .footer-section a:hover {
            text-decoration: underline;
        }
        .footer-section h5, .footer-section h3, .footer-section h4 {
            margin: 10px 0;
        }
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-section {
                padding: 20px 15px;
            }
        }
       /* Navbar styles */
.navbar {
    transition: background 0.3s;
    top: 0; /* Coller en haut */
    z-index: 1000;
    position: sticky;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, rgba(0, 0, 50, 0.5), rgba(0, 50, 100, 0.5)), url('images\images.jpeg') no-repeat center center/cover; /* Image de fond avec dégradé */
    backdrop-filter: blur(10px); /* Flou en arrière-plan */
    padding: 20px 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.navbar.scrolled {
    background: rgba(206, 153, 200, 0.9); /* Fond plus opaque quand on fait défiler */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); /* Ombre plus prononcée quand on fait défiler */
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color: #3b0738;
    display: flex;
    align-items: center;
}
.logo img {
    width: 50%;
}

.navbar-content {
    display: flex;
    align-items: center;
    justify-content:space-around;
    width: 100%;
}
.search-bar {
    display: flex;
    align-items: center;
    margin-left: -170px;
}

.nav-links {
    display: flex;
    align-items: center;
    margin-left: 20px;
}

.nav-links a,
.nav-links button {
    margin-right: 10px;
    padding: 5px 8px;
    text-decoration: none;
    color: white;
    background-color: mediumorchid;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.nav-links a:hover,
.nav-links button:hover {
    background-color: #b968b6; /* Couleur survol */
}

.nav-links button.logout {
    background-color: #dc3545; /* Rouge pour logout */
}

.nav-links button.logout:hover {
    background-color: #c82333; /* Rouge foncé pour logout */
}

.user-info {
    display: flex;
    align-items: center;
    margin-right: 10px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

.user-info span {
    color: #fff;
    font-weight: bold;
    white-space: nowrap; /* Empêche le retour à la ligne du texte */
    overflow: hidden; /* Cache le texte débordant */
    text-overflow: ellipsis; /* Ajoute des points de suspension pour le texte débordant */
    max-width: 150px; /* Limite la largeur pour s'assurer que tout reste aligné */
}

       
        
      
        .content-section {
            display: flex; /* Utilise flexbox pour aligner les éléments */
            align-items: center; /* Centre verticalement les éléments */
            margin: 20px; /* Espace autour de la section */
            }
        
        .text-content {
        flex: 2; /* Permet au contenu textuel de prendre plus d'espace */
        margin-right: 20px; /* Espace entre le texte et l'image */
        }
        
        .image-content {
        flex: 1; /* Permet à l'image de prendre un espace fixe */
        }
        
        .image-content img {
        max-width: 80%; /* Réduit la taille maximale de l'image à 70% */
        height: auto; /* Garde les proportions de l'image */
        border-radius: 8px; /* Arrondit les coins de l'image (optionnel) */
        }
        /* Styles pour la section de logos défilants */
        .partners-section {
        overflow: hidden; /* Cache le débordement */
        white-space: nowrap; /* Empêche le retour à la ligne */
        margin: 20px 0; /* Espace autour de la section */
        }

        .partners-scroll {
        display: inline-block; /* Permet au conteneur de défiler */
        animation: scroll 20s linear infinite; /* Animation de défilement */
        }

        @keyframes scroll {
        from {
            transform: translateX(100%); /* Démarre complètement à droite */
        }
        to {
            transform: translateX(-100%); /* Se termine complètement à gauche */
        }
        }

        .partner-logo {
        display: inline-block; /* Affiche chaque logo en ligne */
        margin: 0 20px; /* Espace entre les logos */
        }

        .partner-logo img {
        max-height: 60px; /* Hauteur maximale des logos */
        height: auto; /* Garde les proportions des logos */
        }
        i{
        color:#b968b6;
        }
        a {
        text-decoration: none;
        }
        .type{
            color: black;
        }
        h4 {
        color:rgb(126, 11, 155); 
        padding: 30px;
        font-size:xx-large; 
        text-decoration: wavy;
        font-family: 'Lucida Sans', 'Lucida Sans Regular', 'Lucida Grande', 'Lucida Sans Unicode', Geneva, Verdana, sans-serif;
        text-shadow: 10px 10px 10px palevioletred;
        }
        .nav-links button.logout {
            background-color: #dc3545; /* Rouge pour logout */
        } 
    </style>
</head>
<body>
  <!-- Custom Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <div class="logo"><img src="images\Eventpro.png" alt="logo"></div>
            <div class="navbar-content">
                <div class="search-bar">
                    <form method="GET" action="#events">
                        <input type="text" name="search" placeholder="Chercher un événement" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                        <button type="submit" class="search-btn">🔍</button>
                    </form>
                </div>

                <div class="nav-links">
                    <a href="events_search.php">Trouver des événements</a>
                    <a href="creer_event.php">Créer des événements</a>
                    <a href="mesbillets.php">Mes billets</a>
                    <a href="avis.php">Voir les avis</a> <!-- New button -->
                    <?php if (empty($currentUserEmail)): ?>
                        <a href="auth.php" class="signup">Connexion</a>
                        <a href="inscription.php" class="signup">Inscrire</a>
                    <?php else: ?>
                        <div class="user-info">
                            <img src="<?= $avatar ?>" alt="User Avatar" class="user-avatar">
                            <span>Bonjour, <?= htmlspecialchars($firstname) ?>!</span>
                        </div>
                        <form method="post" action="logout.php" style="display: inline;">
                            <button type="submit" class="logout">Deconnexion</button>
                        </form>

                        <div class="notifications-container" style="position: relative; display: inline-block;">
                            <button class="notifications-btn" onclick="toggleNotifications()" style="background-color:white; border: none; cursor: pointer;">
                                <i class="fas fa-bell"></i>
                                <span class="notification-count" style="background-color: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; position: absolute; top: -5px; right: -10px;">
                                    <?php echo $notificationCount; ?>
                                </span>
                            </button>
                            <div class="notifications-dropdown" id="notificationsDropdown" style="display: none; position: absolute; top: 30px; right: 0; background: white; box-shadow: 0px 4px 8px rgba(0,0,0,0.2); padding: 10px; border-radius: 4px; z-index: 100; width: 300px;">
                                <h4>Notifications</h4>
                                <ul style="list-style: none; padding: 0; margin: 0;">
                                    <?php if (!empty($notifications)): ?>
                                        <?php foreach ($notifications as $notification): ?>
                                            <li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
                                                <strong><?php echo htmlspecialchars($notification['user_name']); ?> <?php echo htmlspecialchars($notification['user_surname']); ?></strong> veut s'inscrire à <strong><?php echo htmlspecialchars($notification['eventTitle']); ?></strong>.
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="event_id" value="<?php echo $notification['event_id']; ?>">
                                                    <input type="hidden" name="email" value="<?php echo $notification['user_email']; ?>">
                                                    <button type="submit" name="action" value="accept" style="background-color: green; color: white; border: none; padding: 5px 10px; cursor: pointer;">Accepter</button>
                                                    <button type="submit" name="action" value="reject" style="background-color: red; color: white; border: none; padding: 5px 10px; cursor: pointer;">Refuser</button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li>Aucune notification.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
        </div>
    </div>
</nav>

    <!-- Full video background -->
    <div class="video-background">
        <video autoplay muted loop>
            <source src="images\Gestion d'événements et de congrès.mp4" type="video/mp4">
            Votre navigateur ne supporte pas la vidéo.
        </video>
    </div>

    <!-- Card Section -->
    <section class="container card-section" id="events-type">
    <div class="row text-center" id="events-section"> <!-- Identifiant pour la section -->
        <div class="col-md-3"> <!-- Identifiant pour Événements en personne -->
            <a href="#" class="card p-4 shadow border-0">
                <i class="fas fa-home fa-3x mb-3" id="events-in-person"></i>
                <h3>Événements en personne</h3>
                <p class="type">Simplifiez l'enregistrement à l'événement, concevez et imprimez des badges, facilitez le réseautage et la capture de prospects, et bien plus encore !</p>
            </a>
        </div>
            <div class="col-md-3">
                <a href="#" class="card p-4 shadow border-0">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h3>Événements hybrides</h3>
                    <p class="type">Simplifiez l'enregistrement à l'événement, concevez et imprimez des badges, facilitez le réseautage et la capture de prospects, et bien plus encore !</p>
                </a>
            </div>
            <div class="col-md-3">
              <a href="#" class="card p-4 shadow border-0">
                  <i class="fas fa-phone fa-3x mb-3"></i>
                  <h3>Conférences</h3>
                    <p class="type">Créer une communauté interconnectée où les participants peuvent librement partager leurs connaissances et étendre leurs réseaux par le biais d'interactions en personne.</p>
              </a>
          </div>
            <div class="col-md-3">
                <a href="#" class="card p-4 shadow border-0">
                    <i class="fas fa-cogs fa-3x mb-3"></i>
                    <h3>Analyse des événement</h3>
                    <p class="type">Voir les feedbacks!</p>
                </a>
            </div>
            
        </div>
    </section>

    <section class="partners-section">
        <div class="partners-scroll">
          <div class="partner-logo"><img src="images\partenaire1.jpeg" alt="Partenaire 1"></div>
          <div class="partner-logo"><img src="images\partenaire2.png" alt="Partenaire 2"></div>
          <div class="partner-logo"><img src="images\partenaire3.webp" alt="Partenaire 3"></div>
          <div class="partner-logo"><img src="images\partenaire4.png" alt="Partenaire 4"></div>
          <div class="partner-logo"><img src="images\partenaire5.png" alt="Partenaire 5"></div>
          <!-- Répétez les logos autant de fois que nécessaire -->
        </div>
      </section>

    <section class="content-section">
        <div class="text-content">
          <h2>Engageons votre audience en dynamisant votre événement</h2>
          <p>
            Nous vous aidons à récolter les fruits de l’intelligence collective grâce aux fonctionnalités avancées de Quiz, Sondages en direct, Questionnaires de satisfaction et Nuages de mots.
          </p>
          <p>
            De plus, vous encouragez la participation permanente en utilisant le système de questions en direct et en permettant ainsi à tous les participants de s’exprimer.
          </p>
        </div>
        <div class="image-content">
          <img src="images\engagagement-evenement.png" alt="Description de l'image">
        </div>
      </section>

      <section class="content-section"  style="background-color: #cc98be;">
        <div class="image-content">
          <img src="images\data-evenement.png" alt="Description de l'autre image">
        </div>
        <div class="text-content">
        
          <h2>Optimisez vos événements avec nos outils interactifs</h2>
          <p>
            Grâce à nos solutions, vous pouvez créer une expérience dynamique et engageante pour vos participants. Offrez-leur la possibilité de participer activement grâce à des outils de sondage et de feedback instantané.
          </p>
          <p>
            Cela vous permet non seulement de recueillir des informations précieuses, mais aussi de garder votre public impliqué et intéressé tout au long de l'événement.
          </p>
        </div>
      </section>

      <section class="content-section">
        <div class="text-content">
          <h2>Centralisons le contenu de votre événement</h2>
          <p>
            Vous organisez un événement important ? Créez un véritable guide digital et regroupez facilement toutes les informations relatives à cet événement : agenda, liste de participants et d’orateurs, documents, informations utiles, visioconférence, streaming, fil d’actualités…
          </p>
          <p>
            Communiquez efficacement avec votre audience et mettez en avant votre image de marque avec un design à la hauteur de votre expertise.
          </p>
        </div>
        <div class="image-content">
          <img src="images\centraliser.png" alt="Description de l'autre image">
        </div>
      </section>

<section class="container my-5" id="events">
    <h2 class="text-center mb-4">Événements recommandées</h2>
    <div class="row" id="events-container">
        <div class="row text-center">
            <?php if (empty($evenements)): ?>
                <p>Aucun événement disponible à afficher.</p>
            <?php else: ?>
                <?php foreach ($evenements as $event): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card p-4 shadow border-0" style="background-image: url('<?= htmlspecialchars($event['image']) ?>');">
                            <div class="card-body">
                                <h4 ><?= htmlspecialchars($event['eventTitle']) ?></h4>
                                <p><strong>Date :</strong> <?= htmlspecialchars($event['eventDate']) ?></p>
                                <p><strong>Heure :</strong> <?= htmlspecialchars($event['eventTime']) ?></p>
                                <p><strong>Lieu :</strong> <?= htmlspecialchars($event['lieu']) ?></p>
                                <p><strong>Participants :</strong> <?= htmlspecialchars($event['participants']) ?></p>
                                <p><strong>Organisé par :</strong> <?= htmlspecialchars($event['organisation']) ?></p>
                                <p><strong>Catégorie :</strong> <?= htmlspecialchars($event['categorie']) ?> | 
                                <strong>Genre :</strong> <?= htmlspecialchars($event['genre']) ?></p>
                                <p><strong>Description :</strong> <?= htmlspecialchars($event['description']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-section developer-info">
                <h3>Développeurs du Site</h3>
                <!-- Developer 1 -->
                <div class="developer">
                    <img src="images\imen.png" alt="Developer 1 Image" class="developer-img">
                    <div class="developer-details">
                        <h4>Imen BenAmar</h4>
                        <p>Email: <a href="mailto:imen.bnamar@gmail.com">imen.bnamar@gmail.com</a></p>
                        <p>Téléphone: +216 54479420</p>
                        <p>GitHub: <a href="https://github.com/ImenBenAmar" target="_blank">https://github.com/ImenBenAmar</a></p>
                        <p>Kaggle: <a href="https://www.kaggle.com/imenbenamar1" target="_blank">https://www.kaggle.com/imenbenamar1</a></p>
                    </div>
                </div>
                <!-- Add more developer sections as needed -->
            </div>
            
        </div>
        <div class="footer-section links">
            <a href="/terms">Terms</a> |
            <a href="/privacy">Privacy Policy</a> |
            <a href="/faq">FAQ</a>
        </div>
        <div class="footer-section copyright">
            <h5>© 2024 Event Pro</h5>
            <div class="footer-section social-icons">
                <a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="#" target="_blank"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
    </div>
</footer>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-0P5Q1A2Xl+bW1gL3+VHt+p+d8R2XlK5EqYI3F/S1ab9YivlgHdP9vMRy+NQWdfc0" crossorigin="anonymous"></script>
    <script>
        // Change navbar background on scroll
        window.onscroll = function () {
            var navbar = document.querySelector('.navbar');
            if (document.body.scrollTop > 50 || document.documentElement.scrollTop > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        };
       
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationsDropdown');
            dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
        }
   

    </script>
</body>

</html>
