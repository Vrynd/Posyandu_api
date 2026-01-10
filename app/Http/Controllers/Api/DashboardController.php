<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Peserta;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Ringkasan Statistik Dashboard
     * 
     * Mengambil data jumlah peserta per kategori dan total kunjungan hari ini.
     */
    public function getStats(): JsonResponse
    {
        $today = Carbon::today();

        // Count participants per category
        $pesertaQuery = Peserta::select('kategori', DB::raw('count(*) as total'))
            ->groupBy('kategori')
            ->get()
            ->pluck('total', 'kategori');

        // Total visits today
        $visitsToday = Kunjungan::whereDate('tanggal_kunjungan', $today)->count();

        // Total all participants
        $totalPeserta = Peserta::count();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard statistics retrieved',
            'data' => [
                'total_peserta' => $totalPeserta,
                'kunjungan_hari_ini' => $visitsToday,
                'kategori' => [
                    'bumil' => $pesertaQuery->get('bumil', 0),
                    'balita' => $pesertaQuery->get('balita', 0),
                    'remaja' => $pesertaQuery->get('remaja', 0),
                    'produktif' => $pesertaQuery->get('produktif', 0),
                    'lansia' => $pesertaQuery->get('lansia', 0),
                ]
            ]
        ]);
    }

    /**
     * Grafik Kunjungan Bulanan
     * 
     * Mengambil data jumlah kunjungan selama 12 bulan terakhir, dikelompokkan per kategori kesehatan.
     * 
     * @response {
     *  "success": true,
     *  "message": "Dashboard chart data retrieved",
     *  "data": [
     *    { 
     *      "label": "Jan 2026", 
     *      "total": 11,
     *      "bumil": 3,
     *      "balita": 2,
     *      "remaja": 2,
     *      "produktif": 2,
     *      "lansia": 2
     *    }
     *  ]
     * }
     */
    public function getChartData(): JsonResponse
    {
        $last12Months = collect([]);
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $last12Months->push([
                'month' => $month->format('M'),
                'year' => $month->format('Y'),
                'raw' => $month->format('Y-m')
            ]);
        }

        $visits = Kunjungan::join('peserta', 'kunjungan.peserta_id', '=', 'peserta.id')
            ->select(
                DB::raw("DATE_FORMAT(kunjungan.tanggal_kunjungan, '%Y-%m') as month_year"),
                'peserta.kategori',
                DB::raw('count(*) as total')
            )
            ->where('kunjungan.tanggal_kunjungan', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month_year', 'peserta.kategori')
            ->get();

        $formattedData = $last12Months->map(function ($item) use ($visits) {
            $monthData = [
                'label' => $item['month'] . ' ' . $item['year'],
                'total' => 0,
                'bumil' => 0,
                'balita' => 0,
                'remaja' => 0,
                'produktif' => 0,
                'lansia' => 0
            ];

            $monthVisits = $visits->where('month_year', $item['raw']);
            foreach ($monthVisits as $visit) {
                $monthData['total'] += $visit->total;
                if (array_key_exists($visit->kategori, $monthData)) {
                    $monthData[$visit->kategori] = (int) $visit->total;
                }
            }

            return $monthData;
        });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard chart data retrieved',
            'data' => $formattedData
        ]);
    }

    /**
     * Grafik Pendaftaran Peserta Baru
     * 
     * Mengambil data jumlah pendaftaran peserta baru per bulan.
     * 
     * @queryParam year Tahun pendaftaran (default: tahun berjalan). Example: 2026
     * 
     * @response {
     *  "success": true,
     *  "message": "Dashboard registration chart data retrieved",
     *  "data": [
     *    { "label": "Jan 2026", "total": 15 },
     *    { "label": "Feb 2026", "total": 8 }
     *  ]
     * }
     */
    public function getRegistrationsChart(Request $request): JsonResponse
    {
        $year = $request->get('year', date('Y'));

        $months = collect([]);
        for ($m = 1; $m <= 12; $m++) {
            $monthDate = Carbon::create($year, $m, 1);
            $months->push([
                'month' => $monthDate->format('M'),
                'year' => $monthDate->format('Y'),
                'raw' => $monthDate->format('Y-m')
            ]);
        }

        $registrations = Peserta::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_year"),
            DB::raw('count(*) as total')
        )
            ->whereYear('created_at', $year)
            ->groupBy('month_year')
            ->get()
            ->pluck('total', 'month_year');

        $formattedData = $months->map(function ($item) use ($registrations) {
            return [
                'label' => $item['month'] . ' ' . $item['year'],
                'total' => $registrations->get($item['raw'], 0)
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard registration chart data retrieved',
            'data' => $formattedData
        ]);
    }
}
