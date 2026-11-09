from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from fastapi import FastAPI, Depends, HTTPException
from sqlalchemy.orm import Session
from passlib.context import CryptContext
import models, schemas
from database import engine, get_db
import jwt
from datetime import datetime, timedelta

# Membaca models.py dan membuat tabel di MySQL secara otomatis
models.Base.metadata.create_all(bind=engine)

app = FastAPI(title="API Pesta Kita (EMS Ulang Tahun)")

# Konfigurasi Hash Password (Keamanan)
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")

def get_password_hash(password):
    return pwd_context.hash(password)

@app.get("/")
def root():
    return {"message": "Server Pesta Kita berjalan dengan baik!"}

# Endpoint Registrasi Akun
@app.post("/register/")
def register_user(user: schemas.UserCreate, db: Session = Depends(get_db)):
    # Cek apakah email sudah terdaftar di database
    db_user = db.query(models.User).filter(models.User.email == user.email).first()
    if db_user:
        raise HTTPException(status_code=400, detail="Email sudah terdaftar")
    
    # Atur status akun: Vendor dan Venue butuh verifikasi Admin (status 'pending')
    status_akun = "pending" if user.role in ["vendor", "pemilik_venue"] else "aktif"
    
    # Hash password dan simpan user baru
    hashed_password = get_password_hash(user.password)
    new_user = models.User(
        email=user.email,
        password=hashed_password,
        role=user.role,
        no_telepon=user.no_telepon,
        status=status_akun
    )
    
    db.add(new_user)
    db.commit()
    db.refresh(new_user)
    
    return {
        "message": "Registrasi berhasil", 
        "user_id": new_user.id, 
        "role": new_user.role,
        "status": new_user.status
    }

# Konfigurasi Kunci Token Keamanan
SECRET_KEY = "pesta_kita_rahasia_banget" 
ALGORITHM = "HS256"

# Fungsi untuk mengecek kecocokan password
def verify_password(plain_password, hashed_password):
    return pwd_context.verify(plain_password, hashed_password)

# Fungsi untuk membuat token yang berlaku selama 24 jam
def create_access_token(data: dict):
    to_encode = data.copy()
    expire = datetime.utcnow() + timedelta(days=1)
    to_encode.update({"exp": expire})
    encoded_jwt = jwt.encode(to_encode, SECRET_KEY, algorithm=ALGORITHM)
    return encoded_jwt

@app.post("/login/", response_model=schemas.Token)
def login_user(user: schemas.UserLogin, db: Session = Depends(get_db)):
    # 1. Cari pengguna di database berdasarkan email
    db_user = db.query(models.User).filter(models.User.email == user.email).first()
    
    # 2. Cek apakah email ada dan password sesuai
    if not db_user or not verify_password(user.password, db_user.password):
        raise HTTPException(status_code=401, detail="Email atau password salah")
        
    # 3. Validasi status verifikasi khusus Vendor dan Pemilik Venue
    if db_user.role in ["vendor", "pemilik_venue"] and db_user.status == "pending":
        raise HTTPException(status_code=403, detail="Akun penyedia jasa belum diverifikasi oleh Admin")
        
    # 4. Buat dan kembalikan token jika sukses
    access_token = create_access_token(
        data={"sub": db_user.email, "role": db_user.role, "id": db_user.id}
    )
    
    return {"access_token": access_token, "token_type": "bearer"}

# Inisialisasi Gembok Bearer Token
security = HTTPBearer()

# Fungsi untuk membaca token dan mencari siapa user yang sedang login
def get_current_user(credentials: HTTPAuthorizationCredentials = Depends(security), db: Session = Depends(get_db)):
    token = credentials.credentials
    try:
        # Membuka isi token
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        email: str = payload.get("sub")
        if email is None:
            raise HTTPException(status_code=401, detail="Token tidak valid")
    except Exception:
        raise HTTPException(status_code=401, detail="Token tidak valid atau sudah kedaluwarsa")
        
    # Mencari data user di database
    user = db.query(models.User).filter(models.User.email == email).first()
    if user is None:
        raise HTTPException(status_code=401, detail="User tidak ditemukan")
    return user

# Endpoint percobaan yang DIGEMBOK (Harus pakai token)
@app.get("/profile/")
def cek_profil(current_user: models.User = Depends(get_current_user)):
    return {
        "pesan": "Berhasil masuk ke area rahasia!",
        "email": current_user.email,
        "role": current_user.role,
        "status": current_user.status
    }

# Endpoint bagi Admin untuk melihat semua user
@app.get("/admin/users/")
def get_all_users(db: Session = Depends(get_db), current_user: models.User = Depends(get_current_user)):
    if current_user.role != "admin":
        raise HTTPException(status_code=403, detail="Akses ditolak. Khusus Admin.")
    
    # Ambil seluruh data user secara utuh
    users = db.query(models.User).all()
    
    # Ubah ke format list dictionary secara manual agar aman dari error encoding
    result = []
    for u in users:
        result.append({
            "id": u.id,
            "email": u.email,
            "role": u.role,
            "status": u.status,
            "no_telepon": u.no_telepon
        })
    return result

# 2. Endpoint bagi Admin untuk memverifikasi akun (Vendor / Venue)
@app.put("/admin/verify-user/{user_id/")
def verify_user(user_id: int, db: Session = Depends(get_db), current_user: models.User = Depends(get_current_user)):
    # Validasi: Hanya Admin yang boleh akses
    if current_user.role != "admin":
        raise HTTPException(status_code=403, detail="Akses ditolak. Khusus Admin.")
        
    user = db.query(models.User).filter(models.User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Pengguna tidak ditemukan")
        
    # Ubah status menjadi aktif
    user.status = "aktif"
    db.commit()
    
    return {
        "message": f"Akun dengan email {user.email} berhasil diverifikasi dan diaktifkan!",
        "user_id": user.id,
        "status_baru": user.status
    }

# 1. Endpoint bagi Vendor untuk menambah layanan baru
@app.post("/vendor/layanan/")
def tambah_layanan(layanan: schemas.LayananCreate, db: Session = Depends(get_db), current_user: models.User = Depends(get_current_user)):
    # Validasi: Hanya vendor yang aktif boleh menambah layanan
    if current_user.role != "vendor":
        raise HTTPException(status_code=403, detail="Akses ditolak. Khusus Vendor.")
    if current_user.status != "aktif":
        raise HTTPException(status_code=403, detail="Akun vendor belum diverifikasi oleh Admin.")
        
    new_layanan = models.LayananVendor(
        vendor_id=current_user.id,
        nama_layanan=layanan.nama_layanan,
        kategori=layanan.kategori,
        harga=layanan.harga,
        deskripsi=layanan.deskripsi
    )
    
    db.add(new_layanan)
    db.commit()
    db.refresh(new_layanan)
    
    return {
        "message": "Katalog layanan berhasil ditambahkan!",
        "layanan_id": new_layanan.id,
        "nama_layanan": new_layanan.nama_layanan
    }

# 2. Endpoint publik/klien untuk melihat seluruh daftar layanan vendor
@app.get("/layanan/")
def lihat_semua_layanan(db: Session = Depends(get_db)):
    layanan_list = db.query(models.LayananVendor).all()
    return layanan_list