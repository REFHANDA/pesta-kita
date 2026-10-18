from fastapi import FastAPI
import models
from database import engine

# Membuat file database dan tabel secara otomatis
models.Base.metadata.create_all(bind=engine)

app = FastAPI()

@app.get("/")
def read_root():
    return {"message": "Backend Pesta Kita Berjalan dan Database Terhubung!"}