<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kategori\KategoriPemasukanRequest;
use App\Models\KategoriPemasukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KategoriPemasukanController extends Controller
{
    /**
     * Get all categories for current user
     */
    public function index(): JsonResponse
    {
        $categories = KategoriPemasukan::where('user_id', auth()->id())
            ->orderBy('nama_kategori')
            ->withCount('pemasukan')
            ->withSum('pemasukan', 'jumlah')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * Create new category
     */
    public function store(KategoriPemasukanRequest $request): JsonResponse
    {
        $category = KategoriPemasukan::create([
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dibuat',
            'data' => $category
        ], 201);
    }

    /**
     * Get single category
     */
    public function show($id): JsonResponse
    {
        $category = KategoriPemasukan::where('user_id', auth()->id())
            ->with('pemasukan')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $category,
        ]);
    }

    /**
     * Update category
     */
    public function update(KategoriPemasukanRequest $request, $id): JsonResponse
    {
        $category = KategoriPemasukan::where('user_id', auth()->id())
            ->findOrFail($id);

        $category->update([
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category
        ]);
    }

    /**
     * Delete category
     */
    public function destroy($id): JsonResponse
    {
        $category = KategoriPemasukan::where('user_id', auth()->id())
            ->findOrFail($id);

        // Prevent deletion if used in pemasukan
        if ($category->pemasukan()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus kategori yang sudah digunakan di transaksi'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dihapus'
        ]);
    }

    /**
     * Get category usage statistics
     */
    public function stats($id, Request $request): JsonResponse
    {
        $category = KategoriPemasukan::where('user_id', auth()->id())
            ->findOrFail($id);

        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $transactions = $category->pemasukan()
            ->selectRaw('DATE(tanggal) as date, SUM(jumlah) as total')
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->groupBy('date')
            ->get();

        $total = $transactions->sum('total');

        // ✅ Tambahkan pesan jika hanya ada satu transaksi
        $message = null;
        if ($transactions->count() <= 1) {
            $message = 'Hanya ada satu transaksi di bulan ini — statistik harian tidak tersedia.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'total' => $total,
                'transactions' => $transactions,
                'month' => $month,
                'year' => $year
            ]
        ]);
    }
}
