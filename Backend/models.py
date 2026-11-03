from sqlalchemy import Column, Integer, String
from database import Base

class User(Base):
    __tablename__ = "users"

    id_user = Column(Integer, primary_key=True, index=True)
    nama = Column(String, index=True)
    email = Column(String, unique=True, index=True)
    password = Column(String)
    role = Column(String) # Klien, Vendor, Pemilik Venue, atau Admin
    status = Column(String, default="Aktif") # Aktif atau Menunggu Verifikasi