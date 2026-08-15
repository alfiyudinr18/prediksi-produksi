import pymysql

def get_connection():

    return pymysql.connect(

        host="127.0.0.1",

        user="root",

        password="",

        database="prediksi_produksi"

    )
