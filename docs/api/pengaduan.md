# Pengaduan (Bug Report) API

Endpoint untuk mengelola laporan bug dan pengaduan dari Kader ke Admin.

---

## Endpoints

| Method | Endpoint                       | Deskripsi          | Access |
| ------ | ------------------------------ | ------------------ | ------ |
| `GET`  | `/api/pengaduan`               | List pengaduan     | All    |
| `POST` | `/api/pengaduan`               | Buat pengaduan     | All    |
| `GET`  | `/api/pengaduan/{id}`          | Detail + responses | All    |
| `PUT`  | `/api/pengaduan/{id}/status`   | Update status      | Admin  |
| `POST` | `/api/pengaduan/{id}/response` | Tambah respon      | Admin  |
| `GET`  | `/api/pengaduan/stats`         | Statistik          | Admin  |

---

## 1. List Pengaduan

`GET /api/pengaduan`

### Query Parameters

| Parameter  | Type   | Deskripsi                                                  |
| ---------- | ------ | ---------------------------------------------------------- |
| `page`     | int    | Halaman (default: 1)                                       |
| `per_page` | int    | Item per halaman (default: 10)                             |
| `status`   | enum   | Filter: `pending`, `in_progress`, `resolved`, `rejected`   |
| `kategori` | enum   | Filter: `error`, `tampilan`, `data`, `performa`, `lainnya` |
| `search`   | string | Cari di judul/deskripsi                                    |

### Response

```json
{
    "data": [
        {
            "id": 1,
            "user": {
                "id": 5,
                "name": "Kader A",
                "email": "kader@example.com"
            },
            "kategori": "error",
            "prioritas": "tinggi",
            "judul": "Tidak bisa simpan data",
            "deskripsi": "Saat klik tombol simpan, muncul error",
            "status": "pending",
            "images": ["pengaduan/abc123.jpg"],
            "responses_count": 0,
            "created_at": "2026-01-06T08:00:00.000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 25
    }
}
```

> **Note**: Kader hanya melihat pengaduan milik sendiri. Admin melihat semua.

---

## 2. Buat Pengaduan

`POST /api/pengaduan` (multipart/form-data)

### Body

| Field                | Type   | Required | Deskripsi                                          |
| -------------------- | ------ | -------- | -------------------------------------------------- |
| `kategori`           | enum   | ✅       | `error`, `tampilan`, `data`, `performa`, `lainnya` |
| `prioritas`          | enum   | ✅       | `rendah`, `sedang`, `tinggi`                       |
| `judul`              | string | ✅       | Maksimal 255 karakter                              |
| `deskripsi`          | text   | ✅       | Penjelasan detail                                  |
| `langkah_reproduksi` | text   | ❌       | Langkah untuk reproduksi bug                       |
| `browser_info`       | text   | ❌       | Info browser/device                                |
| `images[]`           | file   | ❌       | Max 3 file, jpg/png, max 2MB each                  |

### Response (201)

```json
{
    "success": true,
    "data": { "id": 15 }
}
```

---

## 3. Detail Pengaduan

`GET /api/pengaduan/{id}`

### Response

```json
{
    "data": {
        "id": 1,
        "user": { "id": 5, "name": "Kader A" },
        "kategori": "error",
        "prioritas": "tinggi",
        "judul": "Tidak bisa simpan data",
        "deskripsi": "Saat klik tombol simpan, muncul error",
        "langkah_reproduksi": "1. Buka form\\n2. Isi data\\n3. Klik Simpan",
        "browser_info": "Chrome 120, Windows 11",
        "status": "in_progress",
        "images": ["pengaduan/abc123.jpg", "pengaduan/def456.jpg"],
        "responses": [
            {
                "id": 1,
                "admin": { "id": 1, "name": "Admin Posyandu" },
                "response": "Terima kasih laporannya, sedang kami proses",
                "created_at": "2026-01-06T09:00:00.000Z"
            }
        ],
        "created_at": "2026-01-06T08:00:00.000Z"
    }
}
```

---

## 4. Update Status (Admin Only)

`PUT /api/pengaduan/{id}/status`

### Body

```json
{
    "status": "in_progress"
}
```

**Valid values**: `pending`, `in_progress`, `resolved`, `rejected`

### Response

```json
{
    "success": true,
    "message": "Status berhasil diperbarui"
}
```

---

## 5. Tambah Respon Admin (Admin Only)

`POST /api/pengaduan/{id}/response`

### Body

```json
{
    "response": "Terima kasih, sudah diperbaiki di versi terbaru"
}
```

### Response (201)

```json
{
    "success": true,
    "message": "Respon berhasil ditambahkan",
    "data": { "id": 5 }
}
```

---

## 6. Statistik (Admin Only)

`GET /api/pengaduan/stats`

### Response

```json
{
    "data": {
        "pending": 5,
        "in_progress": 2,
        "resolved": 12,
        "rejected": 1,
        "total": 20
    }
}
```

---

## Image Storage

Gambar diupload ke: `storage/app/public/pengaduan/`

Akses via URL: `{APP_URL}/storage/pengaduan/{filename}`

---

## Authentication

Semua endpoint memerlukan header:

```
Authorization: Bearer YOUR_TOKEN
```
