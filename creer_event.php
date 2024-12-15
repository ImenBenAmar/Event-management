<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
$currentUserEmail = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

if (empty($currentUserEmail)) {
    // Afficher une alerte en HTML et rediriger vers auth.php après un délai
    echo "<script>alert('Vous devez être connecté pour voir vos événements.'); window.location.href = 'auth.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Découvrir nos Événements</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet"> 
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script> 
    <link rel="stylesheet" href="styles.css"> 
  </head> 
  <body> 
    <div class="lottie-background"> <dotlottie-player src="https://lottie.host/0c5ca5ea-35b2-4e33-a6bd-f8ef1172193c/28yZIKNeCQ.lottie" background="transparent" speed="1" loop autoplay></dotlottie-player>
    </div>
    <div class="container text-center">
      <h2>Choisissez un Type d'Événement</h2>

      <div class="btn-group mb-4" role="group">
          <button type="button" onclick="showForm('En Personne')" class="btn btn-outline-primary">
              <i class="fas fa-users"></i> En Personne
          </button>
          <button type="button" onclick="showForm('Hybride')" class="btn btn-outline-info">
              <i class="fas fa-share-alt"></i> Hybride
          </button>
          <button type="button" onclick="showForm('Conference')" class="btn btn-outline-success">
              <i class="fas fa-chalkboard-teacher"></i> Conférence
          </button>
      </div>

      <div id="message" class="alert alert-info d-none"></div>

      <div id="form-container" class="form-container">
          <form id="registrationForm" method="POST" action="creer.php" enctype="multipart/form-data">
              <input type="hidden" id="genre" name="genre">

              <div class="row mb-3">
                  <div class="col-md-6">
                      <label for="nom" class="form-label">Nom</label>
                      <input type="text" class="form-control" id="nom" name="nom" placeholder="Votre nom" required>
                  </div>
                  <div class="col-md-6">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" class="form-control" id="email" name="email" placeholder="Votre email" required>
                  </div>
              </div>

              <div class="row mb-3">
                  <div class="col-md-6">
                      <label for="eventTitle" class="form-label">Titre de l'événement</label>
                      <input type="text" class="form-control" id="eventTitle" name="eventTitle" placeholder="Nom de l'événement" required>
                  </div>
                  <div class="col-md-6">
                      <label for="eventDate" class="form-label">Date</label>
                      <input type="date" class="form-control" id="eventDate" name="eventDate" required>
                  </div>
              </div>

              <div class="row mb-3">
                  <div class="col-md-6">
                      <label for="eventTime" class="form-label">Heure</label>
                      <input type="time" class="form-control" id="eventTime" name="eventTime" required>
                  </div>
                  <div class="col-md-6">
                      <label for="participants" class="form-label">Nombre de Participants</label>
                      <input type="number" class="form-control" id="participants" name="participants" min="1" required>
                  </div>
              </div>

              <div class="row mb-3">
                  <div class="col-md-6">
                      <label for="lieu" class="form-label">Lieu</label>
                      <input type="text" class="form-control" id="lieu" name="lieu" required>
                  </div>
                  <div class="col-md-6">
                      <label for="organisation" class="form-label">Organisation</label>
                      <input type="text" class="form-control" id="organisation" name="organisation" required>
                  </div>
              </div>

              <div class="row mb-3">
                  <div class="col-md-6">
                      <label for="categorie" class="form-label">Catégorie de l'événement</label>
                      <select class="form-select" id="categorie" name="categorie" required>
                          <option value="Business">Business</option>
                          <option value="Éducation">Éducation</option>
                          <option value="Plaisir">Plaisir</option>
                      </select>
                  </div>
                  <div class="col-md-6">
                      <label for="description" class="form-label">Description de l'événement</label>
                      <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                  </div>
              </div>

              <div class="row mb-3">
                  <div class="col-md-12">
                      <label for="image" class="form-label">Image de l'événement</label>
                      <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                  </div>
              </div>
              <button type="submit" class="btn btn-primary">Créer l'Événement</button>
          </form>
      </div>
    </div>

    <script>
        function showForm(type) {
            document.getElementById('message').classList.remove('d-none');
            document.getElementById('message').innerText = "Vous avez choisi : " + type + " ! Remplissez le formulaire ci-dessous.";
            document.getElementById('form-container').style.display = 'block';
            document.getElementById('genre').value = type;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
