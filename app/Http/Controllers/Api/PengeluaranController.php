<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use App\Http\Requests\Pengeluaran\PengeluaranRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengeluaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        $pengeluaran = Pengeluaran::with('kategori')
            ->byUser($user->id)
            ->byMonth($month, $year)
            ->orderBy('tanggal', 'desc')
            ->get();

        $total = $pengeluaran->sum('jumlah');

        return response()->json([
            'data' => $pengeluaran,
            'total' => $total,
            'month' => $month,
            'year' => $year
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PengeluaranRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

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
            'message' => 'Pengeluaran berhasil ditambahkan',
            'data' => $pengeluaran
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $pengeluaran = Pengeluaran::where('user_id', auth()->id())
            ->findOrFail($id);


        return response()->json([
            'data' => $pengeluaran
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PengeluaranRequest $request, $id)
    {
        $user = $request->user();
        $pengeluaran = Pengeluaran::byUser($user->id)->findOrFail($id);
        $data = $request->validated();

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
            'message' => 'Pengeluaran berhasil diperbarui',
            'data' => $pengeluaran
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $pengeluaran = Pengeluaran::byUser($user->id)->findOrFail($id);

        // Delete file if exists
        if ($pengeluaran->bukti_transaksi) {
            Storage::disk('public')->delete('bukti_transaksi/' . $pengeluaran->bukti_transaksi);
        }

        $pengeluaran->delete();

        return response()->json([
            'message' => 'Pengeluaran berhasil dihapus'
        ]);
    }

    /**
     * Get total pengeluaran per bulan
     */
    public function monthlyTotal(Request $request)
    {
        $user = $request->user();
        $year = $request->input('year', date('Y'));

        $results = Pengeluaran::byUser($user->id)
            ->selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total')
            ->whereYear('tanggal', $year)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        return response()->json([
            'data' => $results,
            'year' => $year
        ]);
    }
}