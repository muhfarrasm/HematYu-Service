<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', '12month');
        $year = $request->get('year', Carbon::now()->year);

        $user = Auth::user();

        if ($range === 'alltime') {
            return $this->getAllTimeData($user);
        }

        return $this->getTwelveMonthsData($user, $year);
    }

    /**
     * Data saldo keseluruhan (tanpa range)
     */
    private function getAllTimeData($user)
    {
        $totalPemasukan = Pemasukan::where('user_id', $user->id)->sum('jumlah') ?? 0;
        $totalPengeluaran = Pengeluaran::where('user_id', $user->id)->sum('jumlah') ?? 0;
        $saldo = $totalPemasukan - $totalPengeluaran;

        // Dapatkan 5 transaksi terakhir pemasukan dan pengeluaran
        $lastPemasukan = Pemasukan::where('user_id', $user->id)
                          ->orderBy('tanggal', 'desc')
                          ->limit(5)
                          ->get();

        $lastPengeluaran = Pengeluaran::where('user_id', $user->id)
                            ->orderBy('tanggal', 'desc')
                            ->limit(5)
                            ->get();

        return response()->json([
            'status' => 'success',
            'range' => 'alltime',
            'current_date' => Carbon::now()->format('Y-m-d'),
            'data' => [
                'summary' => [
                    'total_pemasukan' => (float) $totalPemasukan,
                    'total_pengeluaran' => (float) $totalPengeluaran,
                    'saldo' => (float) $saldo
                ],
                'last_transactions' => [
                    'pemasukan' => $lastPemasukan,
                    'pengeluaran' => $lastPengeluaran
                ]
            ]
        ]);
    }

    /**
     * Data 12 bulan terakhir (full bulan)
     */
    private function getTwelveMonthsData($user, $year)
    {
        $monthlyData = collect();
        $now = Carbon::now();
        
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $month = $date->month;
            $year = $date->year;
            
            $monthlyData->push($this->getMonthlySummary($user, $month, $year, $date));
        }

        return response()->json([
            'status' => 'success',
            'range' => '12month',
            'current_date' => $now->format('Y-m-d'),
            'data' => [
                'history' => $monthlyData,
                'chart_data' => $this->buildChartData($monthlyData, 'M Y'),
                'current_balance' => $monthlyData->sum('saldo')
            ]
        ]);
    }

    /**
     * Ringkasan data bulanan
     */
    private function getMonthlySummary($user, $month, $year, $date)
    {
        $pemasukan = Pemasukan::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->sum('jumlah') ?? 0;

        $pengeluaran = Pengeluaran::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->sum('jumlah') ?? 0;

        return [
            'period' => $date->format('M Y'),
            'pemasukan' => (float) $pemasukan,
            'pengeluaran' => (float) $pengeluaran,
            'saldo' => (float) ($pemasukan - $pengeluaran),
            'month' => $month,
            'year' => $year,
            'type' => 'monthly',
            'is_current' => ($month == Carbon::now()->month && $year == Carbon::now()->year)
        ];
    }

    /**
     * Format data chart
     */
    private function buildChartData($data, $dateFormat)
    {
        return [
            'labels' => $data->pluck('period'),
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $data->pluck('pemasukan'),
                    'backgroundColor' => '#4CAF50',
                    'borderColor' => '#388E3C',
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $data->pluck('pengeluaran'),
                    'backgroundColor' => '#F44336',
                    'borderColor' => '#D32F2F',
                ]
            ]
        ];
    }
}