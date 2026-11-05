<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TarifRequest;
use App\Http\Resources\TarifResource;
use App\Models\PembayaranTarif;
use App\Services\Pembayaran\OpsiTarif;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TarifController extends Controller
{
    /**
     * Semua tarif, terbaru lebih dulu. `?aktif=1` untuk tarif aktif saja.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return TarifResource::collection(
            PembayaranTarif::query()
                ->with('kelasType')
                ->when($request->boolean('aktif'), fn ($query) => $query->where('aktif', true))
                ->latest()
                ->get()
        );
    }

    /**
     * Pilihan jenis layanan, program, lembaga, dan santri Privat untuk formulir tarif.
     */
    public function opsi(OpsiTarif $opsi): JsonResponse
    {
        return response()->json(['data' => $opsi->semua()]);
    }

    public function show(PembayaranTarif $tarif): TarifResource
    {
        return new TarifResource($tarif->load('kelasType'));
    }

    public function store(TarifRequest $request): JsonResponse
    {
        $tarif = PembayaranTarif::create($request->tarifData() + ['created_by' => $request->user()->id]);

        return (new TarifResource($tarif->load('kelasType')))->response()->setStatusCode(201);
    }

    public function update(TarifRequest $request, PembayaranTarif $tarif): TarifResource
    {
        $tarif->update($request->tarifData());

        return new TarifResource($tarif->load('kelasType'));
    }

    public function setAktif(Request $request, PembayaranTarif $tarif): TarifResource
    {
        $request->validate(['aktif' => ['required', 'boolean']]);

        $tarif->update(['aktif' => $request->boolean('aktif')]);

        return new TarifResource($tarif->load('kelasType'));
    }
}
