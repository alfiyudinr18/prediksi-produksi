import os
import pandas as pd
import re # Tambahkan library Regex untuk pencarian kata akurat

# ==========================================
# FUNGSI KATEGORISASI BARANG (DISEMPURNAKAN)
# ==========================================
def kategorikan_barang(deskripsi):
    teks = str(deskripsi).lower()

    # 1. Kategori Aksesoris / Non-Baju (Prioritas dibuang ke Lain-lain)
    if any(k in teks for k in ['topi']): return 'Topi'
    if any(k in teks for k in ['tas', 'totebag', 'goodie']): return 'Tas & Aksesoris'
    if any(k in teks for k in ['cover', 'boneka', 'dtf', 'pasang', 'kain', 'koper']): return 'Lain-lain'

    # 2. Celana & Setelan (Beban kerja paling berat, harus ditaruh paling atas)
    if any(k in teks for k in ['setelan', 'set', 'training', 'traning', 'celana']):
        return 'Celana & Setelan'

    # 3. Jaket, Jas & Almamater
    if any(k in teks for k in ['jaket', 'jacket', 'bomber', 'sweater', 'hoodie', 'varsity', 'almamater', 'jas']):
        return 'Jaket & Jas'

    # 4. Wearpack (Baju Montir/Lapangan)
    if any(k in teks for k in ['wearpack', 'warepack', 'wearepack']):
        return 'Wearpack'

    # 5. Seragam
    if 'seragam' in teks:
        # Pisahkan seragam sekolah dan seragam kerja
        if any(k in teks for k in ['sd', 'smp', 'sma', 'smk', 'tk', 'paud', 'sekolah', 'pramuka']):
            return 'Seragam Sekolah'
        else:
            return 'Seragam Kerja / Instansi'

    # 6. Kemeja (Baju Formal Berkerah Kancing Penuh)
    if any(k in teks for k in ['kemeja', 'hem', 'pdl', 'pdh']):
        return 'Kemeja'

    # 7. Rompi
    if any(k in teks for k in ['rompi', 'vest']):
        return 'Rompi'

    # 8. Kaos Berkerah (Wangky / Polo)
    # Gunakan re.search('\bpolo\b') agar kata 'polos' tidak terbaca sebagai 'polo'
    if any(k in teks for k in ['wangky', 'wangki', 'wanky', 'wangkt', 'ploshirt', 'poloshirt']) or re.search(r'\bpolo\b', teks):
        return 'Kaos Wangky'

    # 9. Kaos Lengan Panjang
    if 'panjang' in teks and any(k in teks for k in ['kaos', 'kaso', 'tangan', 'tshirt']):
        return 'Kaos Lengan Panjang'

    # 10. Kaos Oblong (T-Shirt, Jersey, dll)
    # Masukkan berbagai macam typo kaos di sini
    if any(k in teks for k in ['oblong', 't-shirt', 'tshirt', 't shirt', 'thsirt', 'kaos', 'kaso', 'jersey', 'jersy', 'raglan']):
        return 'Kaos Oblong'

    # Jika lolos dari semua filter di atas
    return 'Lain-lain'

# ==========================================
# PROSES UTAMA
# ==========================================
BASE_DIR = os.path.dirname(os.path.dirname(__file__))
DATA_DIR = os.path.join(BASE_DIR, "data")
os.makedirs(DATA_DIR, exist_ok=True)

file_path = os.path.join(DATA_DIR, 'skripsi2.xlsx')
print(f"Membaca data dari: {file_path}...")
df = pd.read_excel(file_path)

cols_to_ffill = ['NO', 'TGL PO', 'DATE LINE', 'DESCRIPTION']
df[cols_to_ffill] = df[cols_to_ffill].ffill()

df_clean = df.groupby('NO').agg({
    'TGL PO': 'first',
    'DATE LINE': 'first',
    'DESCRIPTION': 'first',
    'QTY': 'sum'
}).reset_index()

df_clean['TGL PO'] = pd.to_datetime(df_clean['TGL PO'], errors='coerce')
df_clean['DATE LINE'] = pd.to_datetime(df_clean['DATE LINE'], errors='coerce')
df_clean['Durasi Target (Hari)'] = (df_clean['DATE LINE'] - df_clean['TGL PO']).dt.days

# TERAPKAN KATEGORISASI KE KOLOM DESCRIPTION
df_clean['Kategori_Barang'] = df_clean['DESCRIPTION'].apply(kategorikan_barang)

df_clean['Pekerja'] = ""
df_clean['Mesin_Aktif'] = ""
df_clean['Label_Keterlambatan'] = ""

df_clean.rename(columns={
    'NO': 'ID_Pesanan',
    'TGL PO': 'Tanggal_PO',
    'DATE LINE': 'Date_Line',
    'DESCRIPTION': 'Deskripsi_Asli',
    'QTY': 'Total QTY'
}, inplace=True)

df_final = df_clean[[
    'ID_Pesanan', 'Tanggal_PO', 'Date_Line', 'Deskripsi_Asli',
    'Kategori_Barang', 'Total QTY', 'Durasi Target (Hari)',
    'Pekerja', 'Mesin_Aktif', 'Label_Keterlambatan'
]]

output_name = os.path.join(DATA_DIR, 'Dataset_Siap_RF.xlsx')
df_final.to_excel(output_name, index=False)

print(f"✅ Data berhasil dibersihkan dan dikategorikan!\nFile tersimpan di: {output_name}")
