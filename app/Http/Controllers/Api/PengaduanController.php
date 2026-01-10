<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Models\PengaduanImage;
use App\Models\PengaduanResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PengaduanController extends Controller
{
    /**
     * Daftar Pengaduan
     * 
     * Menampilkan daftar pengaduan (bug report) dengan filter status dan kategori.
     * Admin dapat melihat seluruh data, sedangkan Kader hanya melihat miliknya sendiri.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Pengaduan::with(['user:id,name,email', 'images'])
            ->withCount('responses');

        // Role-based filtering: Kader sees own, Admin sees all
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status_new', $request->status);
        }

        // Filter by kategori
        if ($request->has('kategori')) {
            $query->where('kategori_new', $request->kategori);
        }

        // Search by judul or deskripsi
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);
        $result = $query->latest()->paginate($perPage);

        // Transform response
        $data = $result->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'user' => $item->user ? [
                    'id' => $item->user->id,
                    'name' => $item->user->name,
                    'email' => $item->user->email,
                ] : null,
                'kategori' => $item->kategori,
                'prioritas' => $item->prioritas,
                'judul' => $item->judul,
                'deskripsi' => $item->deskripsi,
                'status' => $item->status,
                'images' => $item->images->pluck('image_path')->toArray(),
                'responses_count' => $item->responses_count,
                'created_at' => $item->created_at->toISOString(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ]
        ]);
    }

    /**
     * Buat Pengaduan Baru
     * 
     * Mengirimkan laporan bug atau keluhan baru (mendukung upload gambar).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kategori' => 'required|in:error,tampilan,data,performa,lainnya',
            'prioritas' => 'required|in:rendah,sedang,tinggi',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'langkah_reproduksi' => 'nullable|string',
            'browser_info' => 'nullable|string',
            'images' => 'nullable|array|max:3',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pengaduan = Pengaduan::create([
            'user_id' => Auth::id(),
            'kategori_new' => $request->kategori,
            'prioritas' => $request->prioritas,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'langkah_reproduksi' => $request->langkah_reproduksi,
            'browser_info' => $request->browser_info,
            'status_new' => 'pending',
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('pengaduan', 'public');
                PengaduanImage::create([
                    'pengaduan_id' => $pengaduan->id,
                    'image_path' => $path,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => ['id' => $pengaduan->id],
        ], 201);
    }

    /**
     * Detail Pengaduan
     * 
     * Menampilkan informasi rincian pengaduan beserta riwayat respon dari admin.
     */
    public function show($id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $pengaduan = Pengaduan::with(['user:id,name,email', 'images', 'responses.admin:id,name'])
            ->find($id);

        if (!$pengaduan) {
            return response()->json([
                'success' => false,
                'message' => 'Pengaduan tidak ditemukan',
            ], 404);
        }

        // Check access: Kader can only see own
        if (!$user->isAdmin() && $pengaduan->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak',
            ], 403);
        }

        return response()->json([
            'data' => [
                'id' => $pengaduan->id,
                'user' => $pengaduan->user ? [
                    'id' => $pengaduan->user->id,
                    'name' => $pengaduan->user->name,
                ] : null,
                'kategori' => $pengaduan->kategori,
                'prioritas' => $pengaduan->prioritas,
                'judul' => $pengaduan->judul,
                'deskripsi' => $pengaduan->deskripsi,
                'langkah_reproduksi' => $pengaduan->langkah_reproduksi,
                'browser_info' => $pengaduan->browser_info,
                'status' => $pengaduan->status,
                'images' => $pengaduan->images->pluck('image_path')->toArray(),
                'responses' => $pengaduan->responses->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'admin' => $r->admin ? [
                            'id' => $r->admin->id,
                            'name' => $r->admin->name,
                        ] : null,
                        'response' => $r->response,
                        'created_at' => $r->created_at->toISOString(),
                    ];
                }),
                'created_at' => $pengaduan->created_at->toISOString(),
            ]
        ]);
    }

    /**
     * Update Status Pengaduan (Hanya Admin)
     * 
     * Mengubah status penyelesaian pengaduan (pending, in_progress, resolved, rejected).
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang dapat mengubah status',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,resolved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pengaduan = Pengaduan::find($id);

        if (!$pengaduan) {
            return response()->json([
                'success' => false,
                'message' => 'Pengaduan tidak ditemukan',
            ], 404);
        }

        $pengaduan->update(['status_new' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui',
        ]);
    }

    /**
     * Berikan Respon Admin (Hanya Admin)
     * 
     * Menambahkan tanggapan atau solusi dari admin untuk pengaduan tertentu.
     */
    public function addResponse(Request $request, $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang dapat memberikan respon',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'response' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pengaduan = Pengaduan::find($id);

        if (!$pengaduan) {
            return response()->json([
                'success' => false,
                'message' => 'Pengaduan tidak ditemukan',
            ], 404);
        }

        $response = PengaduanResponse::create([
            'pengaduan_id' => $pengaduan->id,
            'admin_id' => $user->id,
            'response' => $request->response,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respon berhasil ditambahkan',
            'data' => ['id' => $response->id],
        ], 201);
    }

    /**
     * Statistik Pengaduan (Hanya Admin)
     * 
     * Mengambil ringkasan jumlah pengaduan berdasarkan statusnya.
     */
    public function stats(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang dapat melihat statistik',
            ], 403);
        }

        $stats = [
            'pending' => Pengaduan::where('status_new', 'pending')->count(),
            'in_progress' => Pengaduan::where('status_new', 'in_progress')->count(),
            'resolved' => Pengaduan::where('status_new', 'resolved')->count(),
            'rejected' => Pengaduan::where('status_new', 'rejected')->count(),
        ];
        $stats['total'] = array_sum($stats);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
