import os
import sys
import json
import warnings

from datetime import datetime

warnings.filterwarnings("ignore")

# Urutan fitur HARUS sama dengan train_model.py
FITUR = ["qty", "bobot_kesusahan", "jumlah_pekerja", "durasi_target"]

# Cadangan jika file bobot_barang.pkl belum ada (harus sama dgn train_model.py)
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

try:

    import joblib
    import pandas as pd

    # -------------------------------------------------
    # 1. Ambil parameter dari Laravel
    # -------------------------------------------------

    if len(sys.argv) < 6:
        raise ValueError(
            "Parameter kurang. Format: predict.py qty jenis_barang "
            "jumlah_pekerja tanggal_order target_selesai"
        )

    qty = int(sys.argv[1])
    jenis_barang = sys.argv[2].strip()
    jumlah_pekerja = int(sys.argv[3])
    tanggal_order = sys.argv[4]
    target_selesai = sys.argv[5]

    # -------------------------------------------------
    # 2. Hitung durasi target
    # -------------------------------------------------

    fmt = "%Y-%m-%d"

    d1 = datetime.strptime(tanggal_order[:10], fmt)
    d2 = datetime.strptime(target_selesai[:10], fmt)

    durasi_target = (d2 - d1).days

    if durasi_target <= 0:
        raise ValueError("Target selesai harus lebih besar dari tanggal order.")

    if qty <= 0:
        raise ValueError("Qty harus lebih besar dari 0.")

    if jumlah_pekerja <= 0:
        raise ValueError("Jumlah pekerja harus lebih besar dari 0.")

    # -------------------------------------------------
    # 3. Load model & mapping bobot
    # -------------------------------------------------

    base_path = os.path.dirname(os.path.dirname(__file__))

    model_path = os.path.join(base_path, "models", "model.pkl")
    bobot_path = os.path.join(base_path, "models", "bobot_barang.pkl")

    if not os.path.exists(model_path):
        raise FileNotFoundError(
            "Model belum tersedia. Lakukan Training Model terlebih dahulu."
        )

    model = joblib.load(model_path)

    if os.path.exists(bobot_path):
        bobot_barang = joblib.load(bobot_path)
    else:
        bobot_barang = BOBOT_BARANG

    # -------------------------------------------------
    # 4. Konversi jenis barang -> bobot kesusahan
    # -------------------------------------------------

    if jenis_barang not in bobot_barang:
        raise ValueError(
            f"Jenis barang '{jenis_barang}' belum terdaftar pada mapping bobot."
        )

    bobot_kesusahan = bobot_barang[jenis_barang]

    # -------------------------------------------------
    # 5. Prediksi
    # -------------------------------------------------

    data = pd.DataFrame(
        [[qty, bobot_kesusahan, jumlah_pekerja, durasi_target]],
        columns=FITUR
    )

    proba = model.predict_proba(data)[0]

    kelas_model = list(model.classes_)

    prob_terlambat = float(proba[kelas_model.index(1)]) if 1 in kelas_model else 0.0

    pred_class = 1 if prob_terlambat >= 0.5 else 0

    hasil_teks = "TERLAMBAT" if pred_class == 1 else "TEPAT WAKTU"

    keyakinan = prob_terlambat if pred_class == 1 else (1 - prob_terlambat)

    # -------------------------------------------------
    # 6. Output JSON
    # -------------------------------------------------

    output_data = {
        "durasi_target": durasi_target,
        "bobot_kesusahan": bobot_kesusahan,
        "beban_kerja": round(
            (qty * bobot_kesusahan) / (jumlah_pekerja * durasi_target), 2
        ),
        "hasil": hasil_teks,
        "probabilitas": round(prob_terlambat * 100, 2),
        "keyakinan": round(keyakinan * 100, 2)
    }

    print(json.dumps(output_data))

except Exception as e:

    print(json.dumps({"error": str(e)}))
    sys.exit(1)
