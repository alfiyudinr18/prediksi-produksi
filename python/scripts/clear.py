import os
import re
import pandas as pd

# ==========================================
# FUNGSI KATEGORISASI BARANG
# ==========================================
def kategorikan_barang(deskripsi):
    teks = str(deskripsi).lower().strip()

    if any(k in teks for k in ['stel', 'stelan', 'seragam']):
        if any(k in teks for k in ['olahraga', 'training', 'sport']):
            return 'Setelan Training'
        return 'Seragam & Setelan'

    if any(k in teks for k in ['jaket', 'jacket', 'hoodie', 'bomber',
                                 'sweater', 'almamater', 'varsity']):
        return 'Jaket & Hoodie'

    if any(k in teks for k in ['celana', 'rok', 'trouser', 'pants']):
        return 'Celana & Rok'

    if any(k in teks for k in ['kemeja', 'hem', 'pdl', 'pdh', 'batik']):
        return 'Kemeja Tangan Panjang' if 'panjang' in teks else 'Kemeja Tangan Pendek'

    if any(k in teks for k in ['wangky', 'wangki', 'wanky', 'polo', 'poloshirt']):
        return 'Wangky Tangan Panjang' if 'panjang' in teks else 'Wangky Tangan Pendek'

    if any(k in teks for k in ['rompi', 'vest']):
        return 'Rompi'

    if any(k in teks for k in ['wearpack', 'warepack', 'wearepack']):
        return 'Wearpack'

    if 'panjang' in teks and any(k in teks for k in ['kaos', 'kaso', 'tangan',
                                                       'tshirt', 'koko', 'kurta']):
        return 'Kaos Tangan Panjang'

    if any(k in teks for k in ['kaos', 'kaso', 'oblong', 'tshirt', 't-shirt',
                                 't shirt', 'jersey', 'raglan', 'koko', 'kurta']):
        return 'Kaos Oblong'

    if any(k in teks for k in ['topi', 'tas', 'totebag', 'goodie']):
        return 'Aksesoris'

    return 'Lain-lain'


# ==========================================
# FORMAT NO PO  →  PO-YYYYMM-NNNN
# ==========================================
def format_no_po(no_raw, tanggal):
    try:
        nomor = int(float(str(no_raw).strip()))
    except Exception:
        match = re.search(r'\d+', str(no_raw))
        nomor = int(match.group()) if match else 0

    try:
        tgl   = pd.to_datetime(tanggal)
        prefix = tgl.strftime('%Y%m')
    except Exception:
        prefix = '000000'

    return f"PO-{prefix}-{nomor:04d}"


# ==========================================
# PARSE STATUS  →  0 = Tepat Waktu
#                  1 = Terlambat
# ==========================================
def parse_status(val):
    v = str(val).strip().upper()
    # TRUE / 1  = TIDAK terlambat  → label 0
    if v in ('TRUE', '1', 'YES', 'TEPAT WAKTU', 'ON TIME', 'TIDAK'):
        return 0
    # FALSE / 0 = TERLAMBAT        → label 1
    if v in ('FALSE', '0', 'NO', 'TERLAMBAT', 'LATE', 'YA'):
        return 1
    return 0   # default aman


# ==========================================
# PROSES UTAMA
# ==========================================
def main():
    BASE_DIR    = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    DATA_DIR    = os.path.join(BASE_DIR, 'data')
    os.makedirs(DATA_DIR, exist_ok=True)

    INPUT_FILE  = os.path.join(DATA_DIR, 'skripsi.xlsx')
    OUTPUT_FILE = os.path.join(DATA_DIR, 'Dataset_Produksi_Bersih.xlsx')

    print("=" * 60)
    print("MEMULAI PREPROCESSING DATA PRODUKSI GARMEN")
    print("=" * 60)
    print(f"\nMembaca: {INPUT_FILE}")

    # --------------------------------------------------
    # STEP 1 - Baca & deteksi otomatis struktur Excel
    # --------------------------------------------------
    df_peek = pd.read_excel(INPUT_FILE, header=None, nrows=3)

    # Cek apakah baris ke-1 (index 1) adalah sub-header teks
    # (misal "BAHAN", "WARNA", dll.) → kalau iya, skip baris tsb
    baris1 = df_peek.iloc[1].astype(str).str.strip().str.lower().tolist()
    ada_subheader = any(b in ('bahan', 'warna', 'nan', '') for b in baris1[:5])

    if ada_subheader:
        print("   >> Terdeteksi sub-header di baris ke-2, akan di-skip.")
        df_raw = pd.read_excel(INPUT_FILE, header=0, skiprows=[1])
    else:
        df_raw = pd.read_excel(INPUT_FILE, header=0)

    print(f"   Baris awal : {len(df_raw)}")
    print(f"   Kolom      : {df_raw.columns.tolist()}")

    df = df_raw.copy()

    # --------------------------------------------------
    # STEP 2 - Normalisasi nama kolom
    # Mendukung dua format:
    #   Format A (skripsi.xlsx) : NO, SPK, TGL PO, DATE LINE,
    #                              NAMA, DESCRIPTION, NAMA TOKO,
    #                              SIZE, QTY, SAT, PRICE,
    #                              FINISHING, PEKERJA, STATUS
    #   Format B (dataskrps.xlsx): NO PO, TANGGAL ORDER,
    #                              KETERANGAN BARANG, WARNA, QTY,
    #                              PEKERJA, ..., TARGET SELESAI,
    #                              TGL SELESAI, TERLAMBAT
    # --------------------------------------------------
    KOLOM_MAP = {
        # Format A → nama standar
        'NO'               : 'NO_ID',
        'TGL PO'           : 'TGL_ORDER',
        'DATE LINE'        : 'TGL_TARGET',
        'DESCRIPTION'      : 'DESKRIPSI',
        'QTY'              : 'QTY',
        'PEKERJA'          : 'PEKERJA',
        'STATUS'           : 'STATUS_RAW',
        # Format B → nama standar
        'NO PO'            : 'NO_ID',
        'TANGGAL ORDER'    : 'TGL_ORDER',
        'TARGET SELESAI'   : 'TGL_TARGET',
        'KETERANGAN BARANG': 'DESKRIPSI',
        'TERLAMBAT'        : 'STATUS_RAW',
        'WARNA'            : 'WARNA',
    }
    df.rename(columns={k: v for k, v in KOLOM_MAP.items() if k in df.columns},
              inplace=True)

    # Pastikan kolom wajib tersedia
    wajib = ['NO_ID', 'TGL_ORDER', 'TGL_TARGET', 'DESKRIPSI', 'QTY', 'PEKERJA', 'STATUS_RAW']
    hilang = [c for c in wajib if c not in df.columns]
    if hilang:
        print(f"\n[ERROR] Kolom berikut tidak ditemukan: {hilang}")
        print("Kolom yang tersedia:", df.columns.tolist())
        raise KeyError(f"Kolom tidak ditemukan: {hilang}")

    # --------------------------------------------------
    # STEP 3 - Forward fill kolom utama
    # (1 pesanan bisa punya banyak baris ukuran)
    # --------------------------------------------------
    ff_cols = ['NO_ID', 'TGL_ORDER', 'TGL_TARGET', 'DESKRIPSI', 'PEKERJA', 'STATUS_RAW']
    for col in ff_cols:
        df[col] = df[col].ffill()

    # --------------------------------------------------
    # STEP 4 - Konversi tipe data
    # --------------------------------------------------
    df['TGL_ORDER']  = pd.to_datetime(df['TGL_ORDER'],  errors='coerce', dayfirst=True)
    df['TGL_TARGET'] = pd.to_datetime(df['TGL_TARGET'], errors='coerce', dayfirst=True)
    df['QTY']        = pd.to_numeric(df['QTY'],     errors='coerce').fillna(0)
    df['PEKERJA']    = pd.to_numeric(df['PEKERJA'], errors='coerce').fillna(0)
    df['NO_ID']      = df['NO_ID'].astype(str).str.strip()

    # --------------------------------------------------
    # STEP 5 - Filter baris valid
    # --------------------------------------------------
    df = df[~df['NO_ID'].isin(['', 'nan', 'NaN', 'None', 'NO_ID'])]
    df = df[df['QTY'] > 0]
    print(f"   Baris valid: {len(df)}")

    # --------------------------------------------------
    # STEP 6 - Agregasi per pesanan (gabungkan QTY semua ukuran)
    # --------------------------------------------------
    df_grouped = df.groupby('NO_ID', sort=False).agg(
        TGL_ORDER   = ('TGL_ORDER',   'first'),
        TGL_TARGET  = ('TGL_TARGET',  'first'),
        DESKRIPSI   = ('DESKRIPSI',   'first'),
        QTY         = ('QTY',         'sum'),
        PEKERJA     = ('PEKERJA',     'first'),
        STATUS_RAW  = ('STATUS_RAW',  'first'),
    ).reset_index()

    print(f"   Pesanan unik: {len(df_grouped)}")

    # --------------------------------------------------
    # STEP 7 - Buat kolom output
    # --------------------------------------------------
    df_grouped['no_po'] = df_grouped.apply(
        lambda r: format_no_po(r['NO_ID'], r['TGL_ORDER']), axis=1
    )
    df_grouped['tanggal_order']      = df_grouped['TGL_ORDER'].dt.strftime('%d/%m/%Y')
    df_grouped['target_selesai']     = df_grouped['TGL_TARGET'].dt.strftime('%d/%m/%Y')
    df_grouped['durasi_target_hari'] = (
        df_grouped['TGL_TARGET'] - df_grouped['TGL_ORDER']
    ).dt.days.fillna(0).astype(int)
    df_grouped['keterangan_barang']  = df_grouped['DESKRIPSI'].apply(kategorikan_barang)
    df_grouped['qty']                = df_grouped['QTY'].astype(int)
    df_grouped['pekerja']            = df_grouped['PEKERJA'].astype(int)
    df_grouped['terlambat']          = df_grouped['STATUS_RAW'].apply(parse_status)

    # Warna: ambil jika kolom tersedia, kalau tidak isi 'Campuran'
    if 'WARNA' in df.columns:
        warna_map = df.groupby('NO_ID')['WARNA'].apply(
            lambda x: x.dropna().iloc[0] if not x.dropna().empty else 'Campuran'
        )
        df_grouped['warna'] = df_grouped['NO_ID'].map(warna_map).fillna('Campuran')
        df_grouped['warna'] = df_grouped['warna'].astype(str).str.strip().str.title()
    else:
        df_grouped['warna'] = 'Campuran'

    # --------------------------------------------------
    # STEP 8 - Susun & simpan
    # --------------------------------------------------
    df_final = df_grouped[[
        'no_po',
        'tanggal_order',
        'keterangan_barang',
        'warna',
        'qty',
        'pekerja',
        'target_selesai',
        'durasi_target_hari',
        'terlambat',
    ]].reset_index(drop=True)

    df_final.to_excel(OUTPUT_FILE, index=False)

    # --------------------------------------------------
    # STEP 9 - Ringkasan
    # --------------------------------------------------
    total = len(df_final)
    print("\n" + "=" * 60)
    print("PREPROCESSING SELESAI")
    print("=" * 60)
    print(f"\nFile output  : {OUTPUT_FILE}")
    print(f"Total baris  : {total} pesanan")

    print("\nDistribusi Label Terlambat:")
    for label, count in df_final['terlambat'].value_counts().sort_index().items():
        nama = "Tepat Waktu (0)" if label == 0 else "Terlambat   (1)"
        pct  = count / total * 100
        print(f"  {nama} : {count:>4} data ({pct:.1f}%)")

    print("\nDistribusi Kategori Barang:")
    for cat, cnt in df_final['keterangan_barang'].value_counts().items():
        pct = cnt / total * 100
        print(f"  {cat:<30} : {cnt:>4} ({pct:.1f}%)")

    print("\nStatistik Numerik:")
    print(df_final[['qty', 'pekerja', 'durasi_target_hari']].describe().round(2).to_string())

    print("\nSample Output (5 baris pertama):")
    print(df_final.head(5).to_string(index=False))

    print("\nSiap digunakan untuk training Random Forest!")
    return df_final


if __name__ == '__main__':
    main()
