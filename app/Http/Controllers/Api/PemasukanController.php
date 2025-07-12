<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pemasukan\PemasukanRequest;
use App\Models\Pemasukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PemasukanController extends Controller
{
    /**
     * Get all income records
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pemasukan::with('kategori')
            ->where('user_id', auth()->id());

        // Filter by month and year if provided
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('tanggal', $request->month)
                ->whereYear('tanggal', $request->year);
        }

        // Sorting by date (newest first by default)
        $pemasukan = $query->orderBy('tanggal', 'desc')->get();

        // Transform bukti_transaksi URLs to full paths
        $pemasukan->transform(function ($item) {
            $item->bukti_transaksi = $item->bukti_transaksi
                ? Storage::url('bukti_transaksi/' . $item->bukti_transaksi)
                : null;

            $item->nama_kategori = $item->kategori?->nama_kategori;
            return $item;
        });
        return response()->json([
            'status' => 'success',
            'data' => $pemasukan
        ]);
    }

    /**
     * Create new income record with location support
     */
    public function store(PemasukanRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();

            // Handle file upload if exists
            if ($request->hasFile('bukti_transaksi')) {
                $file = $request->file('bukti_transaksi');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('bukti_transaksi', $filename, 'public');
                $data['bukti_transaksi'] = $filename;
            }

            $pemasukan = Pemasukan::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Pemasukan berhasil ditambahkan',
                'data' => $pemasukan
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan pemasukan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single income record
     */
    public function show($id): JsonResponse
    {
        try { // **PERBAIKAN:** Tambahkan try-catch
           $pemasukan = Pemasukan::with('kategori') // ✅ Include relasi kategori
                ->where('user_id', auth()->id())
                ->findOrFail($id);

             $pemasukan->bukti_transaksi = $pemasukan->bukti_transaksi
                ? Storage::url('bukti_transaksi/' . $pemasukan->bukti_transaksi)
                : null;
                
            $pemasukan->nama_kategori = $pemasukan->kategori?->nama_kategori;   

            return response()->json([
                'status' => 'success',
                'data' => $pemasukan
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pemasukan tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update income record with location support
     */
    public function update(PemasukanRequest $request, $id): JsonResponse
    {
        try {
            $pemasukan = Pemasukan::where('user_id', auth()->id())
                ->findOrFail($id);

            // Validasi data terlebih dahulu
            $validatedData = $request->validated();

            // Handle file upload terpisah
            if ($request->hasFile('bukti_transaksi')) {
                // Delete old file
                if ($pemasukan->bukti_transaksi) {
                    Storage::disk('public')->delete('bukti_transaksi/' . $pemasukan->bukti_transaksi);
                }

                $file = $request->file('bukti_transaksi');
                $filename = 'pemasukan_' . auth()->id() . '_' . time() . '.' . $file->extension();
                $path = $file->storeAs('bukti_transaksi', $filename, 'public');
                $validatedData['bukti_transaksi'] = $filename;
            } else {
                // Pertahankan file yang ada jika tidak diupdate
                // **PERBAIKAN KECIL:** Jika client mengirim 'bukti_transaksi' = null, maka set null.
                // Jika tidak dikirim sama sekali, pertahankan yang lama.
                if ($request->has('bukti_transaksi') && $request->input('bukti_transaksi') === null) {
                    if ($pemasukan->bukti_transaksi) {
                        Storage::disk('public')->delete('bukti_transaksi/' . $pemasukan->bukti_transaksi);
                    }
                    $validatedData['bukti_transaksi'] = null;
                } else {
                    $validatedData['bukti_transaksi'] = $pemasukan->bukti_transaksi;
                }
            }

            $pemasukan->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Pemasukan berhasil diperbarui',
                'data' => $pemasukan->fresh()
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) { // **PERBAIKAN:** Tambahkan penanganan 404
            return response()->json([
                'status' => 'error',
                'message' => 'Pemasukan tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui pemasukan: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Delete income record
     */
    public function destroy($id): JsonResponse
    {
        try { // **PERBAIKAN:** Tambahkan try-catch
            $pemasukan = Pemasukan::where('user_id', auth()->id())
                ->findOrFail($id);

            // Delete associated file if exists
            if ($pemasukan->bukti_transaksi) {
                Storage::disk('public')->delete('bukti_transaksi/' . $pemasukan->bukti_transaksi);
            }

            $pemasukan->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Pemasukan berhasil dihapus'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pemasukan tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus pemasukan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly total income
     */
    public function monthlyTotal(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000'
        ]);

        $total = Pemasukan::where('user_id', auth()->id())
            ->whereMonth('tanggal', $request->month)
            ->whereYear('tanggal', $request->year)
            ->sum('jumlah');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => (float) $total, // **PERBAIKAN:** Pastikan tipe data float
                'month' => (int) $request->month,
                'year' => (int) $request->year
            ]
        ]);
    }

    /**
     * Get total income for the last 12 months, broken down by month.
     * Can optionally filter by 'year'. If no 'year', uses current year.
     */
    public function yearlyTotal(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = $request->input('year', Carbon::now()->year); // Default to current year

        // **PERBAIKAN KECIL:** Sesuaikan rentang tanggal jika Anda hanya ingin data dari tahun yang diminta
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 12, 31)->endOfDay();

        $results = Pemasukan::where('user_id', $user->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        // Fill in months with no transactions with total 0
        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[$i] = 0.0; // Initialize with float
        }
        foreach ($results as $row) {
            $monthlyData[$row->bulan] = (float) $row->total;
        }

        // Convert to format array of objects { 'month': X, 'total': Y }
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
}