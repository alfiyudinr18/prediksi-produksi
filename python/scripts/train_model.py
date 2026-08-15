import os
import sys
import json
import joblib
import warnings

import pandas as pd

warnings.filterwarnings("ignore")

sys.path.append(os.path.dirname(os.path.dirname(__file__)))

from config.database import get_connection

from sklearn.model_selection import train_test_split, cross_val_score, StratifiedKFold
from sklearn.ensemble import RandomForestClassifier

from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    confusion_matrix,
    classification_report,
    roc_auc_score
)

# =====================================================
# KONFIGURASI
# =====================================================

MODEL_DIR = os.path.join(
    os.path.dirname(os.path.dirname(__file__)),
    "models"
)

os.makedirs(MODEL_DIR, exist_ok=True)

# Bobot kesusahan pengerjaan tiap jenis barang (1 = paling mudah, 5 = paling sulit).
# Dipakai sebagai bentuk numerik dari jenis_barang, MENGGANTIKAN LabelEncoder.
# Semua pilihan pada form prediksi WAJIB ada di daftar ini.
BOBOT_BARANG = {
    "Bahan / Kain": 1,
    "Kaos Oblong": 1,
    "Kaos Tangan Pendek": 1,
    "Topi": 1,
    "Kaos Tangan Panjang": 2,
    "Tas": 2,
    "Wangky Tangan Pendek": 2,
    "Celana": 3,
    "Rompi": 3,
    "Seragam & Rok": 3,
    "Wangky Tangan Panjang": 3,
    "Kemeja Tangan Pendek": 4,
    "Kemeja Tangan Panjang": 4,
    "Hoodie & Jaket": 5,
}

# Urutan fitur ini HARUS sama dengan yang dipakai di predict.py
FITUR = ["qty", "bobot_kesusahan", "jumlah_pekerja", "durasi_target"]

# =====================================================
# KONEKSI DATABASE
# =====================================================

conn = get_connection()

query = """
SELECT
    qty,
    jenis_barang,
    jumlah_pekerja,
    tanggal_order,
    target_selesai,
    terlambat
FROM produksis
WHERE terlambat IS NOT NULL
"""

df = pd.read_sql(query, conn)

conn.close()

if df.empty:
    print("Dataset kosong. Import dataset terlebih dahulu.")
    sys.exit()

print("========================================")
print("DATA HISTORIS")
print("========================================")
print(df.head())

# =====================================================
# FEATURE ENGINEERING
# =====================================================

df["tanggal_order"] = pd.to_datetime(df["tanggal_order"])

df["target_selesai"] = pd.to_datetime(df["target_selesai"])

df["durasi_target"] = (
    df["target_selesai"] -
    df["tanggal_order"]
).dt.days

df["jenis_barang"] = df["jenis_barang"].astype(str).str.strip()

df["qty"] = pd.to_numeric(df["qty"], errors="coerce")

df["jumlah_pekerja"] = pd.to_numeric(df["jumlah_pekerja"], errors="coerce")

df["terlambat"] = pd.to_numeric(df["terlambat"], errors="coerce")

# =====================================================
# KONVERSI JENIS BARANG -> BOBOT KESUSAHAN
# =====================================================

tidak_dikenal = sorted(
    set(df["jenis_barang"]) - set(BOBOT_BARANG.keys())
)

if tidak_dikenal:

    print("\n========================================")
    print("JENIS BARANG BELUM ADA DI DAFTAR BOBOT")
    print("========================================")

    for nama in tidak_dikenal:
        print("-", nama)

    print("\nTambahkan nama di atas ke BOBOT_BARANG pada train_model.py,")
    print("atau samakan penamaannya dengan dataset. Training dibatalkan.")

    sys.exit(1)

df["bobot_kesusahan"] = df["jenis_barang"].map(BOBOT_BARANG)

# =====================================================
# PEMBERSIHAN DATA
# =====================================================

sebelum = len(df)

df = df.dropna(subset=FITUR + ["terlambat"])

df = df[df["durasi_target"] > 0]

df = df[df["jumlah_pekerja"] > 0]

df = df[df["qty"] > 0]

df["terlambat"] = df["terlambat"].astype(int)

dibuang = sebelum - len(df)

print("\n========================================")
print("INFORMASI DATASET")
print("========================================")

print("Jumlah Dataset :", len(df))
print("Baris Dibuang  :", dibuang, "(durasi/qty/pekerja tidak valid)")
print("Tepat Waktu    :", int((df["terlambat"] == 0).sum()))
print("Terlambat      :", int((df["terlambat"] == 1).sum()))

if df["terlambat"].nunique() < 2:
    print("\nDataset hanya berisi satu kelas. Training dibatalkan.")
    sys.exit(1)

if len(df) < 30:
    print("\nData terlalu sedikit untuk training. Training dibatalkan.")
    sys.exit(1)

# =====================================================
# FITUR & TARGET
# =====================================================

X = df[FITUR]

y = df["terlambat"]

print("\n========================================")
print("FITUR YANG DIGUNAKAN")
print("========================================")

print(FITUR)
print("\nTarget : terlambat")

# =====================================================
# SPLIT DATASET 80:20
# =====================================================

X_train, X_test, y_train, y_test = train_test_split(
    X,
    y,
    test_size=0.2,
    random_state=42,
    stratify=y
)

print("\n========================================")
print("PEMBAGIAN DATA")
print("========================================")

print("Data Training :", len(X_train))
print("Data Testing  :", len(X_test))

# =====================================================
# MEMBANGUN MODEL RANDOM FOREST
# =====================================================

print("\nMemulai proses training model...")

model = RandomForestClassifier(
    n_estimators=500,
    max_depth=7,
    min_samples_leaf=5,
    min_samples_split=10,
    class_weight="balanced_subsample",
    random_state=42
)

model.fit(X_train, y_train)

print("Training model berhasil.")

# =====================================================
# EVALUASI MODEL
# =====================================================

train_pred = model.predict(X_train)

test_pred = model.predict(X_test)

train_accuracy = accuracy_score(y_train, train_pred)

testing_accuracy = accuracy_score(y_test, test_pred)

precision = precision_score(y_test, test_pred, zero_division=0)

recall = recall_score(y_test, test_pred, zero_division=0)

f1 = f1_score(y_test, test_pred, zero_division=0)

cm = confusion_matrix(y_test, test_pred, labels=[0, 1])

selisih = abs(train_accuracy - testing_accuracy)

cv = cross_val_score(
    RandomForestClassifier(
        n_estimators=500,
        max_depth=7,
        min_samples_leaf=5,
        min_samples_split=10,
        class_weight="balanced_subsample",
        random_state=42
    ),
    X,
    y,
    cv=StratifiedKFold(5, shuffle=True, random_state=42),
    scoring="accuracy"
)

importance = dict(zip(FITUR, model.feature_importances_))

# Metrik per kelas + rata-rata makro dan terbobot
report = classification_report(
    y_test,
    test_pred,
    labels=[0, 1],
    target_names=["Tepat Waktu", "Terlambat"],
    output_dict=True,
    zero_division=0
)

try:
    auc = roc_auc_score(y_test, model.predict_proba(X_test)[:, 1])
except Exception:
    auc = 0.0

tn, fp, fn, tp = cm.ravel()

specificity = tn / (tn + fp) if (tn + fp) else 0.0


def blok(nama):
    d = report[nama]
    return {
        "precision": round(d["precision"] * 100, 2),
        "recall": round(d["recall"] * 100, 2),
        "f1_score": round(d["f1-score"] * 100, 2),
        "support": int(d["support"])
    }

# =====================================================
# SIMPAN MODEL & MAPPING BOBOT
# =====================================================

model_path = os.path.join(MODEL_DIR, "model.pkl")

joblib.dump(model, model_path)

bobot_path = os.path.join(MODEL_DIR, "bobot_barang.pkl")

joblib.dump(BOBOT_BARANG, bobot_path)

# =====================================================
# SIMPAN HASIL EVALUASI
# =====================================================

evaluation = {

    "jumlah_data": int(len(df)),

    "data_training": int(len(X_train)),

    "data_testing": int(len(X_test)),

    "tepat_waktu": int((df["terlambat"] == 0).sum()),

    "terlambat": int((df["terlambat"] == 1).sum()),

    "training_accuracy": round(train_accuracy * 100, 2),

    "testing_accuracy": round(testing_accuracy * 100, 2),

    "precision": round(precision * 100, 2),

    "recall": round(recall * 100, 2),

    "f1_score": round(f1 * 100, 2),

    "confusion_matrix": cm.tolist(),

    "overfitting": round(selisih * 100, 2),

    "cv_accuracy": [round(float(v) * 100, 2) for v in cv],

    "cv_accuracy_mean": round(float(cv.mean()) * 100, 2),

    "auc": round(float(auc) * 100, 2),

    "specificity": round(float(specificity) * 100, 2),

    "per_kelas": {

        "Tepat Waktu": blok("Tepat Waktu"),

        "Terlambat": blok("Terlambat")

    },

    "macro_avg": blok("macro avg"),

    "weighted_avg": blok("weighted avg"),

    "parameter": {

        "n_estimators": 500,

        "criterion": "gini",

        "max_depth": 7,

        "min_samples_leaf": 5,

        "min_samples_split": 10,

        "class_weight": "balanced_subsample",

        "random_state": 42,

        "test_size": "20%"

    },

    "waktu_training": pd.Timestamp.now().strftime("%d-%m-%Y %H:%M:%S"),

    "feature_importance": {

        fitur: round(float(nilai), 4)

        for fitur, nilai in importance.items()

    },

    "fitur": FITUR

}

evaluation_path = os.path.join(MODEL_DIR, "evaluation.json")

with open(evaluation_path, "w") as file:
    json.dump(evaluation, file, indent=4)

# =====================================================
# OUTPUT HASIL TRAINING
# =====================================================

print("\n========================================")
print("HASIL EVALUASI MODEL")
print("========================================")

print("Training Accuracy :", evaluation["training_accuracy"], "%")
print("Testing Accuracy  :", evaluation["testing_accuracy"], "%")
print("Precision         :", evaluation["precision"], "%")
print("Recall            :", evaluation["recall"], "%")
print("F1 Score          :", evaluation["f1_score"], "%")

print("\nConfusion Matrix (baris = aktual, kolom = prediksi)")
print(cm)

print("\nMetrik per kelas")
print(f"{'Kelas':<14}{'Precision':>11}{'Recall':>9}{'F1':>9}{'Data':>7}")
for nama, d in evaluation["per_kelas"].items():
    print(f"{nama:<14}{d['precision']:>10.2f}%{d['recall']:>8.2f}%{d['f1_score']:>8.2f}%{d['support']:>7}")
for nama, key in [("Macro avg", "macro_avg"), ("Weighted avg", "weighted_avg")]:
    d = evaluation[key]
    print(f"{nama:<14}{d['precision']:>10.2f}%{d['recall']:>8.2f}%{d['f1_score']:>8.2f}%{d['support']:>7}")

print("\nSpecificity :", evaluation["specificity"], "%")
print("AUC         :", evaluation["auc"], "%")

print("\nCross Validation 5-Fold :", evaluation["cv_accuracy"])
print("Rata-rata CV Accuracy   :", evaluation["cv_accuracy_mean"], "%")

print("\n========================================")
print("ANALISIS OVERFITTING")
print("========================================")

print("Selisih Accuracy :", evaluation["overfitting"], "%")

if evaluation["overfitting"] < 10:
    print("Kesimpulan : Model tidak menunjukkan indikasi overfitting.")
else:
    print("Kesimpulan : Model menunjukkan indikasi overfitting.")

print("\n========================================")
print("FEATURE IMPORTANCE")
print("========================================")

for fitur, nilai in sorted(importance.items(), key=lambda x: x[1], reverse=True):
    print(f"{fitur:20s} : {nilai*100:.2f}%")

print("\n========================================")

print("Model disimpan       :", model_path)
print("Mapping bobot        :", bobot_path)
print("Hasil evaluasi       :", evaluation_path)

print("\n========================================")
print("TRAINING MODEL SELESAI")
print("========================================")
