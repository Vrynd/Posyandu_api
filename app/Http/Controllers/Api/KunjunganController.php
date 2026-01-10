<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\KunjunganBalita;
use App\Models\KunjunganBumil;
use App\Models\KunjunganDewasa;
use App\Models\KunjunganRemaja;
use App\Models\Peserta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KunjunganController extends Controller
{
    /**
     * Riwayat Kunjungan
     * 
     * Menampilkan daftar riwayat kunjungan peserta dengan filter pencarian.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Kunjungan::with('peserta:id,nama,kategori');

        // Filter by peserta
        if ($request->has('peserta_id')) {
            $query->where('peserta_id', $request->peserta_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('tanggal_kunjungan', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('tanggal_kunjungan', '<=', $request->end_date);
        }

        $kunjungan = $query->latest('tanggal_kunjungan')->paginate($request->get('limit', 15));

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan list retrieved',
            'data' => $kunjungan
        ]);
    }

    /**
     * Catat Kunjungan Baru
     * 
     * Membuat data kunjungan baru beserta detail pemeriksaan spesifik kategorinya.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'peserta_id' => 'required|exists:peserta,id',
            'tanggal_kunjungan' => 'required|date',
            'berat_badan' => 'nullable|numeric',
            'rujuk' => 'nullable|boolean',
            'lokasi' => 'nullable|in:posyandu,kunjungan_rumah',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $peserta = Peserta::findOrFail($request->peserta_id);

        try {
            return DB::transaction(function () use ($request, $peserta) {
                // Create Master Kunjungan
                $kunjungan = Kunjungan::create([
                    'peserta_id' => $peserta->id,
                    'tanggal_kunjungan' => $request->tanggal_kunjungan,
                    'berat_badan' => $request->berat_badan,
                    'rujuk' => $request->boolean('rujuk'),
                    'lokasi' => $request->lokasi ?? 'posyandu',
                    'created_by' => Auth::id(),
                ]);

                // Create Detail based on Peserta Category
                $this->createDetailData($kunjungan, $peserta->kategori, $request->all());

                return response()->json([
                    'success' => true,
                    'message' => 'Kunjungan recorded successfully',
                    'data' => $kunjungan->loadDetail()
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat kunjungan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail Kunjungan
     * 
     * Menampilkan informasi lengkap satu record kunjungan beserta data pemeriksaannya.
     */
    public function show($id): JsonResponse
    {
        $kunjungan = Kunjungan::with('peserta')->find($id);

        if (!$kunjungan) {
            return response()->json([
                'success' => false,
                'message' => 'Kunjungan tidak ditemukan'
            ], 404);
        }

        $kunjungan->loadDetail();

        return response()->json([
            'success' => true,
            'data' => $kunjungan
        ]);
    }

    /**
     * Update Data Kunjungan
     * 
     * Memperbarui informasi pada record kunjungan dan data pemeriksaan terkait.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $kunjungan = Kunjungan::with('peserta')->find($id);

        if (!$kunjungan) {
            return response()->json([
                'success' => false,
                'message' => 'Kunjungan tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tanggal_kunjungan' => 'sometimes|date',
            'berat_badan' => 'sometimes|numeric',
            'rujuk' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $kunjungan->loadDetail();

            DB::transaction(function () use ($kunjungan, $request) {
                // Update Master
                $kunjungan->update($request->only(['tanggal_kunjungan', 'berat_badan', 'rujuk', 'lokasi']));

                // Update Detail
                $detail = $kunjungan->detail; // This uses accessor
                if ($detail) {
                    $detail->update($request->all());
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Kunjungan updated successfully',
                'data' => $kunjungan->fresh()->loadDetail()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate kunjungan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus Kunjungan
     * 
     * Menghapus record kunjungan beserta data detail pemeriksaannya secara permanen.
     */
    public function destroy($id): JsonResponse
    {
        $kunjungan = Kunjungan::find($id);

        if (!$kunjungan) {
            return response()->json([
                'success' => false,
                'message' => 'Kunjungan tidak ditemukan'
            ], 404);
        }

        $kunjungan->delete(); // Cascade delete handles category records

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan deleted successfully'
        ]);
    }

    // --- Helpers ---

    private function createDetailData($kunjungan, $kategori, $data)
    {
        switch ($kategori) {
            case 'bumil':
                KunjunganBumil::create(array_merge(['id' => $kunjungan->id], $data));
                break;
            case 'balita':
                KunjunganBalita::create(array_merge(['id' => $kunjungan->id], $data));
                break;
            case 'remaja':
                KunjunganRemaja::create(array_merge(['id' => $kunjungan->id], $data));
                break;
            case 'produktif':
            case 'lansia':
                KunjunganDewasa::create(array_merge(['id' => $kunjungan->id], $data));
                break;
        }
    }
}
