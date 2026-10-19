from fastapi import FastAPI, Depends, HTTPException
from sqlalchemy.orm import Session
from passlib.context import CryptContext

import models
import schemas
from database import engine, SessionLocal

# Perintah ini akan membuat file database dan tabelnya otomatis
models.Base.metadata.create_all(bind=engine)

app = FastAPI()

# Pengaturan untuk menyandikan (hash) password
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")

def get_password_hash(password):
    return pwd_context.hash(password)

# Fungsi untuk membuka dan menutup koneksi database setiap kali ada request
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

@app.get("/")
def read_root():
    return {"message": "Backend Pesta Kita Berjalan dan Database Terhubung!"}

# --- ENDPOINT REGISTRASI PENGGUNA ---
@app.post("/register/")
def register_user(user: schemas.UserCreate, db: Session = Depends(get_db)):
    # 1. Cek apakah email sudah pernah didaftarkan
    cek_email = db.query(models.User).filter(models.User.email == user.email).first()
    if cek_email:
        raise HTTPException(status_code=400, detail="Email sudah terdaftar!")
    
    # 2. Sandikan password
    hashed_password = get_password_hash(user.password)
    
    # 3. Masukkan data ke database
    pengguna_baru = models.User(
        nama=user.nama, 
        email=user.email, 
        password=hashed_password, 
        role=user.role,
        status="Aktif" 
    )
    db.add(pengguna_baru)
    db.commit()
    db.refresh(pengguna_baru)
    
    return {"message": "Registrasi berhasil!", "nama": pengguna_baru.nama, "role": pengguna_baru.role}