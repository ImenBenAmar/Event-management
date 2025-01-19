from flask import Flask, request, jsonify
import numpy as np
import pickle
from tensorflow.keras.models import load_model
from tensorflow.keras.preprocessing.sequence import pad_sequences
import mysql.connector
import logging

app = Flask(__name__)

# Configurer le logger
logging.basicConfig(level=logging.DEBUG)

# Charger le tokenizer
try:
    with open(r'model\tokenizer.pkl', 'rb') as file:
        tokenizer = pickle.load(file)
    logging.info("Tokenizer chargé avec succès")
except Exception as e:
    logging.error(f"Erreur lors du chargement du tokenizer: {e}")

# Charger le modèle
try:
    model = load_model(r'model\sentiment_analysis_model (4).h5')
    logging.info("Modèle chargé avec succès")
except Exception as e:
    logging.error(f"Erreur lors du chargement du modèle: {e}")

# Carte des sentiments
sentiment_map = {0: 'negative', 1: 'positive'}

# Fonction de prédiction
def predict_sentiment(texts, tokenizer, model, max_len=100):
    try:
        sequences = tokenizer.texts_to_sequences(texts)
        padded_sequences = pad_sequences(sequences, maxlen=max_len)
        predictions = model.predict(padded_sequences)
        predicted_labels = np.argmax(predictions, axis=1)
        return [sentiment_map[label] for label in predicted_labels]
    except Exception as e:
        logging.error(f"Erreur lors de la prédiction: {e}")
        return []

# Connexion à la base de données
def get_db_connection():
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root",  # Remplacez par votre utilisateur MySQL
            password="",  # Remplacez par votre mot de passe MySQL
            database="tpweb"
        )
        logging.info("Connexion à la base de données réussie")
        return conn
    except mysql.connector.Error as err:
        logging.error(f"Erreur de connexion à la base de données: {err}")
        return None

# Endpoint pour prédire et stocker
@app.route('/predict', methods=['POST'])
def predict_and_store():
    try:
        # Récupérer les données JSON envoyées
        data = request.get_json()
        logging.debug(f"Données reçues: {data}")
        text = data.get("comment")
        event_id = data.get("event_id")
        user_id = data.get("user_id")

        if not text or not event_id or not user_id:
            return jsonify({"error": "Les champs 'comment', 'event_id' et 'user_id' sont requis"}), 400

        # Prédire le sentiment
        prediction = predict_sentiment([text], tokenizer, model)[0]
        logging.debug(f"Prédiction: {prediction}")

        # Stocker dans la base de données
        conn = get_db_connection()
        if conn is None:
            return jsonify({"error": "Erreur de connexion à la base de données"}), 500
        cursor = conn.cursor()
        query = "INSERT INTO comments (event_id, user_id, content, predict) VALUES (%s, %s, %s, %s)"
        cursor.execute(query, (event_id, user_id, text, prediction))
        conn.commit()
        cursor.close()
        conn.close()

        return jsonify({"text": text, "prediction": prediction}), 200

    except Exception as e:
        logging.error(f"Erreur lors du traitement de la requête: {e}")
        return jsonify({"error": str(e)}), 500

# Lancer le serveur Flask
if __name__ == '__main__':
    app.run(debug=True)
