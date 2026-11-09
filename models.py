from sqlalchemy import Column, Integer, String, Float, Text, Boolean, ForeignKey
from sqlalchemy.orm import relationship
from database import Base

class User(Base):
    __tablename__ = "users"
    
    id = Column(Integer, primary_key=True, index=True)
    email = Column(String(255), unique=True, index=True, nullable=False)
    hashed_password = Column(String(255), nullable=False)
    role = Column(String(50), nullable=False)
    status = Column(String(50), default="pending")
    no_telepon = Column(String(20), nullable=True)

class LayananVendor(Base):
    __tablename__ = "layanan_vendor"
    
    id = Column(Integer, primary_key=True, index=True)
    vendor_id = Column(Integer, ForeignKey("users.id"))
    nama_layanan = Column(String(100), nullable=False)
    kategori = Column(String(50), nullable=False)
    harga = Column(Float, nullable=False)