"""
SolarShare - Repair time prediction (maintenance module).

Trains a Random Forest Regressor that predicts how many days a repair will take
from the incident report: the words of the description (TF-IDF), the severity,
the equipment category, the intervention type and the equipment history.

Data: a simulated history of past repairs, plus the real repairs exported by
`php artisan repair:train` (ml/data/repair_history_real.csv) when there are some.

Usage: python ml/train_repair_model.py
Outputs: ml/repair_time_model.joblib and ml/repair_metrics.json
"""

import json
import os
from datetime import datetime

import joblib
import numpy as np
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestRegressor
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import cross_val_score, train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(BASE_DIR, "repair_time_model.joblib")
METRICS_PATH = os.path.join(BASE_DIR, "repair_metrics.json")
REAL_DATA_PATH = os.path.join(BASE_DIR, "data", "repair_history_real.csv")

TEXT = "description"
CATEGORICAL = ["severity", "category", "action"]
NUMERIC = ["past_incidents", "past_maintenances", "photos"]
TARGET = "days"

PANEL, BATTERY, WIND = "Panneaux solaires", "Batteries", "Petites éoliennes"

# Typical problems: phrases a client would write, the usual intervention,
# a base repair time in days and how severe it usually is (mineur, moyen, grave).
PROBLEMS = [
    {"cats": [PANEL], "action": "nettoyage", "base": 1, "sev": [.7, .3, 0], "phrases": [
        "le panneau est couvert de poussière", "beaucoup de sable sur la surface", "des fientes d'oiseaux sur le panneau",
        "le panneau est très sale", "des feuilles collées sur les cellules"]},
    {"cats": [PANEL], "action": "reparation", "base": 6, "sev": [0, .4, .6], "phrases": [
        "le verre est fissuré", "une fissure sur une cellule", "impact de grêle sur le verre",
        "le cadre est tordu", "le verre est cassé"]},
    {"cats": [PANEL], "action": "inspection", "base": 2, "sev": [.3, .6, .1], "phrases": [
        "le panneau produit beaucoup moins", "baisse de puissance importante",
        "rendement faible même en plein soleil", "la production a chuté"]},
    {"cats": [PANEL, WIND], "action": "reparation", "base": 3, "sev": [0, .5, .5], "phrases": [
        "le connecteur MC4 est abîmé", "le câble est arraché", "un fil est dénudé", "la boîte de jonction est ouverte"]},
    {"cats": [PANEL], "action": "reparation", "base": 7, "sev": [0, .1, .9], "phrases": [
        "odeur de brûlé près de la boîte de jonction", "des traces de brûlure sur une cellule", "de la fumée est sortie du câble"]},
    {"cats": [BATTERY], "action": "reparation", "base": 7, "sev": [0, 0, 1], "phrases": [
        "la batterie est gonflée", "le boîtier de la batterie est déformé", "la batterie a gonflé et chauffe"]},
    {"cats": [BATTERY], "action": "reparation", "base": 4, "sev": [0, .6, .4], "phrases": [
        "la batterie ne charge plus", "la batterie reste bloquée à 0%", "le voyant de charge ne s'allume pas"]},
    {"cats": [BATTERY], "action": "inspection", "base": 3, "sev": [.4, .6, 0], "phrases": [
        "la batterie se décharge très vite", "l'autonomie a beaucoup baissé", "elle ne tient plus la charge"]},
    {"cats": [BATTERY], "action": "reparation", "base": 3, "sev": [.2, .7, .1], "phrases": [
        "l'écran ne s'allume plus", "un code erreur s'affiche", "les boutons ne répondent plus"]},
    {"cats": [BATTERY], "action": "reparation", "base": 6, "sev": [0, .2, .8], "phrases": [
        "la batterie chauffe anormalement", "odeur de brûlé pendant la charge", "de la fumée pendant la charge"]},
    {"cats": [BATTERY], "action": "reparation", "base": 2, "sev": [.5, .5, 0], "phrases": [
        "la prise USB ne marche plus", "le port de charge est cassé", "la sortie 220V ne fonctionne plus"]},
    {"cats": [BATTERY, WIND], "action": "nettoyage", "base": 1, "sev": [.8, .2, 0], "phrases": [
        "l'appareil est sale", "de la poussière dans les aérations", "l'équipement est encrassé"]},
    {"cats": [WIND], "action": "reparation", "base": 8, "sev": [0, .2, .8], "phrases": [
        "une pale est cassée", "une pale est fissurée", "il manque un morceau de pale"]},
    {"cats": [WIND], "action": "inspection", "base": 3, "sev": [.2, .7, .1], "phrases": [
        "l'éolienne fait un bruit anormal", "des vibrations très fortes", "un grincement quand elle tourne"]},
    {"cats": [WIND], "action": "reparation", "base": 5, "sev": [0, .6, .4], "phrases": [
        "l'éolienne ne tourne plus", "le rotor est bloqué", "elle tourne mais ne produit rien"]},
    {"cats": [WIND], "action": "reparation", "base": 6, "sev": [0, .3, .7], "phrases": [
        "le mât penche", "le mât est tordu après la tempête", "les haubans sont détendus"]},
    {"cats": [PANEL, BATTERY, WIND], "action": "reparation", "base": 4, "sev": [0, .6, .4], "phrases": [
        "infiltration d'eau dans le boîtier", "de la corrosion sur les bornes", "de l'humidité dans le boîtier"]},
]

OPENERS = ["", "", "Bonjour,", "Bonjour, je vous signale un problème :", "Problème :", "Attention,"]
CONTEXTS = ["", "", "depuis l'orage", "après le transport", "depuis ce matin", "depuis trois jours",
            "après la tempête", "depuis la dernière location", "pendant l'utilisation"]
ENDINGS = ["", "", "Merci de vérifier.", "Pouvez-vous intervenir rapidement ?", "Je n'ose plus l'utiliser.",
           "C'est urgent.", "Merci d'avance."]
SEVERITIES = ["mineur", "moyen", "grave"]
CATEGORY_FACTOR = {PANEL: 1.0, BATTERY: 1.15, WIND: 1.4}
SEVERITY_DAYS = {"mineur": -0.5, "moyen": 0.5, "grave": 1.5}
ACTIONS = ["nettoyage", "reparation", "inspection"]
# Common French words that carry no meaning for the repair (ignored by TF-IDF).
STOP_WORDS = ["a", "au", "aux", "avec", "c", "ce", "d", "dans", "de", "des", "du", "elle", "en", "est", "et", "il", "j", "je",
              "l", "la", "le", "les", "me", "mais", "n", "ne", "on", "ou", "par", "pas", "pour", "qu", "que", "s", "se", "sur",
              "un", "une", "vous", "bonjour", "merci", "probleme", "signale"]


def generate_synthetic_dataset(n_samples=1500, random_state=42):
    """Simulated repair history. Repair time depends on the problem, the category,
    the severity and the equipment history, plus random noise (real life is noisy)."""
    rng = np.random.default_rng(random_state)
    rows = []
    for _ in range(n_samples):
        category = rng.choice([PANEL, BATTERY, WIND], p=[.45, .35, .20])
        options = [p for p in PROBLEMS if category in p["cats"]]
        problems = [options[rng.integers(len(options))]]
        if rng.random() < .15:  # sometimes two problems at once
            problems.append(options[rng.integers(len(options))])

        main = max(problems, key=lambda p: p["base"])
        severity = SEVERITIES[rng.choice(3, p=main["sev"])]
        action = main["action"] if rng.random() < .9 else ACTIONS[rng.integers(3)]
        past_incidents = int(rng.poisson(1.0))
        past_maintenances = int(rng.poisson(2.0))
        photos = int(rng.choice(5, p=[.35, .3, .2, .1, .05]))

        phrases = [p["phrases"][rng.integers(len(p["phrases"]))] for p in problems]
        text = " ".join(filter(None, [
            OPENERS[rng.integers(len(OPENERS))],
            " et ".join(phrases),
            CONTEXTS[rng.integers(len(CONTEXTS))],
        ])) + ". " + ENDINGS[rng.integers(len(ENDINGS))]

        days = (main["base"] * CATEGORY_FACTOR[category]
                + SEVERITY_DAYS[severity]
                + (1 if len(problems) > 1 else 0)
                + 0.4 * past_incidents + 0.15 * past_maintenances
                - (0.4 if photos > 0 else 0)
                + rng.normal(0, 0.9 + 0.1 * main["base"]))

        rows.append({TEXT: text.strip(), "severity": severity, "category": category, "action": action,
                     "past_incidents": past_incidents, "past_maintenances": past_maintenances,
                     "photos": photos, TARGET: int(np.clip(round(days), 1, 21))})
    return pd.DataFrame(rows)


def load_dataset():
    synthetic = generate_synthetic_dataset()
    synthetic["origin"] = "simulated"
    if os.path.exists(REAL_DATA_PATH):
        real = pd.read_csv(REAL_DATA_PATH)
        if not real.empty:
            real["origin"] = "real"
            return pd.concat([synthetic, real[synthetic.columns]], ignore_index=True)
    return synthetic


def build_pipeline():
    features = ColumnTransformer([
        ("words", TfidfVectorizer(strip_accents="unicode", lowercase=True, stop_words=STOP_WORDS, ngram_range=(1, 2), min_df=3, max_features=600), TEXT),
        ("cat", OneHotEncoder(handle_unknown="ignore"), CATEGORICAL),
        ("num", "passthrough", NUMERIC),
    ])
    model = RandomForestRegressor(n_estimators=200, min_samples_leaf=3, random_state=42, n_jobs=-1)
    return Pipeline([("features", features), ("forest", model)])


def feature_importances(pipeline):
    names = pipeline.named_steps["features"].get_feature_names_out()
    values = pipeline.named_steps["forest"].feature_importances_
    groups = {"Mots de la description": 0.0, "Gravité": 0.0, "Catégorie": 0.0, "Type d'intervention": 0.0,
              "Incidents passés": 0.0, "Maintenances passées": 0.0, "Nombre de photos": 0.0}
    words = {}
    for name, value in zip(names, values):
        kind, feature = name.split("__", 1)
        if kind == "words":
            groups["Mots de la description"] += value
            words[feature] = words.get(feature, 0) + value
        elif kind == "cat":
            key = feature.split("_", 1)[0]
            groups[{"severity": "Gravité", "category": "Catégorie", "action": "Type d'intervention"}[key]] += value
        else:
            groups[{"past_incidents": "Incidents passés", "past_maintenances": "Maintenances passées", "photos": "Nombre de photos"}[feature]] += value

    top_words = sorted(words.items(), key=lambda item: item[1], reverse=True)[:10]
    return ({k: round(float(v) * 100, 2) for k, v in sorted(groups.items(), key=lambda item: item[1], reverse=True)},
            {k: round(float(v) * 100, 2) for k, v in top_words})


def train_and_save():
    data = load_dataset()
    X, y = data[[TEXT] + CATEGORICAL + NUMERIC], data[TARGET]
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

    pipeline = build_pipeline()
    cv_mae = -cross_val_score(build_pipeline(), X_train, y_train, cv=5, scoring="neg_mean_absolute_error")
    pipeline.fit(X_train, y_train)
    predicted = pipeline.predict(X_test)
    errors = np.abs(predicted - y_test.to_numpy())

    # Baselines: what you get without machine learning.
    baseline_global = np.full(len(y_test), y_train.mean())
    action_means = y_train.groupby(X_train["action"]).mean()
    baseline_action = X_test["action"].map(action_means).fillna(y_train.mean()).to_numpy()
    mae = mean_absolute_error(y_test, predicted)
    mae_action = mean_absolute_error(y_test, baseline_action)

    groups, top_words = feature_importances(pipeline)
    examples = X_test.assign(real=y_test, predicted=np.round(predicted, 1)).head(8)

    metrics = {
        "model_name": "Random Forest Regressor + TF-IDF",
        "n_estimators": 200,
        "min_samples_leaf": 3,
        "mae": round(float(mae), 2),
        "rmse": round(float(np.sqrt(mean_squared_error(y_test, predicted))), 2),
        "r2": round(float(r2_score(y_test, predicted)), 3),
        "within_1_day": round(float((errors <= 1).mean() * 100), 1),
        "within_2_days": round(float((errors <= 2).mean() * 100), 1),
        "cv_mae_mean": round(float(cv_mae.mean()), 2),
        "cv_mae_std": round(float(cv_mae.std()), 2),
        "baseline_global_mae": round(float(mean_absolute_error(y_test, baseline_global)), 2),
        "baseline_action_mae": round(float(mae_action), 2),
        "improvement_vs_action_baseline": round(float((mae_action - mae) / mae_action * 100), 1),
        "action_average_days": {k: round(float(v), 1) for k, v in action_means.items()},
        "feature_groups": groups,
        "top_words": top_words,
        "examples": [{"description": r[TEXT], "category": r["category"], "severity": r["severity"],
                      "real": int(r["real"]), "predicted": float(r["predicted"])} for _, r in examples.iterrows()],
        "train_samples": int(len(X_train)),
        "test_samples": int(len(X_test)),
        "simulated_samples": int((data["origin"] == "simulated").sum()),
        "real_samples": int((data["origin"] == "real").sum()),
        "trained_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    }

    joblib.dump(pipeline, MODEL_PATH, compress=3)
    with open(METRICS_PATH, "w", encoding="utf-8") as file:
        json.dump(metrics, file, indent=4, ensure_ascii=False)

    print(f"Model saved to {MODEL_PATH}")
    print(f"MAE {metrics['mae']} days | RMSE {metrics['rmse']} | R2 {metrics['r2']} | "
          f"within 1 day {metrics['within_1_day']}% | baseline (by action) MAE {metrics['baseline_action_mae']} "
          f"| improvement {metrics['improvement_vs_action_baseline']}%")
    print(f"Samples: {metrics['simulated_samples']} simulated + {metrics['real_samples']} real")


if __name__ == "__main__":
    train_and_save()
