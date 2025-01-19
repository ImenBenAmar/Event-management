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

// Fonction pour calculer le pourcentage de stars
function calculateStars($positiveReviews, $totalReviews) {
    if ($totalReviews == 0) return 0;
    return ($positiveReviews / $totalReviews) * 5;
}

// Initialiser la requête de base pour afficher tous les événements approuvés
$currentUserEmail = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
$evenementsQuery = "
    SELECT e.id, e.eventTitle AS name, e.image,
           COALESCE(SUM(CASE WHEN c.predict = 'positive' THEN 1 ELSE 0 END), 0) AS positive_reviews,
           COALESCE(SUM(CASE WHEN c.predict = 'negative' THEN 1 ELSE 0 END), 0) AS negative_reviews,
           COUNT(c.id) AS total_reviews
    FROM evenements e
    LEFT JOIN comments c ON e.id = c.event_id
    WHERE e.statuts = 'approuvé'
    GROUP BY e.id, e.eventTitle, e.image
";
$result = mysqli_query($connection, $evenementsQuery);
$events = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avis des Événements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css"> <!-- Link to the CSS file -->
</head>
<body>
    <!-- Page content -->
    <div class="events-list">
        <h2>Événements</h2>
        <?php foreach ($events as $event): ?>
            <?php
            $positiveReviews = $event['positive_reviews'];
            $totalReviews = $event['total_reviews'];
            $stars = calculateStars($positiveReviews, $totalReviews);
            $starPercentage = ($stars / 5) * 100;
            $starColor = 'yellow';
            if ($totalReviews > 0) {
                if ($positiveReviews > ($totalReviews / 2)) {
                    $starColor = 'green';
                } else if ($positiveReviews < ($totalReviews / 2)) {
                    $starColor = 'red';
                }
            }
            ?>
            <div class="event-card" data-event-id="<?= $event['id'] ?>" style="background-image: url('<?= htmlspecialchars($event['image']) ?>');">
                <div class="event-card-content">
                    <h3><?= htmlspecialchars($event['name']) ?></h3>
                    <div class="star-rating" data-star-color="<?= $starColor ?>" style="color: <?= $starColor ?>;">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <div class="star <?= $i < $stars ? 'filled' : '' ?>"></div>
                        <?php endfor; ?>
                    </div>
                    <p><strong><?= number_format($starPercentage, 2) ?>%</strong></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="event-reviews">
        <h2>Avis des utilisateurs</h2>
        <div class="reviews-container"></div>
        <form id="comment-form">
            <textarea name="comment" placeholder="Écrire un commentaire..."></textarea>
            <button type="submit">Envoyer</button>
        </form>
    </div>

    <script>
    document.querySelectorAll('.event-card').forEach(event => {
        event.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            fetch(`get_reviews.php?event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Erreur:', data.error);
                        alert('Erreur lors de la récupération des avis.');
                        return;
                    }
                    const reviewsContainer = document.querySelector('.reviews-container');
                    reviewsContainer.innerHTML = '';
                    let positiveReviews = 0;
                    let negativeReviews = 0;
                    data.reviews.forEach(review => {
                        const reviewElement = document.createElement('div');
                        reviewElement.classList.add('review');
                        reviewElement.innerHTML = `
                            <div class="review-header">
                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ7vB-49_BT-dirwttYZaeE_VByjlQ3raVJZg&s" alt="Avatar" class="review-avatar">
                                <span class="review-author">${review.firstname} ${review.lastname}</span>
                            </div>
                            <p>${review.content}</p>
                            <span class="review-prediction ${review.predict == 'positive' ? 'positive' : 'negative'}">
                                ${review.predict == 'positive' ? 'Positive' : 'Negative'}
                            </span>`;
                        reviewsContainer.appendChild(reviewElement);
                        if (review.predict == 'positive') positiveReviews++;
                        if (review.predict == 'negative') negativeReviews++;
                    });
                    const totalReviews = data.reviews.length;
                    const stars = (positiveReviews / totalReviews) * 5;
                    const starPercentage = (stars / 5) * 100;
                    const starRating = document.querySelector('.star-rating');
                    starRating.innerHTML = '';
                    let starColor = 'yellow';
                    if (totalReviews > 0) {
                        if (positiveReviews > negativeReviews) {
                            starColor = 'green';
                        } else if (negativeReviews > positiveReviews) {
                            starColor = 'red';
                        }
                    }
                    starRating.style.color = starColor;
                    starRating.setAttribute('data-star-color', starColor);
                    for (let i = 0; i < 5; i++) {
                        const star = document.createElement('div');
                        star.classList.add('star');
                        if (i < stars) {
                            star.classList.add('filled');
                        }
                        starRating.appendChild(star);
                    }
                    // Show the comment form for the selected event
                    document.getElementById('comment-form').style.display = 'block';
                    document.getElementById('comment-form').dataset.eventId = eventId;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la récupération des avis.');
                });
        });
    });

    document.getElementById('comment-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const comment = this.querySelector('textarea[name="comment"]').value;
        const eventId = this.dataset.eventId;
        const userId = <?= json_encode($_SESSION['user_idd']) ?>; // Get user ID from session

        console.log({ event_id: eventId, user_id: userId, comment: comment }); // Log the data

        fetch('add_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ event_id: eventId, user_id: userId, comment: comment })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Commentaire envoyé avec succès');
                // Recharger la page pour actualiser les commentaires et les étoiles
                location.reload();
            } else {
                console.error('Erreur lors de l’envoi du commentaire:', data.error);
                alert(data.error || 'Une erreur est survenue.');
            }
        })
        .catch(error => {
            console.error('Erreur lors de l’envoi du commentaire:', error);
            alert('Erreur lors de l’envoi du commentaire.');
        });
    });

    // Ensure star colors remain intact when switching between events
    document.querySelectorAll('.event-card').forEach(event => {
        event.addEventListener('click', function() {
            document.querySelectorAll('.star-rating').forEach(starRating => {
                const starColor = starRating.getAttribute('data-star-color');
                starRating.style.color = starColor;
            });
        });
    });
    </script>
</body>
</html>

<?php
?>
