<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kategori\KategoriTargetRequest;
use App\Models\KategoriTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KategoriTargetController extends Controller
{
    /**
     * Get all target categories for current user
     */
    public function index(): JsonResponse
    {
        $categories = KategoriTarget::where('user_id', auth()->id())
            ->orderBy('nama_kategori')
            ->withCount('targets')
            ->withSum('targets', 'target_dana')
            ->withSum('targets', 'terkumpul')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'nama_kategori' => $category->nama_kategori,
                    'deskripsi' => $category->deskripsi,
                    'user_id' => $category->user_id,
                    'jumlah_target' => $category->targets_count,
                    'total_target_dana' => (float) $category->targets_sum_target_dana,
                    'total_terkumpul' => (float) $category->targets_sum_terkumpul,
                    'persentase_pencapaian' => $category->targets_sum_target_dana > 0 ?
                        round(($category->targets_sum_terkumpul / $category->targets_sum_target_dana) * 100, 2) : 0,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at
                ];
            })
        ]);
    }

    /**
     * Create new target category
     */
    public function store(KategoriTargetRequest $request): JsonResponse
    {
        $category = KategoriTarget::create([
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori target berhasil dibuat',
            'data' => $category
        ], 201);
    }

    /**
     * Get single target category with detailed statistics
     */
    public function show($id): JsonResponse
    {
        $category = KategoriTarget::where('user_id', auth()->id())
            ->findOrFail($id);

        $category->load([
            'targets' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'kategori' => $category,
                'statistik' => [
                    'total_target_dana' => $category->getTotalTarget(),
                    'total_terkumpul' => $category->getTotalTerkumpul(),
                    'persentase_pencapaian' => $category->getPersentasePencapaian(),
                    'jumlah_target_aktif' => $category->getJumlahTargetAktif(),
                    'jumlah_target_tercapai' => $category->getJumlahTargetTercapai(),
                    'jumlah_target_tidak_tercapai' => $category->getJumlahTargetTidakTercapai(),
                    'targets_by_status' => $category->getTargetsByStatus()
                ]
            ]
        ]);
    }

    /**
     * Update target category
     */
    public function update(KategoriTargetRequest $request, $id): JsonResponse
    {
        $category = KategoriTarget::where('user_id', auth()->id())
            ->findOrFail($id);

        $category->update([
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori target berhasil diperbarui',
            'data' => $category
        ]);
    }

    /**
     * Delete target category
     */
    public function destroy($id): JsonResponse
    {
        $category = KategoriTarget::where('user_id', auth()->id())
            ->findOrFail($id);

        // Prevent deletion if used in targets
        if ($category->targets()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus kategori yang sudah digunakan dalam target'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori target berhasil dihapus'
        ]);
    }

    /**
     * Get monthly statistics for targets in this category
     */
    public function monthlyStats($id, Request $request): JsonResponse
    {
        $category = KategoriTarget::where('user_id', auth()->id())
            ->findOrFail($id);

        $request->validate([
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);

        $year = $request->year;

        // Get monthly data for targets created in this category
        $targetsData = $category->targets()
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count, SUM(target_dana) as total_target, SUM(terkumpul) as total_terkumpul')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Get monthly data for achieved targets (use target_tanggal instead of tanggal_tercapai)
        $achievedData = $category->targets()
            ->where('status', 'tercapai')
            ->selectRaw('MONTH(target_tanggal) as month, COUNT(*) as achieved_count, SUM(target_dana) as achieved_target, SUM(terkumpul) as achieved_terkumpul')
            ->whereYear('target_tanggal', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Prepare data for all months in year
        $monthlyData = [];
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        for ($month = 1; $month <= 12; $month++) {
            $targetMonth = $targetsData->firstWhere('month', $month);
            $achievedMonth = $achievedData->firstWhere('month', $month);

            $monthlyData[$monthNames[$month - 1]] = [
                'target_count' => $targetMonth->count ?? 0,
                'total_target' => (float) ($targetMonth->total_target ?? 0),
                'total_terkumpul' => (float) ($targetMonth->total_terkumpul ?? 0),
                'achieved_count' => $achievedMonth->achieved_count ?? 0,
                'achieved_target' => (float) ($achievedMonth->achieved_target ?? 0),
                'achieved_terkumpul' => (float) ($achievedMonth->achieved_terkumpul ?? 0),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'monthly_data' => $monthlyData,
                'year' => $year,
                'total_statistics' => [
                    'total_targets' => $category->targets()->whereYear('created_at', $year)->count(),
                    'total_target_dana' => $category->targets()->whereYear('created_at', $year)->sum('target_dana'),
                    'total_terkumpul' => $category->targets()->whereYear('created_at', $year)->sum('terkumpul'),
                    'total_achieved' => $category->targets()->where('status', 'tercapai')->whereYear('created_at', $year)->count(),
                    'achievement_rate' => $category->targets()->whereYear('created_at', $year)->avg(
                        DB::raw('CASE WHEN status = "tercapai" THEN 1 ELSE 0 END')
                    ) * 100
                ]
            ]
        ]);
    }
}