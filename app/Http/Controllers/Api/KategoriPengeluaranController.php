<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kategori\KategoriPengeluaranRequest;
use App\Models\KategoriPengeluaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KategoriPengeluaranController extends Controller
{
    /**
     * Get all categories for current user
     */
    public function index(): JsonResponse
    {
        $categories = KategoriPengeluaran::where('user_id', auth()->id())
            ->orderBy('nama_kategori')
            ->withCount('pengeluaran')
            ->withSum('pengeluaran', 'jumlah')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * Create new category
     */
    public function store(KategoriPengeluaranRequest $request): JsonResponse
    {
        $category = KategoriPengeluaran::create([
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi,
            'anggaran' => $request->anggaran,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori pengeluaran berhasil dibuat',
            'data' => $category
        ], 201);
    }

    /**
     * Get single category
     */
    public function show($id): JsonResponse
    {
        $category = KategoriPengeluaran::where('user_id', auth()->id())
            ->findOrFail($id);

        $category->load(['pengeluaran' => function($query) {
            $query->orderBy('tanggal', 'desc');
        }]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'kategori' => $category,
                'statistik' => [
                    'total_pengeluaran' => $category->getTotalPengeluaran(),
                    'persentase_anggaran' => $category->getPersentaseAnggaran(),
                    'sisa_anggaran' => $category->getSisaAnggaran(),
                ]
            ]
        ]);
    }

    /**
     * Update category
     */
    public function update(KategoriPengeluaranRequest $request, $id): JsonResponse
    {
        $category = KategoriPengeluaran::where('user_id', auth()->id())
            ->findOrFail($id);

        $category->update([
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi,
            'anggaran' => $request->anggaran
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori pengeluaran berhasil diperbarui',
            'data' => $category
        ]);
    }

    /**
     * Delete category
     */
    public function destroy($id): JsonResponse
    {
        $category = KategoriPengeluaran::where('user_id', auth()->id())
            ->findOrFail($id);

        // Prevent deletion if used in pengeluaran
        if ($category->pengeluaran()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus kategori yang sudah digunakan di transaksi pengeluaran'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori pengeluaran berhasil dihapus'
        ]);
    }

    /**
     * Get daily statistics for a month
     */
    public function dailyStats($id, Request $request): JsonResponse
    {
        $category = KategoriPengeluaran::where('user_id', auth()->id())
            ->findOrFail($id);

        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);

        $month = $request->month;
        $year = $request->year;

        $transactions = $category->pengeluaran()
            ->selectRaw('DAY(tanggal) as day, SUM(jumlah) as total')
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Prepare data for all days in month
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $dailyData = [];
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dailyData[$day] = $transactions->firstWhere('day', $day)->total ?? 0;
        }

        $total = $category->getTotalPengeluaran($month, $year);
        $percentage = $category->getPersentaseAnggaran($month, $year);
        $remaining = $category->getSisaAnggaran($month, $year);

        $message = null;
        if ($transactions->count() <= 1) {
            $message = 'Hanya ada satu transaksi di bulan ini — statistik harian mungkin kurang bermakna.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'total' => $total,
                'presentase' => $percentage,
                'sisa' => $remaining,
                'daily_data' => $dailyData,
                'month' => $month,
                'year' => $year
            ]
        ]);
    }

    /**
     * Get monthly statistics for a year
     */
    public function monthlyStats($id, Request $request): JsonResponse
    {
        $category = KategoriPengeluaran::where('user_id', auth()->id())
            ->findOrFail($id);

        $request->validate([
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);

        $year = $request->year;

        $transactions = $category->pengeluaran()
            ->selectRaw('MONTH(tanggal) as month, SUM(jumlah) as total')
            ->whereYear('tanggal', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Prepare data for all months in year
        $monthlyData = [];
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[$monthNames[$month - 1]] = $transactions->firstWhere('month', $month)->total ?? 0;
        }

        $total = $category->getTotalPengeluaran(null, $year);
        $percentage = $category->getPersentaseAnggaran(null, $year);
        $remaining = $category->getSisaAnggaran(null, $year);

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $total,
                'presentase' => $percentage,
                'sisa' => $remaining,
                'monthly_data' => $monthlyData,
                'year' => $year
            ]
        ]);
    }
}