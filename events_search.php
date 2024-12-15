<?php
// Démarrer la session
session_start();
// Vérifier l'email de l'utilisateur depuis la session
$currentUserEmail = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
// Vérifier si l'utilisateur est connecté

if (empty($currentUserEmail)) {
    // Afficher une alerte en HTML et rediriger vers auth.php après un délai
    echo "<script>alert('Vous devez être connecté pour voir nos événements.'); window.location.href = 'auth.php';</script>";
    exit;
}


// Connexion à la base de données
$server = 'localhost';
$user = 'root';
$password = '';
$base = 'tpweb';

$connexion = mysqli_connect($server, $user, $password);

if (!$connexion) {
    die("Échec de la connexion : " . mysqli_connect_error());
}

if (!mysqli_select_db($connexion, $base)) {
    die("Sélection de la base de données échouée : " . mysqli_error($connexion));
}

// Initialiser la requête de base pour afficher tous les événements approuvés
$evenementsQuery = "
    SELECT * 
    FROM evenements e
    WHERE statuts = 'approuvé' 
    AND NOT EXISTS (
        SELECT 1 
        FROM event_user eu
        WHERE eu.event_id = e.id 
        AND eu.email = '$currentUserEmail' 
    )
";

// Ajouter les filtres si appliqués
$categorieFiltre = isset($_GET['categorie']) ? $_GET['categorie'] : '';
$genreFiltre = isset($_GET['genre']) ? $_GET['genre'] : '';
$populariteFiltre = isset($_GET['popularite']) ? $_GET['popularite'] : '';

if ($categorieFiltre) {
    $evenementsQuery .= " AND categorie = '$categorieFiltre'";
}
if ($genreFiltre) {
    $evenementsQuery .= " AND genre = '$genreFiltre'";
}
if ($populariteFiltre) {
    $evenementsQuery .= " AND popularite >= 3"; // Filtrer par popularité >= 3
}

$resultEvenements = mysqli_query($connexion, $evenementsQuery);
$evenements = mysqli_fetch_all($resultEvenements, MYSQLI_ASSOC);


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Découvrir nos Événements</title>
    <style>
       body {
    background-color: #f7e9f2;
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
}
h3{
    color: purple;
    font-size: xx-large;
    position: relative;
    text-align: center;
  
}

h1 {
    color: #d63384;
    text-align: center;
    margin: 20px 0;
}

.filter-bar {
    text-align: center;
    margin: 20px 0;
}

.filter-bar select, .filter-bar button {
    padding: 10px;
    margin: 10px;
    border-radius: 5px;
    border: 1px solid #d63384;
    font-size: 16px;
}

.filter-bar button {
    background-color: #d63384;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.filter-bar button:hover {
    background-color: #a0196f;
}

.card-section {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    padding: 20px;
}

.card {
    background-size: cover;
    background-position: center;
    color: white;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    padding: 20px;
    margin: 15px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s;
    width: 300px;
    position: relative;
    overflow: hidden;
    position: relative;
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
    filter: blur(5px); /* Applique le flou */
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

.edit-icon {
    position: absolute;
    top: 10px;
    right: 10px;
    cursor: pointer;
    width: 10%;
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

/* Styles pour le modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.7); /* Background plus foncé pour plus d'effet */
    padding-top: 60px;
    z-index: 2;
}

.modal-content {
    background-color: #ffffff;
    margin: 5% auto;
    padding: 30px;
    border: 1px solid #ccc;
    width: 70%;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    transition: color 0.3s;
}

.close:hover,
.close:focus {
    color: #d63384;
    text-decoration: none;
    cursor: pointer;
}

button[type="submit"] {
    background-color: #d63384;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button[type="submit"]:hover {
    background-color: #a0196f;
}

.modal label {
    color: #d63384;
    font-weight: bold;
    margin-top: 10px;
}

.modal input[type="text"],
.modal input[type="date"],
.modal input[type="time"],
.modal input[type="number"],
.modal select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
    transition: border-color 0.3s;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}
.inscription-button {
    background-color: green;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
    
}


.inscription-button:hover {
    background-color: darkgreen;
}

.event-complet {
    color: red;
    font-weight: bold;
    margin-top: 10px;
}

</style>
</head>
<body>
    <h1>Découvrir nos Événements</h1>

    <!-- Barre de filtre -->
    <div class="filter-bar">
        <form method="GET" action="events_search.php">
            <select name="categorie">
                <option value="">Toutes les catégories</option>
                <option value="Éducation">Éducation</option>
                <option value="Business">Business</option>
                <option value="Plaisir">Plaisir</option>
                <!-- Ajouter d'autres options de catégorie si nécessaire -->
            </select>

            <select name="genre">
                <option value="">Tous les genres</option>
                <option value="En Personne">En Personne</option>
                <option value="Hybride">Hybride</option>
                <option value="Conférence">Conférence</option>
                <!-- Ajouter d'autres options de genre si nécessaire -->
            </select>
            <button type="submit">Filtrer</button>
            <!-- Filtrer par popularité -->
            <button type="submit" name="popularite" value="3">Popularité</button>
        </form>
    </div>

    <div class="card-section">
        <?php foreach ($evenements as $event): ?>
            <div class="card" id="event-<?= htmlspecialchars($event['id']) ?>" style="background-image: url('<?= htmlspecialchars($event['image']) ?>');">
                <div class="card-body">
                    <?php if ($event['email'] === $currentUserEmail): ?>
                        <!-- Icône de modification -->
                        <img src="https://cdn-icons-png.flaticon.com/512/6324/6324968.png" alt="Modifier" class="edit-icon" onclick="openModal('<?= $event['id'] ?>')">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($event['eventTitle']) ?></h3>
                    <p><strong>Date :</strong> <?= htmlspecialchars($event['eventDate']) ?></p>
                    <p><strong>Heure :</strong> <?= htmlspecialchars($event['eventTime']) ?></p>
                    <p><strong>Participants :</strong> <?= htmlspecialchars($event['participants']) ?></p>
                    <p><strong>Lieu :</strong> <?= htmlspecialchars($event['lieu']) ?></p>
                    <p><strong>Organisation :</strong> <?= htmlspecialchars($event['organisation']) ?></p>
                    <p><strong>Catégorie :</strong> <?= htmlspecialchars($event['categorie']) ?> </p>
                    <p><strong>Genre :</strong> <?= htmlspecialchars($event['genre']) ?></p>
                    <p><strong>Description :</strong> <?= htmlspecialchars($event['description']) ?></p>
                    <!-- Condition pour afficher bouton ou message -->
                    <?php if ($event['popularite'] < $event['participants']): ?>
                    <?php if ($event['email'] !== $currentUserEmail): // Vérifie si l'utilisateur n'est pas le créateur de l'événement ?>
                        <button class="inscription-button" onclick="inscrireEvent(<?= $event['id'] ?>)">Inscrire</button>
                    <?php else: ?>
                        <p class="event-complet">Vous êtes le créateur de cet événement</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="event-complet">Cet événement est déjà complet</p>
                <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Bouton de retour -->
    <button class="back-button" ><a href="aceuil.php">Retour</a></button>

    
    <!-- Modal pour modifier l'événement -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <form id="editForm" action="update_event.php" method="POST">
                <input type="hidden" name="id" id="eventId">
                <label for="eventTitle">Titre :</label>
                <input type="text" id="eventTitle" name="eventTitle" required><br>
                <label for="eventDate">Date :</label>
                <input type="date" id="eventDate" name="eventDate" required><br>
                <label for="eventTime">Heure :</label>
                <input type="time" id="eventTime" name="eventTime" required><br>
                <label for="participants">Participants :</label>
                <input type="number" id="participants" name="participants" required><br>
                <label for="lieu">Lieu :</label>
                <input type="text" id="lieu" name="lieu" required><br>
                <label for="organisation">Organisation :</label>
                <input type="text" id="organisation" name="organisation" required><br>
                <label for="categorie">Catégorie :</label>
                <input type="text" id="categorie" name="categorie" required><br>
                <label for="genre">Genre :</label>
                <input type="text" id="genre" name="genre" required><br>
                <label for="description">Description :</label> 
                <textarea id="description" name="description" rows="4" required></textarea>
                <button type="submit">Modifier</button>
            </form>
        </div>
    </div>

    <script>
        // Fonction pour ouvrir le modal et remplir le formulaire avec les informations de l'événement
        function openModal(eventId) {
            const eventRow = document.querySelector(`#event-${eventId} div`);
            document.getElementById('eventId').value = eventId;
            document.getElementById('eventTitle').value = eventRow.querySelector('h3').textContent;
            document.getElementById('eventDate').value = eventRow.querySelector('p:nth-of-type(1)').textContent.replace('Date : ', '').trim();
            document.getElementById('eventTime').value = eventRow.querySelector('p:nth-of-type(2)').textContent.replace('Heure : ', '').trim();
            document.getElementById('participants').value = eventRow.querySelector('p:nth-of-type(3)').textContent.replace('Participants : ', '').trim();
            document.getElementById('lieu').value = eventRow.querySelector('p:nth-of-type(4)').textContent.replace('Lieu : ', '').trim();
            document.getElementById('organisation').value = eventRow.querySelector('p:nth-of-type(5)').textContent.replace('Organisation : ', '').trim();
            document.getElementById('categorie').value = eventRow.querySelector('p:nth-of-type(6)').textContent.replace('Catégorie : ', '').trim();
            document.getElementById('genre').value = eventRow.querySelector('p:nth-of-type(7)').textContent.replace('Genre : ', '').trim();
            document.getElementById('description').value = eventRow.querySelector('p:nth-of-type(8)').textContent.replace('Description : ', '').trim();

            // Afficher le modal
            document.getElementById('modal').style.display = 'block';
        }

        // Fonction pour fermer le modal
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        // Fermer le modal en cliquant en dehors de la fenêtre de modal
        window.onclick = function(event) {
            if (event.target == document.getElementById('modal')) {
                closeModal();
            }
        }
        function inscrireEvent(eventId) {
        let userEmail = <?= json_encode($currentUserEmail) ?>; // Insérer l'email de l'utilisateur connecté

        if (userEmail) {
            // Créer un formulaire pour envoyer les données
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'inscrire_event.php';

            let eventIdField = document.createElement('input');
            eventIdField.type = 'hidden';
            eventIdField.name = 'event_id';
            eventIdField.value = eventId;
            form.appendChild(eventIdField);

            let userEmailField = document.createElement('input');
            userEmailField.type = 'hidden';
            userEmailField.name = 'user_email';
            userEmailField.value = userEmail;
            form.appendChild(userEmailField);

            document.body.appendChild(form);
            form.submit();
        } else {
            alert("Vous devez être connecté pour vous inscrire à un événement.");
        }
    }


    </script>
</body>
</html>
