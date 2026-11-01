#!/usr/bin/env python3
"""
RFM Client Segmentation for SolarShare
========================================
Calculates Recency, Frequency, Monetary scores for each user
and classifies them into segments: VIP, Regular, At-Risk, Lost, New.

Usage:
    python rfm_segmentation.py <base64-encoded-json>

Input (JSON via base64):
    {
        "users": [
            {
                "id": 1,
                "name": "John",
                "rental_count": 5,
                "total_spent": 1500.00,
                "days_since_last_rental": 10,
                "avg_payment_delay": 2,
                "incident_count": 0,
                "account_age_days": 365
            }
        ]
    }

Output (JSON):
    {
        "segments": {
            "1": {
                "segment": "vip",
                "rfm_recency": 5,
                "rfm_frequency": 4,
                "rfm_monetary": 5,
                "rfm_total": 4.67,
                "confidence": 0.92
            }
        },
        "distribution": {
            "vip": 1, "regular": 3, "at_risk": 1, "lost": 0, "new": 2
        },
        "metrics": { ... }
    }
"""

import sys
import json
import base64
import os
import numpy as np
from datetime import datetime

try:
    from sklearn.cluster import KMeans
    from sklearn.preprocessing import StandardScaler, MinMaxScaler
    HAS_SKLEARN = True
except ImportError:
    HAS_SKLEARN = False


def decode_input(raw_arg):
    """Decode base64-encoded JSON input."""
    if raw_arg.startswith('b64:'):
        raw_arg = raw_arg[4:]
    decoded = base64.b64decode(raw_arg).decode('utf-8')
    return json.loads(decoded)


def calculate_rfm_scores(users):
    """
    Calculate RFM scores (1-5) for each user using quintile-based scoring.
    For small datasets (<5 users), uses rule-based thresholds.
    """
    if not users:
        return {}

    results = {}

    # Extract raw RFM values
    user_ids = [u['id'] for u in users]
    recencies = [u.get('days_since_last_rental', 9999) for u in users]
    frequencies = [u.get('rental_count', 0) for u in users]
    monetaries = [u.get('total_spent', 0.0) for u in users]
    incidents = [u.get('incident_count', 0) for u in users]
    account_ages = [u.get('account_age_days', 1) for u in users]

    n_users = len(users)

    if n_users >= 5 and HAS_SKLEARN:
        # Use K-Means clustering for larger datasets
        results = _kmeans_segmentation(users, recencies, frequencies, monetaries, incidents)
    else:
        # Rule-based scoring for small datasets
        results = _rule_based_segmentation(users, recencies, frequencies, monetaries, incidents, account_ages)

    return results


def _score_quintile(values, reverse=False):
    """Score values into 1-5 quintiles. reverse=True means lower value = higher score (for recency)."""
    arr = np.array(values, dtype=float)
    if len(arr) < 5:
        # For very small arrays, use simple ranking
        sorted_unique = np.sort(np.unique(arr))
        scores = []
        for v in arr:
            if len(sorted_unique) == 1:
                scores.append(3)
            else:
                rank = np.searchsorted(sorted_unique, v)
                score = int(1 + (rank / (len(sorted_unique) - 1)) * 4)
                if reverse:
                    score = 6 - score
                scores.append(min(5, max(1, score)))
        return scores

    percentiles = np.percentile(arr, [20, 40, 60, 80])
    scores = []
    for v in arr:
        if v <= percentiles[0]:
            s = 1
        elif v <= percentiles[1]:
            s = 2
        elif v <= percentiles[2]:
            s = 3
        elif v <= percentiles[3]:
            s = 4
        else:
            s = 5
        if reverse:
            s = 6 - s
        scores.append(s)
    return scores


def _rule_based_segmentation(users, recencies, frequencies, monetaries, incidents, account_ages):
    """Rule-based RFM segmentation for small datasets."""
    results = {}

    # Thresholds (tuned for solar equipment rental)
    RECENCY_THRESHOLDS = [7, 30, 90, 180]       # days
    FREQUENCY_THRESHOLDS = [1, 3, 6, 10]          # number of rentals
    MONETARY_THRESHOLDS = [100, 500, 1500, 5000]   # total spent (DT/EUR)

    for i, user in enumerate(users):
        r_val = recencies[i]
        f_val = frequencies[i]
        m_val = monetaries[i]
        inc_val = incidents[i]
        age_days = account_ages[i]

        # Score Recency (5=recent, 1=long ago) — lower days = higher score
        if r_val == 9999 or f_val == 0:
            r_score = 1
        elif r_val <= RECENCY_THRESHOLDS[0]:
            r_score = 5
        elif r_val <= RECENCY_THRESHOLDS[1]:
            r_score = 4
        elif r_val <= RECENCY_THRESHOLDS[2]:
            r_score = 3
        elif r_val <= RECENCY_THRESHOLDS[3]:
            r_score = 2
        else:
            r_score = 1

        # Score Frequency (5=many, 1=few)
        if f_val >= FREQUENCY_THRESHOLDS[3]:
            f_score = 5
        elif f_val >= FREQUENCY_THRESHOLDS[2]:
            f_score = 4
        elif f_val >= FREQUENCY_THRESHOLDS[1]:
            f_score = 3
        elif f_val >= FREQUENCY_THRESHOLDS[0]:
            f_score = 2
        else:
            f_score = 1

        # Score Monetary (5=high spender, 1=low)
        if m_val >= MONETARY_THRESHOLDS[3]:
            m_score = 5
        elif m_val >= MONETARY_THRESHOLDS[2]:
            m_score = 4
        elif m_val >= MONETARY_THRESHOLDS[1]:
            m_score = 3
        elif m_val >= MONETARY_THRESHOLDS[0]:
            m_score = 2
        else:
            m_score = 1

        # Incident penalty
        penalty = min(inc_val * 0.3, 1.5)

        # Weighted total: R(35%) + F(35%) + M(30%) - penalty
        total = round((r_score * 0.35 + f_score * 0.35 + m_score * 0.30) - penalty, 2)
        total = max(0.5, min(5.0, total))

        # Determine segment
        segment = _classify_segment(r_score, f_score, m_score, total, f_val, age_days)

        # Confidence based on data availability
        confidence = min(1.0, 0.5 + (f_val * 0.1) + (0.1 if age_days > 30 else 0))

        results[str(user['id'])] = {
            'segment': segment,
            'rfm_recency': r_score,
            'rfm_frequency': f_score,
            'rfm_monetary': m_score,
            'rfm_total': total,
            'confidence': round(confidence, 2),
            'details': {
                'days_since_last': r_val if r_val != 9999 else None,
                'rental_count': f_val,
                'total_spent': m_val,
                'incident_count': inc_val,
            }
        }

    return results


def _kmeans_segmentation(users, recencies, frequencies, monetaries, incidents):
    """K-Means based RFM segmentation for larger datasets."""
    # Prepare feature matrix
    X = np.column_stack([
        np.array(recencies, dtype=float),
        np.array(frequencies, dtype=float),
        np.array(monetaries, dtype=float),
        np.array(incidents, dtype=float),
    ])

    # Normalize
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)

    # Invert recency (lower = better)
    X_scaled[:, 0] = -X_scaled[:, 0]
    # Invert incidents (lower = better)
    X_scaled[:, 3] = -X_scaled[:, 3]

    # K-Means with 4 clusters
    n_clusters = min(4, len(users))
    kmeans = KMeans(n_clusters=n_clusters, random_state=42, n_init=10)
    labels = kmeans.fit_predict(X_scaled)

    # Score each dimension using quintiles
    r_scores = _score_quintile(recencies, reverse=True)
    f_scores = _score_quintile(frequencies, reverse=False)
    m_scores = _score_quintile(monetaries, reverse=False)

    # Map clusters to segments by average score
    cluster_means = {}
    for c in range(n_clusters):
        mask = labels == c
        avg = np.mean([
            np.mean(np.array(r_scores)[mask]),
            np.mean(np.array(f_scores)[mask]),
            np.mean(np.array(m_scores)[mask]),
        ])
        cluster_means[c] = avg

    # Sort clusters by average score
    sorted_clusters = sorted(cluster_means.items(), key=lambda x: x[1], reverse=True)
    segment_names = ['vip', 'regular', 'at_risk', 'lost']
    cluster_to_segment = {}
    for idx, (cluster_id, _) in enumerate(sorted_clusters):
        cluster_to_segment[cluster_id] = segment_names[min(idx, len(segment_names) - 1)]

    results = {}
    for i, user in enumerate(users):
        r_score = r_scores[i]
        f_score = f_scores[i]
        m_score = m_scores[i]
        inc_val = incidents[i]

        penalty = min(inc_val * 0.3, 1.5)
        total = round((r_score * 0.35 + f_score * 0.35 + m_score * 0.30) - penalty, 2)
        total = max(0.5, min(5.0, total))

        segment = cluster_to_segment[labels[i]]

        # Override: if user has 0 rentals, they're "new"
        if user.get('rental_count', 0) == 0:
            segment = 'new'

        confidence = min(1.0, 0.6 + (user.get('rental_count', 0) * 0.08))

        results[str(user['id'])] = {
            'segment': segment,
            'rfm_recency': r_score,
            'rfm_frequency': f_score,
            'rfm_monetary': m_score,
            'rfm_total': total,
            'confidence': round(confidence, 2),
            'details': {
                'days_since_last': recencies[i] if recencies[i] != 9999 else None,
                'rental_count': user.get('rental_count', 0),
                'total_spent': user.get('total_spent', 0),
                'incident_count': inc_val,
            }
        }

    return results


def _classify_segment(r, f, m, total, rental_count, account_age_days):
    """Classify a user into a segment based on scores."""
    # New user: no rentals at all
    if rental_count == 0:
        return 'new'

    # VIP: high scores across the board
    if total >= 3.8 and r >= 4 and f >= 3:
        return 'vip'

    # Regular: decent activity
    if total >= 2.5 and r >= 3:
        return 'regular'

    # At-Risk: was active but recency is declining
    if f >= 2 and r <= 2:
        return 'at_risk'

    # Lost: very low recency + low overall
    if r <= 1 and total < 2.5:
        return 'lost'

    # Fallback: if they have some activity but low scores
    if total >= 2.0:
        return 'regular'

    if r <= 2:
        return 'at_risk'

    return 'regular'


def compute_distribution(segments):
    """Compute distribution counts."""
    dist = {'vip': 0, 'regular': 0, 'at_risk': 0, 'lost': 0, 'new': 0}
    for uid, data in segments.items():
        seg = data['segment']
        if seg in dist:
            dist[seg] += 1
        else:
            dist[seg] = 1
    return dist


def main():
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No input provided'}))
        sys.exit(1)

    try:
        data = decode_input(sys.argv[1])
        users = data.get('users', [])

        if not users:
            print(json.dumps({
                'segments': {},
                'distribution': {'vip': 0, 'regular': 0, 'at_risk': 0, 'lost': 0, 'new': 0},
                'metrics': {'method': 'none', 'total_users': 0}
            }))
            sys.exit(0)

        # Run segmentation
        segments = calculate_rfm_scores(users)
        distribution = compute_distribution(segments)

        # Build metrics
        method = 'kmeans' if (len(users) >= 5 and HAS_SKLEARN) else 'rule_based'
        metrics = {
            'method': method,
            'total_users': len(users),
            'sklearn_available': HAS_SKLEARN,
            'timestamp': datetime.now().isoformat(),
            'thresholds': {
                'recency_days': [7, 30, 90, 180],
                'frequency_count': [1, 3, 6, 10],
                'monetary_amount': [100, 500, 1500, 5000],
            }
        }

        result = {
            'segments': segments,
            'distribution': distribution,
            'metrics': metrics,
        }

        print(json.dumps(result))

    except Exception as e:
        print(json.dumps({'error': str(e)}))
        sys.exit(1)


if __name__ == '__main__':
    main()

