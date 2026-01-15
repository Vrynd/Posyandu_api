<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Models\PesertaBalita;
use App\Models\PesertaBumil;
use App\Models\PesertaDewasa;
use App\Models\PesertaRemaja;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PesertaController extends Controller
{
    /**
     * Daftar Peserta (Server-Side Filtering)
     * 
     * Menampilkan daftar peserta dengan filter lanjutan:
     * - search: Nama atau NIK
     * - kategori: Filter per kategori
     * - gender: L (Laki-laki), P (Perempuan)
     * - min_age/max_age: Rentang umur dalam tahun
     * - sort_by: nama, tanggal_lahir, created_at
     * - sort_order: asc, desc
     */
    public function index(Request $request): JsonResponse
    {
        $query = Peserta::query();

        // 0. Dynamic Field Selection (optional)
        if ($fields = $request->input('fields')) {
            $allowedFields = ['id', 'nama', 'nik', 'kategori', 'jenis_kelamin', 'tanggal_lahir', 'telepon', 'rt', 'rw', 'created_at'];
            $selectedFields = array_intersect(explode(',', $fields), $allowedFields);
            if (!empty($selectedFields)) {
                $query->select($selectedFields);
            }
        }

        // 1. Search by Name or NIK
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('nik_hash', Peserta::hashNik($search));
            });
        }

        // 2. Filter by Category
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // 3. Filter by Gender (L/P mapping)
        if ($request->has('gender')) {
            $gender = $request->gender === 'L' ? 'Laki-Laki' : ($request->gender === 'P' ? 'Perempuan' : null);
            if ($gender) {
                $query->where('jenis_kelamin', $gender);
            }
        }

        // 4. Filter by Age Range (min_age, max_age)
        if ($request->has('min_age') || $request->has('max_age')) {
            $now = Carbon::now();
            if ($request->has('min_age')) {
                $query->where('tanggal_lahir', '<=', $now->copy()->subYears($request->min_age)->endOfDay());
            }
            if ($request->has('max_age')) {
                $query->where('tanggal_lahir', '>=', $now->copy()->subYears($request->max_age)->startOfDay());
            }
        }

        // 5. Filter by RT/RW
        if ($request->has('rt')) {
            $query->where('rt', $request->rt);
        }
        if ($request->has('rw')) {
            $query->where('rw', $request->rw);
        }

        // 6. Dynamic Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort_by field to prevent SQL injection
        if (in_array($sortBy, ['nama', 'tanggal_lahir', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $limit = $request->get('limit', 20);
        $peserta = $query->with('latestKunjungan')->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Peserta list retrieved',
            'data' => $peserta
        ]);
    }

    /**
     * Tambah Peserta Baru
     * 
     * Mendaftarkan peserta baru beserta data spesifik kategorinya.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Master data
            'nik' => 'required|string|size:16',
            'nama' => 'required|string|max:255',
            'kategori' => 'required|in:bumil,balita,remaja,produktif,lansia',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:Laki-Laki,Perempuan',
            'rt' => 'nullable|string|max:4',
            'rw' => 'nullable|string|max:4',
            'telepon' => 'nullable|string|max:20',

            // Category specific data
            'nama_suami' => 'required_if:kategori,bumil|string|max:255',
            'nama_ortu' => 'required_if:kategori,balita,remaja|string|max:255',
            'pekerjaan' => 'required_if:kategori,produktif,lansia|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check unique NIK hash
        if (Peserta::findByNik($request->nik)) {
            return response()->json([
                'success' => false,
                'message' => 'NIK sudah terdaftar',
                'errors' => ['nik' => ['NIK sudah terdaftar dalam sistem']]
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                // Create Master
                $peserta = Peserta::create([
                    'nik' => $request->nik,
                    'nik_hash' => Peserta::hashNik($request->nik),
                    'nama' => $request->nama,
                    'kategori' => $request->kategori,
                    'tanggal_lahir' => $request->tanggal_lahir,
                    'jenis_kelamin' => $request->jenis_kelamin,
                    'alamat' => $request->alamat,
                    'rt' => $request->rt,
                    'rw' => $request->rw,
                    'telepon' => $request->telepon,
                    'kepesertaan_bpjs' => $request->boolean('kepesertaan_bpjs'),
                    'nomor_bpjs' => $request->nomor_bpjs,
                ]);

                // Create category record
                $this->createCategoryData($peserta, $request->all());

                return response()->json([
                    'success' => true,
                    'message' => 'Peserta registered successfully',
                    'data' => $peserta->loadExtension()
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftarkan peserta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail Peserta
     * 
     * Menampilkan data lengkap seorang peserta termasuk data ekstensi kategori.
     */
    public function show($id): JsonResponse
    {
        $peserta = Peserta::find($id);

        if ($peserta) {
            $peserta->loadExtension();
        }

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $peserta
        ]);
    }

    /**
     * Update Data Peserta
     * 
     * Memperbarui data master dan data kategori peserta.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $peserta = Peserta::find($id);

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nik' => 'sometimes|string|size:16',
            'nama' => 'sometimes|string|max:255',
            'tanggal_lahir' => 'sometimes|date',
            'telepon' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::transaction(function () use ($peserta, $request) {
                // Update Master
                $data = $request->except(['nik', 'nik_hash', 'kategori']);

                if ($request->has('nik')) {
                    $data['nik'] = $request->nik;
                    $data['nik_hash'] = Peserta::hashNik($request->nik);
                }

                $peserta->update($data);

                // Update category data
                $this->updateCategoryData($peserta, $request->all());
            });

            return response()->json([
                'success' => true,
                'message' => 'Peserta updated successfully',
                'data' => $peserta->fresh()->loadExtension()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate peserta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail Pemeriksaan Terakhir
     * 
     * Mengambil data pemeriksaan kesehatan terbaru untuk seorang peserta.
     * Response ini menggabungkan data master kunjungan dengan detail spesifik kategori.
     * 
     * @response {
     *  "success": true,
     *  "data": {
     *    "id": 1,
     *    "peserta_id": 1,
     *    "tanggal_kunjungan": "2024-01-01",
     *    "kategori": "balita",
     *    "detail": { "panjang_badan": 85.0, "kesimpulan_bb": "NAIK" }
     *  }
     * }
     */
    public function getLatestVisit($id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $peserta = Peserta::find($id);

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan'
            ], 404);
        }

        $latestKunjungan = $peserta->latestKunjungan;

        if (!$latestKunjungan) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }
        // Explicitly load the category detail for this visit
        $latestKunjungan->loadDetail();
        $detail = $latestKunjungan->detail;

        // Map detail based on category
        $detailData = [];

        // Common field across most categories
        $detailData['berat_badan'] = $latestKunjungan->berat_badan;

        if ($detail) {
            // Merge all attributes from the category detail model
            // This ensures we don't miss any fields like ada_gejala_sakit, etc.
            $detailData = array_merge($detailData, $detail->makeHidden(['id', 'timestamps', 'created_at', 'updated_at'])->toArray());

            // Special mappings if needed (e.g. keeping previous agreed keys while adding actual ones)
            if ($peserta->kategori === 'balita') {
                // frontend explicitly asked for tinggi_badan previously, 
                // but now they want consistency with database (panjang_badan).
                // To be safe during transition, we can provide both or just sync to database.
                // The plan says: Use panjang_badan (instead of tinggi_badan).
            }

            if ($peserta->kategori === 'bumil') {
                $detailData['hpht'] = null; // Still not in DB
            }
        }

        // Base response structure following frontend requirements
        $response = [
            'id' => $latestKunjungan->id,
            'peserta_id' => $latestKunjungan->peserta_id,
            'tanggal_kunjungan' => $latestKunjungan->tanggal_kunjungan?->format('Y-m-d'),
            'lokasi_pemeriksaan' => $latestKunjungan->lokasi,
            'kategori' => $peserta->kategori,
            'detail' => $detailData
        ];

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Hapus Peserta
     * 
     * Menghapus data peserta beserta seluruh riwayat kunjungannya secara permanen.
     */
    public function destroy($id): JsonResponse
    {
        $peserta = Peserta::find($id);

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan'
            ], 404);
        }

        $peserta->delete(); // Cascade delete handles category records

        return response()->json([
            'success' => true,
            'message' => 'Peserta deleted successfully'
        ]);
    }

    /**
     * Daftar Ringkas Peserta (Lightweight)
     * 
     * Mengembalikan data minimal untuk list view (70% lebih ringan).
     * Fields: id, nama, nik, kategori, jenis_kelamin
     */
    public function summary(Request $request): JsonResponse
    {
        $query = Peserta::select(['id', 'nama', 'nik', 'kategori', 'jenis_kelamin']);

        // Basic filtering
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('nik_hash', Peserta::hashNik($search));
            });
        }

        $limit = $request->get('limit', 50);
        $peserta = $query->orderBy('nama')->paginate($limit);

        // Generate ETag for caching
        $etag = md5($peserta->toJson());
        $lastModified = Peserta::max('updated_at');

        return response()->json([
            'success' => true,
            'message' => 'Peserta summary retrieved',
            'data' => $peserta
        ])->header('ETag', '"' . $etag . '"')
            ->header('Last-Modified', $lastModified ? Carbon::parse($lastModified)->toRfc7231String() : now()->toRfc7231String());
    }

    /**
     * Hapus Banyak Peserta (Bulk Delete)
     * 
     * Menghapus beberapa peserta sekaligus dalam satu request.
     * Body: { "ids": [1, 2, 3] }
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:peserta,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $deletedCount = Peserta::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "Berhasil menghapus {$deletedCount} peserta"
        ]);
    }

    // --- Helpers ---

    private function createCategoryData($peserta, $data)
    {
        switch ($peserta->kategori) {
            case 'bumil':
                PesertaBumil::create([
                    'peserta_id' => $peserta->id,
                    'nama_suami' => $data['nama_suami'] ?? null,
                    'hamil_anak_ke' => $data['hamil_anak_ke'] ?? null,
                    'jarak_anak' => $data['jarak_anak'] ?? null,
                    'bb_sebelum_hamil' => $data['bb_sebelum_hamil'] ?? null,
                    'tinggi_badan' => $data['tinggi_badan'] ?? null,
                ]);
                break;
            case 'balita':
                PesertaBalita::create([
                    'peserta_id' => $peserta->id,
                    'nama_ortu' => $data['nama_ortu'] ?? null,
                ]);
                break;
            case 'remaja':
                PesertaRemaja::create([
                    'peserta_id' => $peserta->id,
                    'nama_ortu' => $data['nama_ortu'] ?? null,
                    'riwayat_keluarga' => $data['riwayat_keluarga'] ?? null,
                    'perilaku_berisiko' => $data['perilaku_berisiko'] ?? null,
                ]);
                break;
            case 'produktif':
            case 'lansia':
                PesertaDewasa::create([
                    'peserta_id' => $peserta->id,
                    'pekerjaan' => $data['pekerjaan'] ?? null,
                    'status_perkawinan' => $data['status_perkawinan'] ?? null,
                    'riwayat_diri' => $data['riwayat_diri'] ?? null,
                    'merokok' => $data['merokok'] ?? false,
                    'konsumsi_gula' => $data['konsumsi_gula'] ?? false,
                    'konsumsi_garam' => $data['konsumsi_garam'] ?? false,
                    'konsumsi_lemak' => $data['konsumsi_lemak'] ?? false,
                ]);
                break;
        }
    }

    private function updateCategoryData($peserta, $data)
    {
        $ext = $peserta->extension;
        if ($ext) {
            $ext->update($data);
        }
    }
}
