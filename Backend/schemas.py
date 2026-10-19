from pydantic import BaseModel

class UserCreate(BaseModel):
    nama: str
    email: str
    password: str
    role: str # Contoh: Klien, Vendor, Pemilik Venue, Admin