"""
SolarShare - Repair time prediction.

Reads one incident as JSON on stdin and prints {"days", "min", "max"}.
The range comes from the spread of the 200 trees of the forest (20th-80th percentile).

Usage: echo '{"description": "...", ...}' | python ml/predict_repair.py
"""

import json
import os
import sys

import joblib
import numpy as np
import pandas as pd

MODEL_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "repair_time_model.joblib")
COLUMNS = ["description", "severity", "category", "action", "past_incidents", "past_maintenances", "photos"]


def main():
    incident = json.loads(sys.stdin.buffer.read().decode("utf-8").lstrip("﻿"))
    pipeline = joblib.load(MODEL_PATH)

    row = pd.DataFrame([{column: incident.get(column) for column in COLUMNS}])
    features = pipeline.named_steps["features"].transform(row)
    trees = np.array([tree.predict(features)[0] for tree in pipeline.named_steps["forest"].estimators_])

    days = max(1, int(round(float(trees.mean()))))
    low = max(1, int(np.floor(np.percentile(trees, 20))))
    high = max(days, int(np.ceil(np.percentile(trees, 80))))
    print(json.dumps({"days": days, "min": min(low, days), "max": high}))


if __name__ == "__main__":
    try:
        main()
    except Exception as error:  # Laravel falls back to averages
        print(json.dumps({"error": str(error)}))
        sys.exit(1)
