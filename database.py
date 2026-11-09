from sqlalchemy import create_engine
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker

# URL Koneksi ke Database MySQL (Sesuaikan user & password database kamu jika ada)
SQLALCHEMY_DATABASE_URL = "mysql+pymysql://root:@localhost:3306/pesta_kita"

engine = create_engine(SQLALCHEMY_DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

# INI BAGIAN YANG KURANG (Deklarasi Base)
Base = declarative_base()

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()