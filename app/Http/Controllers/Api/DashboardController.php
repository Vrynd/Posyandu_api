<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Peserta;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get summary statistics for the dashboard
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
     * Get monthly visit data for charts
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

        $visits = Kunjungan::select(
            DB::raw("DATE_FORMAT(tanggal_kunjungan, '%Y-%m') as month_year"),
            DB::raw('count(*) as total')
        )
            ->where('tanggal_kunjungan', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month_year')
            ->get()
            ->pluck('total', 'month_year');

        $formattedData = $last12Months->map(function ($item) use ($visits) {
            return [
                'label' => $item['month'] . ' ' . $item['year'],
                'total' => $visits->get($item['raw'], 0)
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard chart data retrieved',
            'data' => $formattedData
        ]);
    }
}
