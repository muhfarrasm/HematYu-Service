<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use App\Http\Requests\Pengeluaran\PengeluaranRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PengeluaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ambil semua pengeluaran user tanpa filter bulan/tahun
        $pengeluaran = Pengeluaran::with('kategori')
            ->byUser($user->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        $pengeluaran->transform(function ($item) {
            $item->bukti_transaksi = $item->bukti_transaksi
                ? Storage::url('bukti_transaksi/' . $item->bukti_transaksi)
                : null;

            $item->nama_kategori = $item->kategori?->nama_kategori;
            return $item;
        });

        $total = $pengeluaran->sum('jumlah');

        return response()->json([
            'status' => 'success',
            'data' => $pengeluaran,
            'total' => $total
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(PengeluaranRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        // Hitung total pemasukan dan pengeluaran
        $totalPemasukan = \App\Models\Pemasukan::where('user_id', $user->id)->sum('jumlah');
        $totalPengeluaran = Pengeluaran::where('user_id', $user->id)->sum('jumlah');

        if ($totalPengeluaran + $data['jumlah'] > $totalPemasukan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi. Pengeluaran melebihi pemasukan.'
            ], 400);
        }

        

        // Handle file upload
        if ($request->hasFile('bukti_transaksi')) {
            $file = $request->file('bukti_transaksi');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('bukti_transaksi', $filename, 'public');
            $data['bukti_transaksi'] = $filename;
        }

        $data['user_id'] = $user->id;
        $pengeluaran = Pengeluaran::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengeluaran berhasil ditambahkan',
            'data' => $pengeluaran
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $pengeluaran = Pengeluaran::with('kategori') // ✅ Relasi kategori
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            $pengeluaran->bukti_transaksi = $pengeluaran->bukti_transaksi
                ? Storage::url('bukti_transaksi/' . $pengeluaran->bukti_transaksi)
                : null;

            $pengeluaran->nama_kategori = $pengeluaran->kategori?->nama_kategori;


            return response()->json([
                'status' => 'success',
                'data' => $pengeluaran
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengeluaran tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PengeluaranRequest $request, $id): JsonResponse
    {
        $user = $request->user();
        try {
            $pengeluaran = Pengeluaran::byUser($user->id)->findOrFail($id);
            $data = $request->validated();

            // Hitung total pemasukan dan total pengeluaran (dikurangi pengeluaran yang akan diupdate)
            $totalPemasukan = \App\Models\Pemasukan::where('user_id', $user->id)->sum('jumlah');
            $totalPengeluaranLain = Pengeluaran::where('user_id', $user->id)
                ->where('id', '!=', $pengeluaran->id)
                ->sum('jumlah');

            // Validasi saldo mencukupi
            if ($totalPengeluaranLain + $data['jumlah'] > $totalPemasukan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Saldo tidak mencukupi. Total pengeluaran melebihi pemasukan.'
                ], 400);
            }


            // Handle file upload
            if ($request->hasFile('bukti_transaksi')) {
                // Delete old file if exists
                if ($pengeluaran->bukti_transaksi) {
                    Storage::disk('public')->delete('bukti_transaksi/' . $pengeluaran->bukti_transaksi);
                }

                $file = $request->file('bukti_transaksi');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('bukti_transaksi', $filename, 'public');
                $data['bukti_transaksi'] = $filename;
            }

            $pengeluaran->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil diperbarui',
                'data' => $pengeluaran
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengeluaran tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        try {
            $pengeluaran = Pengeluaran::byUser($user->id)->findOrFail($id);

            if ($pengeluaran->bukti_transaksi) {
                Storage::disk('public')->delete('bukti_transaksi/' . $pengeluaran->bukti_transaksi);
            }

            $pengeluaran->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil dihapus'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengeluaran tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total pengeluaran per bulan
     */
    public function monthlyTotal(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = $request->input('year', date('Y'));

        $results = Pengeluaran::byUser($user->id)
            ->selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total')
            ->whereYear('tanggal', $year)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[$i] = 0.0;
        }
        foreach ($results as $row) {
            $monthlyData[$row->bulan] = (float) $row->total;
        }

        $formattedResults = [];
        foreach ($monthlyData as $monthNum => $total) {
            $formattedResults[] = [
                'month' => $monthNum,
                'total' => $total,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $formattedResults,
            'year' => (int) $year
        ]);
    }

    /**
     * Get summary of expenses per category for a given month and year.
     * Required 'month' and 'year' in request.
     */
    public function monthlyCategorySummary(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000'
        ]);

        $user = $request->user();

        try {
            $summary = Pengeluaran::from('pengeluaran as pengeluarans') // <-- Tambahkan ini
                ->where('pengeluarans.user_id', $user->id)
                ->whereMonth('pengeluarans.tanggal', $request->month)
                ->whereYear('pengeluarans.tanggal', $request->year)
                ->join('kategori_pengeluaran', 'pengeluarans.kategori_id', '=', 'kategori_pengeluaran.id')
                ->selectRaw('kategori_pengeluaran.nama_kategori as category_name, SUM(pengeluarans.jumlah) as total_amount')
                ->groupBy('kategori_pengeluaran.nama_kategori')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $summary,
                'month' => (int) $request->month,
                'year' => (int) $request->year
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in monthlyCategorySummary: {$e->getMessage()} for user {$user->id}, month {$request->month}, year {$request->year}");
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil ringkasan kategori pengeluaran bulanan: ' . $e->getMessage()
            ], 500);
        }
    }
}