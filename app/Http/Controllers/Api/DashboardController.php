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
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $user = Auth::user();

        if ($range === '1month') {
            return $this->getOneMonthData($user, $month, $year);
        }

        return $this->getTwelveMonthsData($user);
    }

    /**
     * Data 12 bulan terakhir (full bulan)
     */
    private function getTwelveMonthsData($user)
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
                'current_balance' => $monthlyData->last()['saldo']
            ]
        ]);
    }

    /**
     * Data 1 bulan (realtime sampai hari ini untuk bulan berjalan)
     */
    private function getOneMonthData($user, $month, $year)
    {
        $currentDate = Carbon::now();
        $isCurrentMonth = ($month == $currentDate->month && $year == $currentDate->year);
        $targetDate = Carbon::create($year, $month);
        
        $daysInMonth = $isCurrentMonth 
            ? $currentDate->day 
            : $targetDate->daysInMonth;

        $dailyData = collect();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dailyData->push($this->getDailySummary($user, $day, $month, $year, $isCurrentMonth, $currentDate));
        }
        
        return response()->json([
            'status' => 'success',
            'range' => '1month',
            'current_date' => $currentDate->format('Y-m-d'),
            'data' => [
                'history' => $dailyData,
                'chart_data' => $this->buildChartData($dailyData, 'd M'),
                'current_balance' => $dailyData->sum('saldo')
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
     * Ringkasan data harian
     */
    private function getDailySummary($user, $day, $month, $year, $isCurrentMonth, $currentDate)
    {
        $pemasukan = Pemasukan::where('user_id', $user->id)
            ->whereDay('tanggal', $day)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->sum('jumlah') ?? 0;
            
        $pengeluaran = Pengeluaran::where('user_id', $user->id)
            ->whereDay('tanggal', $day)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->sum('jumlah') ?? 0;
        
        return [
            'period' => sprintf('%02d-%s', $day, Carbon::create($year, $month)->format('M')),
            'pemasukan' => (float) $pemasukan,
            'pengeluaran' => (float) $pengeluaran,
            'saldo' => (float) ($pemasukan - $pengeluaran),
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'type' => 'daily',
            'is_current_day' => $isCurrentMonth && $day == $currentDate->day
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