<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RelasiTargetPemasukan\RelasiTargetPemasukanRequest;
use App\Models\RelasiTargetPemasukan;
use Illuminate\Http\JsonResponse;

class RelasiTargetPemasukanController extends Controller
{

    /**
     * Get all allocations for current user
     */
    public function index(): JsonResponse
    {
        $relasi = RelasiTargetPemasukan::with(['target', 'pemasukan'])
            ->whereHas('target', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->whereHas('pemasukan', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $relasi
        ]);
    }

    /**
     * Create new allocation
     */
    public function store(RelasiTargetPemasukanRequest $request): JsonResponse
    {
        try {
            // Debug data yang diterima
            logger()->info('Request data:', $request->all());

            // Validasi total alokasi target tidak boleh lebih besar dari target_dana
            $target = \App\Models\Target::findOrFail($request->id_target);
            $totalAlokasiSaatIni = $target->relasiPemasukan()->sum('jumlah_alokasi');
            $totalBaru = $totalAlokasiSaatIni + $request->jumlah_alokasi;

            if ($totalBaru > $target->target_dana) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Jumlah alokasi melebihi target dana. Maksimal sisa alokasi: ' . number_format($target->target_dana - $totalAlokasiSaatIni, 2)
                ], 400);
            }


            $data = [
                'id_target' => $request->id_target,
                'id_pemasukan' => $request->id_pemasukan,
                'jumlah_alokasi' => $request->jumlah_alokasi
            ];

            // Debug data sebelum disimpan
            logger()->info('Data to be saved:', $data);

            $relasi = RelasiTargetPemasukan::create($data);

            // Debug hasil penyimpanan
            logger()->info('Saved data:', $relasi->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Alokasi berhasil ditambahkan',
                'data' => $relasi->load(['target', 'pemasukan'])
            ], 201);
        } catch (\Exception $e) {
            logger()->error('Error saving data:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan alokasi: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get allocation details
     */
    public function show($id): JsonResponse
    {
        $relasi = RelasiTargetPemasukan::with(['target', 'pemasukan'])
            ->whereHas('target', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->whereHas('pemasukan', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $relasi
        ]);
    }

    /**
     * Update allocation
     */
    public function update(RelasiTargetPemasukanRequest $request, $id): JsonResponse
    {
        $relasi = RelasiTargetPemasukan::with(['target', 'pemasukan'])
            ->whereHas('target', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->whereHas('pemasukan', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->findOrFail($id);

        try {
            $data = [
                'id_target' => $request->id_target,
                'id_pemasukan' => $request->id_pemasukan,
                'jumlah_alokasi' => $request->jumlah_alokasi
            ];

            $relasi->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Alokasi berhasil diperbarui',
                'data' => $relasi->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui alokasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete allocation
     */
    public function destroy($id): JsonResponse
    {
        $relasi = RelasiTargetPemasukan::whereHas('target', function ($q) {
            $q->where('user_id', auth()->id());
        })
            ->whereHas('pemasukan', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->findOrFail($id);

        try {
            $relasi->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Alokasi berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus alokasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get allocations by income
     */
    public function byPemasukan($pemasukanId): JsonResponse
    {
        $pemasukan = \App\Models\Pemasukan::where('user_id', auth()->id())
            ->findOrFail($pemasukanId);

        $alokasi = $pemasukan->relasiTarget()
            ->with('target')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $alokasi
        ]);
    }

    /**
     * Get allocations by target
     */
    public function byTarget($targetId): JsonResponse
    {
        $target = \App\Models\Target::where('user_id', auth()->id())
            ->findOrFail($targetId);

        $alokasi = $target->relasiPemasukan()
            ->with('pemasukan')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $alokasi
        ]);
    }

    /**
     * Get summary of allocations by target
     */
    public function summaryByTarget($targetId): JsonResponse
    {
        $target = \App\Models\Target::where('user_id', auth()->id())
            ->findOrFail($targetId);

        $alokasi = $target->relasiPemasukan()
            ->with('pemasukan')
            ->get()
            ->groupBy(function ($item) {
                return $item->pemasukan->tanggal->format('Y-m');
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'target' => $target,
                'alokasi_by_month' => $alokasi,
                'total_alokasi' => $target->terkumpul
            ]
        ]);
    }
}