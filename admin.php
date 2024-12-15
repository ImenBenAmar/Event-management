<?php
session_start();

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

// Vérification si l'admin est connecté
$adminNom = ""; // Initialisation de la variable par défaut

if (isset($_SESSION['user_id'])) {
    $adminId = $_SESSION['user_idd']; // Récupérer l'ID de l'admin connecté depuis la session

    // Récupérer les informations de l'admin
    $adminQuery = "SELECT firstname FROM admins WHERE id = $adminId";
    $resultAdmin = mysqli_query($connexion, $adminQuery);

    if ($resultAdmin && mysqli_num_rows($resultAdmin) > 0) {
        $admin = mysqli_fetch_assoc($resultAdmin);
        $adminNom = $admin['firstname']; // Récupérer le prénom de l'admin
    } else {
        echo "Admin non trouvé.";
    }
} else {
    echo "Aucun admin connecté.";
}

// Récupérer les données nécessaires
$evenementsQuery = "SELECT * FROM evenements";
$resultEvenements = mysqli_query($connexion, $evenementsQuery);
$evenements = mysqli_fetch_all($resultEvenements, MYSQLI_ASSOC);

$utilisateursQuery = "SELECT * FROM users";
$resultUtilisateurs = mysqli_query($connexion, $utilisateursQuery);
$utilisateurs = mysqli_fetch_all($resultUtilisateurs, MYSQLI_ASSOC);

$inscriptionsQuery = "SELECT eu.event_id, eu.email, eu.status, e.eventTitle
                      FROM event_user eu
                      JOIN evenements e ON eu.event_id = e.id";
$resultInscriptions = mysqli_query($connexion, $inscriptionsQuery);
$inscriptions = mysqli_fetch_all($resultInscriptions, MYSQLI_ASSOC);

// Statistiques
$nbEvenements = count($evenements);
$nbUtilisateurs = count($utilisateurs);
$nbInscriptions = count($inscriptions);

// Répartition des événements par statut
$statuts = ['confirmé' => 0, 'en attente' => 0, 'annulé' => 0];
foreach ($evenements as $event) {
    if ($event['statuts'] === 'approuvé') {
        $statuts['confirmé']++;
    } elseif ($event['statuts'] === 'en attente') {
        $statuts['en attente']++;
    } elseif ($event['statuts'] === 'annulé') {
        $statuts['annulé']++;
    }
}

// Statistiques des utilisateurs
$nbUsers = ['actif' => 0, 'inactif' => 0];
foreach ($utilisateurs as $user) {
    if ($user['status'] === 'actif') {
        $nbUsers['actif']++;
    } else {
        $nbUsers['inactif']++;
    }
}

// Gestion des actions sur les événements
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'approuvé') {
        mysqli_query($connexion, "UPDATE evenements SET statuts = 'approuvé' WHERE id = $id");
    } elseif ($_GET['action'] === 'annulé') {
        mysqli_query($connexion, "UPDATE evenements SET statuts = 'annulé' WHERE id = $id");
    } elseif ($_GET['action'] === 'delete') {
        mysqli_query($connexion, "DELETE FROM evenements WHERE id = $id");
    }
    header("Location: admin.php#evenements");    exit;
}

// Gestion des actions sur les utilisateurs
if (isset($_GET['deleteUser'])) {
    $userId = (int)$_GET['deleteUser'];
    mysqli_query($connexion, "DELETE FROM users WHERE id = $userId");
    header("Location: admin.php#utilisateurs");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Ajout de Chart.js -->
    <style>
/* Styles généraux */
body {
    font-family: Arial, sans-serif;
    background-color: #f5f5f5;
    margin: 0;
    padding: 0;
    color: #333;
}

/* Styles pour la Navbar */
.navbar {
    background-color: #4a148c;
    color: #fff;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
}

.navbar.scrolled {
    background: rgba(47, 9, 69, 0.9);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.navbar h1 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

.navbar .avatar {
    display: flex;
    align-items: center;
    gap: 10px;
}

.navbar .avatar img {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    object-fit: cover;
    border: 2px solid #fff;
}

.navbar button.logout {
    margin-right: 30px;
    background-color: #dc3545;
    margin-left: 10px;
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 10px 15px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.navbar button.logout:hover {
    background-color: #c82333;
}

/* Styles pour la Sidebar */
.sidebar {
    width: 190px;
    height: 100vh;
    background-color: #6a1b9a;
    color: #fff;
    position: fixed;
    top: 0; /* Fixe la sidebar en haut */
    left: 0;
    padding: 80px 20px 20px; /* Ajuste pour compenser la hauteur de la navbar */
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    z-index: 900; /* Pour s'assurer que la sidebar est derrière la navbar */
}

.sidebar a {
    display: block;
    color: #fff;
    margin: 10px 0;
    text-decoration: none;
    padding: 10px;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.sidebar a:hover {
    background-color: #8e24aa;
}

/* Styles pour le contenu principal */
.main-content {
    margin-left: 270px; /* Ajuste pour compenser la largeur de la sidebar */
    padding: 20px;
    padding-top: 100px; /* Ajuste pour compenser la hauteur de la navbar */
}

/* Styles pour les cartes */
.card {
    margin:20px 10px 10px 240px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Styles pour les tableaux */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

table th, table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

table th {
    background-color: #6a1b9a;
    color: #fff;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

/* Styles pour les boutons */
.card a {
    margin-right: 10px;
    padding: 8px 12px;
    text-decoration: none;
    color: white;
    background-color: mediumorchid;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

button.logout {
    background-color: #dc3545;
    font-weight: bold;
}

button.logout:hover {
    background-color: #c82333;
}

/* Styles pour la section de statistiques */
#stats {
    background: #f4f4f9;
    /* padding: 5px; */
    margin: 1%;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    color: #333;
    display: grid;
    gap: 20px;
}

/* Configuration pour les petits écrans */
@media (max-width: 767px) {
    #stats {
        grid-template-columns: 1fr;
    }
}

@media (min-width: 768px) {
    #stats {
        grid-template-columns: repeat(2, 1fr);
    }

    #stats h2 {
        grid-column: span 2;
    }

    #stats .stat-info {
        flex-direction: row;
        justify-content: space-around;
        grid-column: span 2;
    }
}

@media (min-width: 1024px) {
    #stats {
        grid-template-columns: repeat(3, 1fr);
    }

    #stats h2 {
        grid-column: span 3;
    }

    #stats .stat-info {
        justify-content: space-between;
        grid-column: span 3;
    }
}

#stats .stat-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

#stats p {
    font-size: 16px;
    color: #666;
    margin: 10px 0;
}

#stats .chart-card {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
}

#stats h2 {
    text-align: center;
    font-size: 24px;
    color: #555;
}

#stats h3 {
    font-size: 20px;
    color: #444;
    margin-bottom: 15px;
    text-align: center;
}

canvas {
    max-width: 100%;
    margin: 0 auto;
    display: block;
}
</style>
</head>
<body>
    <div class="navbar">
        <h1>Dashboard Admin</h1>
        <div class="avatar">
            <img src="https://static-00.iconduck.com/assets.00/user-avatar-icon-512x512-vufpcmdn.png" alt="Avatar">
            <span>Bonjour, <?= $adminNom ?></span>
            <span><form method="post" action="logout.php" style="display: inline;">
                            <button type="submit" class="logout">Deconnexion</button>
                        </form>
            </span>
        </div>
    </div>
    <div class="sidebar">
        <h2>Navigation</h2>
        <a href="#stats">Statistiques</a>
        <a href="#evenements">Événements</a>
        <a href="#utilisateurs">Utilisateurs</a>
        <a href="#inscriptions">Inscriptions</a>
    </div>
    <div class="main-content">
        <div id="stats" class="card">
            <h2>Statistiques</h2>
            <div class="stat-info">
                <p>Nombre d'inscriptions : <?= $nbInscriptions ?></p>
                <p>Nombre d'événements : <?= $nbEvenements ?></p>
                <p>Nombre d'utilisateurs : <?= $nbUtilisateurs ?></p>
            </div>

            <!-- Section des graphiques -->
            <div class="chart-card">
                <h3>Répartition des événements</h3>
                <canvas id="pieChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>Statistiques des utilisateurs</h3>
                <canvas id="barChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>Évolution des inscriptions</h3>
                <canvas id="lineChart"></canvas>
        </div>
    </div>

</div>

    </div>


        <!-- Liste des événements -->
        <div id="evenements" class="card">
            <h2>Liste des événements</h2>
            <table>
                <thead>
                    <tr>
                        <th>Événement</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evenements as $event): ?>
                        <tr>
                            <td><?= $event['eventTitle'] ?></td>
                            <td><?= $event['eventDate'] ?></td>
                            <td><?= $event['statuts'] ?></td>
                            <td>
                                <a href="?action=approuvé&id=<?= $event['id'] ?>">Approuvé</a> |
                                <a href="?action=annulé&id=<?= $event['id'] ?>">Annulé</a> |
                                <a href="?action=delete&id=<?= $event['id'] ?>">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Liste des utilisateurs -->
        <div id="utilisateurs" class="card">
            <h2>Liste des utilisateurs</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prenom</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $user): ?>
                        <tr>
                            <td><?= $user['firstname'] ?></td>
                            <td><?= $user['lastname'] ?></td>
                            <td><?= $user['email'] ?></td>
                            <td>
                                <a href="?deleteUser=<?= $user['id'] ?>">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Liste des inscriptions -->
        <div id="inscriptions" class="card">
            <h2>Liste des Inscriptions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Événement</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscriptions as $inscription): ?>
                        <tr>
                            <td><?= $inscription['email'] ?></td>
                            <td><?= $inscription['eventTitle'] ?></td>
                            <td><?= $inscription['status'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Graphique pour les événements
        var ctx = document.getElementById('pieChart').getContext('2d');
        var pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Confirmés', 'En attente', 'Annulés'],
                datasets: [{
                    data: [<?= $statuts['confirmé'] ?>, <?= $statuts['en attente'] ?>, <?= $statuts['annulé'] ?>],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                }]
            }
        });

        // Graphique pour les utilisateurs
        var ctx = document.getElementById('barChart').getContext('2d');
        var barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Actifs', 'Inactifs'],
                datasets: [{
                    data: [<?= $nbUsers['actif'] ?>, <?= $nbUsers['inactif'] ?>],
                    backgroundColor: ['#28a745', '#dc3545'],
                }]
            }
        });

        // Graphique pour l'évolution des inscriptions (exemple avec des données statiques)
        var ctx = document.getElementById('lineChart').getContext('2d');
        var lineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai'],
                datasets: [{
                    label: 'Inscriptions Mensuelles',
                    data: [10, 15, 13, 8, 20], // Remplacer avec des données réelles
                    borderColor: '#007bff',
                    fill: false
                }]
            }
        });
    </script>
</body>
</html>
