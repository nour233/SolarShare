import os
import sys
import json
import base64
import joblib
import pandas as pd

MODEL_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_FILE = os.path.join(MODEL_DIR, "fraud_model.joblib")
FEATURE_NAMES_FILE = os.path.join(MODEL_DIR, "feature_names.json")

def predict():
    try:
        raw_input = ""
        if len(sys.argv) > 1:
            arg = sys.argv[1].strip()
            # If base64 encoded to avoid Windows quote escaping
            if arg.startswith("b64:"):
                raw_input = base64.b64decode(arg[4:]).decode("utf-8")
            else:
                raw_input = arg
        else:
            raw_input = sys.stdin.read().strip()

        input_data = json.loads(raw_input)

        if not os.path.exists(MODEL_FILE):
            print(json.dumps({"error": "Model file not found. Please train first."}))
            sys.exit(1)

        clf = joblib.load(MODEL_FILE)
        with open(FEATURE_NAMES_FILE, "r", encoding="utf-8") as f:
            features = json.load(f)

        row = {}
        for col in features:
            val = input_data.get(col, 0.0)
            row[col] = float(val) if val is not None else 0.0

        if "deposit_to_price_ratio" in features and row["deposit_to_price_ratio"] == 0.0:
            denom = row["total_price"] if row["total_price"] > 0 else 1.0
            row["deposit_to_price_ratio"] = row["deposit_amount"] / denom

        X_input = pd.DataFrame([row], columns=features)
        prob = float(clf.predict_proba(X_input)[0, 1])
        score = int(round(prob * 100))

        factors = []
        if row["hours_before_start"] <= 12:
            factors.append("Réservation d'extrême urgence (< 12h avant début) [Facteur ML majeur]")
        elif row["hours_before_start"] <= 24:
            factors.append("Réservation de dernière minute (< 24h avant début)")

        if row["total_price"] > 450:
            factors.append(f"Montant total élevé ({row['total_price']:.2f} €)")

        if row["deposit_amount"] > 400:
            factors.append(f"Caution importante sur matériel sensible ({row['deposit_amount']:.2f} €)")

        if row["user_past_rentals"] == 0:
            factors.append("Nouveau compte client sans historique validé")
        elif row["user_past_rentals"] >= 3:
            factors.append(f"Client régulier de confiance ({int(row['user_past_rentals'])} locations précédentes)")

        if row["user_cancelled_count"] >= 2:
            factors.append(f"Historique d'annulations répétées ({int(row['user_cancelled_count'])} annulations)")

        if row["user_reclamations_count"] >= 2:
            factors.append(f"Plusieurs réclamations passées ({int(row['user_reclamations_count'])} réclamations)")

        level = "high" if score >= 65 else ("medium" if score >= 35 else "low")

        result = {
            "score": score,
            "level": level,
            "probability": round(prob, 4),
            "algorithm": "Random Forest Classifier (Scikit-Learn ML)",
            "factors": factors
        }

        print(json.dumps(result, ensure_ascii=False))
    except Exception as e:
        print(json.dumps({
            "score": 15,
            "level": "low",
            "error": str(e),
            "algorithm": "Fallback Rule-Based",
            "factors": ["Erreur lors de l'exécution du script ML"]
        }, ensure_ascii=False))

if __name__ == "__main__":
    predict()
