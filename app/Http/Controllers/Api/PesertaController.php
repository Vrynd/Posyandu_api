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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PesertaController extends Controller
{
    /**
     * List all peserta with filtering and search
     */
    public function index(Request $request): JsonResponse
    {
        $query = Peserta::query();

        // Search by name
        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        // Search by NIK (exact)
        if ($request->has('nik')) {
            $query->where('nik_hash', Peserta::hashNik($request->nik));
        }

        // Filter by category
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Filter by RT/RW
        if ($request->has('rt')) {
            $query->where('rt', $request->rt);
        }
        if ($request->has('rw')) {
            $query->where('rw', $request->rw);
        }

        $peserta = $query->with('latestKunjungan')->latest()->paginate($request->get('limit', 15));

        return response()->json([
            'success' => true,
            'message' => 'Peserta list retrieved',
            'data' => $peserta
        ]);
    }

    /**
     * Store a new peserta (Master + Category Specific)
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
     * Get detail of a peserta
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
     * Update peserta
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
     * Remove peserta
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
