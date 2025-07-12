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
        try { // **PERBAIKAN:** Tambahkan try-catch
            $category = KategoriPengeluaran::create([
                'nama_kategori' => $request->nama_kategori,
                'deskripsi' => $request->deskripsi,
                'anggaran' => (float)$request->anggaran, // **PERBAIKAN:** Pastikan anggaran adalah float
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori pengeluaran berhasil dibuat',
                'data' => $category
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat kategori pengeluaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single category
     */
    public function show($id): JsonResponse
    {
        try { // **PERBAIKAN:** Tambahkan try-catch
            $category = KategoriPengeluaran::where('user_id', auth()->id())
                ->findOrFail($id);

            // Load related pengeluaran for the category
            $category->load(['pengeluaran' => function($query) {
                $query->orderBy('tanggal', 'desc');
            }]);

            // **PERBAIKAN:** Pastikan method ini ada di model KategoriPengeluaran atau hitung di sini
            // Aku asumsikan method ini ada di model KategoriPengeluaran
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori pengeluaran tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update category
     */
    public function update(KategoriPengeluaranRequest $request, $id): JsonResponse
    {
        try { // **PERBAIKAN:** Tambahkan try-catch
            $category = KategoriPengeluaran::where('user_id', auth()->id())
                ->findOrFail($id);

            $category->update([
                'nama_kategori' => $request->nama_kategori,
                'deskripsi' => $request->deskripsi,
                'anggaran' => (float)$request->anggaran // **PERBAIKAN:** Pastikan anggaran adalah float
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori pengeluaran berhasil diperbarui',
                'data' => $category
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori pengeluaran tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete category
     */
    public function destroy($id): JsonResponse
    {
        try { // **PERBAIKAN:** Tambahkan try-catch
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori pengeluaran tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily statistics for a month
     */
    public function dailyStats($id, Request $request): JsonResponse
    {
        try { // **PERBAIKAN:** Tambahkan try-catch
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
                $dailyData[$day] = (float)($transactions->firstWhere('day', $day)->total ?? 0); // **PERBAIKAN:** Casting ke float
            }

            // **PERBAIKAN:** Pastikan method getTotalPengeluaran, getPersentaseAnggaran, getSisaAnggaran
            // di model KategoriPengeluaran bisa menerima parameter bulan dan tahun.
            $total = $category->getTotalPengeluaran($month, $year);
            $percentage = $category->getPersentaseAnggaran($month, $year);
            $remaining = $category->getSisaAnggaran($month, $year);

            $message = null;
            if ($transactions->count() === 0) { // **PERBAIKAN:** Jika tidak ada transaksi sama sekali
                $message = 'Tidak ada transaksi di bulan ini.';
            } else if ($transactions->count() <= 1) {
                $message = 'Hanya ada satu transaksi di bulan ini — statistik harian mungkin kurang bermakna.';
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'total' => (float)$total, // **PERBAIKAN:** Casting ke float
                    'presentase' => (float)$percentage, // **PERBAIKAN:** Casting ke float
                    'sisa' => (float)$remaining, // **PERBAIKAN:** Casting ke float
                    'daily_data' => $dailyData,
                    'month' => (int)$month,
                    'year' => (int)$year
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori pengeluaran tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mendapatkan statistik harian: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly statistics for a year
     */
    public function monthlyStats($id, Request $request): JsonResponse
    {
        try { // **PERBAIKAN:** Tambahkan try-catch
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
                $monthlyData[$monthNames[$month - 1]] = (float)($transactions->firstWhere('month', $month)->total ?? 0); // **PERBAIKAN:** Casting ke float
            }

            // **PERBAIKAN:** Pastikan method getTotalPengeluaran, getPersentaseAnggaran, getSisaAnggaran
            // di model KategoriPengeluaran bisa menerima parameter bulan=null dan tahun.
            $total = $category->getTotalPengeluaran(null, $year);
            $percentage = $category->getPersentaseAnggaran(null, $year);
            $remaining = $category->getSisaAnggaran(null, $year);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total' => (float)$total, // **PERBAIKAN:** Casting ke float
                    'presentase' => (float)$percentage, // **PERBAIKAN:** Casting ke float
                    'sisa' => (float)$remaining, // **PERBAIKAN:** Casting ke float
                    'monthly_data' => $monthlyData,
                    'year' => (int)$year
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori pengeluaran tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mendapatkan statistik bulanan: ' . $e->getMessage()
            ], 500);
        }
    }
}