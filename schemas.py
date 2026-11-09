from pydantic import BaseModel, EmailStr, Field
from typing import Optional

class UserCreate(BaseModel):
    email: EmailStr
    password: str = Field(..., max_length=72)
    role: str 
    no_telepon: Optional[str] = None

class UserLogin(BaseModel):
    email: EmailStr
    password: str

class Token(BaseModel):
    access_token: str
    token_type: str

class LayananCreate(BaseModel):
    nama_layanan: str
    kategori: str
    harga: float
    deskripsi: Optional[str] = None