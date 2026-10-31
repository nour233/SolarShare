import os
import sys
import json
import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, roc_auc_score, confusion_matrix
import joblib

MODEL_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_FILE = os.path.join(MODEL_DIR, "fraud_model.joblib")
FEATURE_NAMES_FILE = os.path.join(MODEL_DIR, "feature_names.json")
METRICS_FILE = os.path.join(MODEL_DIR, "model_metrics.json")

def generate_synthetic_dataset(n_samples=3000, random_state=42):
    np.random.seed(random_state)

    duration_days = np.random.choice([1, 2, 3, 5, 7, 10, 14, 21, 30], size=n_samples, p=[0.25, 0.20, 0.15, 0.15, 0.10, 0.05, 0.05, 0.03, 0.02])
    daily_rate = np.random.uniform(20.0, 100.0, size=n_samples)
    total_price = np.round(duration_days * daily_rate, 2)
    deposit_amount = np.random.choice([100.0, 150.0, 250.0, 350.0, 500.0, 750.0], size=n_samples)
    hours_before_start = np.random.exponential(scale=36.0, size=n_samples)
    hours_before_start = np.clip(hours_before_start, 0.5, 336.0)
    user_past_rentals = np.random.poisson(lam=2.5, size=n_samples)
    user_cancelled_count = np.random.poisson(lam=0.4, size=n_samples)
    user_reclamations_count = np.random.poisson(lam=0.2, size=n_samples)
    deposit_to_price_ratio = deposit_amount / (total_price + 1e-5)

    risk_logits = (
        - 2.8
        + 1.8 * (hours_before_start < 12)
        + 1.0 * (hours_before_start < 24)
        + 1.2 * (total_price > 450)
        + 1.1 * (deposit_amount > 400)
        + 0.9 * (duration_days == 1) * (deposit_amount > 300)
        + 1.3 * (user_past_rentals == 0)
        - 1.4 * (user_past_rentals >= 3)
        + 1.5 * (user_cancelled_count >= 2)
        + 1.6 * (user_reclamations_count >= 2)
    )

    probs = 1.0 / (1.0 + np.exp(-risk_logits))
    labels = (np.random.rand(n_samples) < probs).astype(int)

    df = pd.DataFrame({
        "duration_days": duration_days,
        "total_price": total_price,
        "deposit_amount": deposit_amount,
        "hours_before_start": hours_before_start,
        "user_past_rentals": user_past_rentals,
        "user_cancelled_count": user_cancelled_count,
        "user_reclamations_count": user_reclamations_count,
        "deposit_to_price_ratio": deposit_to_price_ratio,
        "is_fraud": labels
    })

    return df

def train_and_save():
    print("Generating dataset...")
    df = generate_synthetic_dataset(n_samples=3000)

    feature_cols = [
        "duration_days",
        "total_price",
        "deposit_amount",
        "hours_before_start",
        "user_past_rentals",
        "user_cancelled_count",
        "user_reclamations_count",
        "deposit_to_price_ratio"
    ]

    X = df[feature_cols]
    y = df["is_fraud"]

    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)

    print(f"Training Random Forest on {len(X_train)} samples...")
    clf = RandomForestClassifier(
        n_estimators=100,
        max_depth=6,
        min_samples_split=5,
        random_state=42,
        class_weight="balanced"
    )
    clf.fit(X_train, y_train)

    y_pred = clf.predict(X_test)
    y_pred_proba = clf.predict_proba(X_test)[:, 1]

    acc = float(accuracy_score(y_test, y_pred))
    auc = float(roc_auc_score(y_test, y_pred_proba))
    prec = float(precision_score(y_test, y_pred))
    rec = float(recall_score(y_test, y_pred))
    f1 = float(f1_score(y_test, y_pred))
    cm = confusion_matrix(y_test, y_pred).tolist()

    # Feature importances
    feature_importances = {}
    for col, imp in zip(feature_cols, clf.feature_importances_):
        feature_importances[col] = round(float(imp) * 100, 2)
    # Sort descending
    feature_importances = dict(sorted(feature_importances.items(), key=lambda item: item[1], reverse=True))

    metrics_data = {
        "model_name": "Random Forest Classifier",
        "n_estimators": 100,
        "max_depth": 6,
        "accuracy": round(acc * 100, 2),
        "roc_auc": round(auc, 4),
        "precision": round(prec * 100, 2),
        "recall": round(rec * 100, 2),
        "f1_score": round(f1, 4),
        "test_samples": len(y_test),
        "train_samples": len(X_train),
        "confusion_matrix": {
            "true_negatives": cm[0][0],
            "false_positives": cm[0][1],
            "false_negatives": cm[1][0],
            "true_positives": cm[1][1]
        },
        "feature_importances": feature_importances,
        "trained_at": pd.Timestamp.now().strftime("%Y-%m-%d %H:%M:%S")
    }

    joblib.dump(clf, MODEL_FILE)
    with open(FEATURE_NAMES_FILE, "w", encoding="utf-8") as f:
        json.dump(feature_cols, f)

    with open(METRICS_FILE, "w", encoding="utf-8") as f:
        json.dump(metrics_data, f, indent=4)

    print("Model and metrics successfully saved to disk!")
    print(json.dumps(metrics_data, indent=2))

if __name__ == "__main__":
    train_and_save()
