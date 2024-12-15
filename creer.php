<?php


// Connexion à la base de données
$server = 'localhost';
$user = 'root';
$password = '';
$base = 'tpweb';

// Connexion au serveur
$connexion = mysqli_connect($server, $user, $password);

// Vérifier la connexion
if (!$connexion) {
    die("Échec de la connexion : " . mysqli_connect_error());
}

// Sélectionner la base de données
if (!mysqli_select_db($connexion, $base)) {
    die("Sélection de la base de données échouée : " . mysqli_error($connexion));
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nom = mysqli_real_escape_string($connexion, $_POST['nom'] ?? '');
    $email = mysqli_real_escape_string($connexion, $_POST['email'] ?? '');
    $eventTitle = mysqli_real_escape_string($connexion, $_POST['eventTitle'] ?? '');
    $eventDate = mysqli_real_escape_string($connexion, $_POST['eventDate'] ?? '');
    $eventTime = mysqli_real_escape_string($connexion, $_POST['eventTime'] ?? '');
    $participants = (int) ($_POST['participants'] ?? 0);
    $lieu = mysqli_real_escape_string($connexion, $_POST['lieu'] ?? '');
    $organisation = mysqli_real_escape_string($connexion, $_POST['organisation'] ?? '');
    $categorie = mysqli_real_escape_string($connexion, $_POST['categorie'] ?? '');
    $genre = mysqli_real_escape_string($connexion, $_POST['genre'] ?? '');
    $description = mysqli_real_escape_string($connexion, $_POST['description'] ?? '');

    // Gestion de l'upload de l'image
    $image = $_FILES['image']['name'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($image);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $extensions_arr = array("jpg", "jpeg", "png", "gif");

    // Vérifier l'extension de l'image
    if (in_array($imageFileType, $extensions_arr)) {
        // Vérifier si le dossier de destination existe, sinon le créer
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        // Upload du fichier
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // Préparer la requête SQL d'insertion
            $sql = "INSERT INTO evenements 
                    (nom, email, eventTitle, eventDate, eventTime, participants, lieu, organisation, categorie, genre, description, image) 
                    VALUES ('$nom', '$email', '$eventTitle', '$eventDate', '$eventTime', $participants, '$lieu', '$organisation', '$categorie', '$genre', '$description', '$target_file')";

            // Exécuter la requête
            if (mysqli_query($connexion, $sql)) {
                echo "<script>
                        alert('Événement créé avec succès.');
                        window.location.href = 'aceuil.php'; // Remplacez par la page de redirection
                      </script>";
            } else {
                echo "Erreur : " . mysqli_error($connexion);
            }
        } else {
            echo "Erreur lors de l'upload de l'image.";
        }
    } else {
        echo "Extension de fichier non autorisée.";
    }
} else {
    echo "Méthode de requête invalide.";
}

// Fermer la connexion
mysqli_close($connexion);
?>
