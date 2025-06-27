<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Target\TargetRequest;
use App\Models\Target;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    /**
     * Get all targets with optional filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Target::with(['user', 'relasiPemasukan.pemasukan'])
            ->where('user_id', auth()->id());

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('target_tanggal', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Sorting options
        $sortBy = $request->get('sort_by', 'target_tanggal');
        $sortDirection = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $targets = $query->get()->map(function ($target) {
            return $this->formatTargetResponse($target);
        });

        return response()->json([
            'status' => 'success',
            'data' => $targets
        ]);
    }

    /**
     * Create new target
     */
    public function store(TargetRequest $request): JsonResponse
    {
        try {
            $target = Target::create([
                'user_id' => auth()->id(),
                'nama_target' => $request->nama_target,
                'target_dana' => $request->target_dana,
                'target_tanggal' => $request->target_tanggal,
                'deskripsi' => $request->deskripsi,
                'status' => 'aktif',
                'kategori_target_id' => $request->kategori_target_id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Target berhasil dibuat',
                'data' => $this->formatTargetResponse($target)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat target: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single target with details
     */
    public function show($id): JsonResponse
    {
        $target = Target::with(['relasiPemasukan.pemasukan'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatTargetResponse($target, true)
        ]);
    }

    /**
     * Update target
     */
    public function update(TargetRequest $request, $id): JsonResponse
    {
        $target = Target::where('user_id', auth()->id())
            ->findOrFail($id);

        try {
            // Only allow updating certain fields
            $target->update([
                'nama_target' => $request->nama_target,
                'target_dana' => $request->target_dana,
                'target_tanggal' => $request->target_tanggal,
                'deskripsi' => $request->deskripsi,
                'kategori_target_id' => $request->kategori_target_id
            ]);

            // Recalculate if target dana changed
            if ($target->wasChanged('target_dana')) {
                $target->updateTerkumpul();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Target berhasil diperbarui',
                'data' => $this->formatTargetResponse($target)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui target: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete target
     */
    public function destroy($id): JsonResponse
    {
        $target = Target::where('user_id', auth()->id())
            ->findOrFail($id);

        try {
            // Delete related allocations first
            $target->relasiPemasukan()->delete();
            $target->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Target berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus target: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get target summary statistics
     */
    public function summary(): JsonResponse
    {
        $totalTarget = Target::where('user_id', auth()->id())->count();
        $activeTargets = Target::where('user_id', auth()->id())->active()->count();
        $achievedTargets = Target::where('user_id', auth()->id())
            ->where('status', 'tercapai')
            ->count();

        $totalNeeded = Target::where('user_id', auth()->id())
            ->active()
            ->sum('target_dana');

        $totalCollected = Target::where('user_id', auth()->id())
            ->active()
            ->sum('terkumpul');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_target' => $totalTarget,
                'active_targets' => $activeTargets,
                'achieved_targets' => $achievedTargets,
                'total_needed' => (float) $totalNeeded,
                'total_collected' => (float) $totalCollected,
                'percentage_collected' => $totalNeeded > 0 ?
                    round(($totalCollected / $totalNeeded) * 100, 2) : 0
            ]
        ]);
    }

    /**
     * Format target response consistently
     */
    protected function formatTargetResponse(Target $target, bool $detailed = false): array
    {
        $response = [
            'id' => $target->id,
            'nama_target' => $target->nama_target,
            'target_dana' => (float) $target->target_dana,
            'terkumpul' => (float) $target->terkumpul,
            'target_tanggal' => $target->target_tanggal->format('Y-m-d'),
            'deskripsi' => $target->deskripsi,
            'status' => $target->status,
            'persentase' => $target->getPersentaseTercapai(),
            'sisa_target' => (float) $target->getSisaTarget(),
            'kategori_target_id' => $target->kategori_target_id,
            'created_at' => $target->created_at->toISOString(),
            'updated_at' => $target->updated_at->toISOString(),
        ];

        if ($detailed) {
            $response['alokasi'] = $target->relasiPemasukan->map(function ($relasi) {
                return [
                    'id' => $relasi->id,
                    'jumlah_alokasi' => (float) $relasi->jumlah_alokasi,
                    'tanggal_alokasi' => $relasi->created_at->format('Y-m-d'),
                    'pemasukan' => [
                        'id' => $relasi->pemasukan->id,
                        'jumlah' => (float) $relasi->pemasukan->jumlah,
                        'tanggal' => $relasi->pemasukan->tanggal->format('Y-m-d'),
                        'deskripsi' => $relasi->pemasukan->deskripsi
                    ]
                ];
            });
        }

        return $response;
    }
}