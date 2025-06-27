<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pemasukan\PemasukanRequest;
use App\Models\Pemasukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $pemasukan = Pemasukan::where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $pemasukan
        ]);
    }

    /**
     * Update income record with location support
     */
    public function update(PemasukanRequest $request, $id): JsonResponse
    {
        $pemasukan = Pemasukan::where('user_id', auth()->id())
            ->findOrFail($id);

        try {
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
                $validatedData['bukti_transaksi'] = $pemasukan->bukti_transaksi;
            }

            $pemasukan->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Pemasukan berhasil diperbarui',
                'data' => $pemasukan->fresh()
            ]);
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
        $pemasukan = Pemasukan::where('user_id', auth()->id())
            ->findOrFail($id);

        try {
            // Delete associated file if exists
            if ($pemasukan->bukti_transaksi) {
                Storage::disk('public')->delete('bukti_transaksi/' . $pemasukan->bukti_transaksi);
            }

            $pemasukan->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Pemasukan berhasil dihapus'
            ]);
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
                'total' => $total,
                'month' => $request->month,
                'year' => $request->year
            ]
        ]);
    }
}