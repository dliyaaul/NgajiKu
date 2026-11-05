<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RekeningRequest;
use App\Http\Resources\RekeningResource;
use App\Models\PembayaranRekening;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RekeningController extends Controller
{
    /**
     * Rekening tujuan transfer yang aktif. Admin dapat meminta semua dengan `?semua=1`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $semua = $request->boolean('semua') && $request->user()->isAdmin();

        return RekeningResource::collection(
            $semua
                ? PembayaranRekening::query()->orderBy('urutan')->orderBy('nama_bank')->get()
                : PembayaranRekening::query()->aktifUrut()->get()
        );
    }

    public function store(RekeningRequest $request): JsonResponse
    {
        $rekening = PembayaranRekening::create($request->rekeningData() + ['created_by' => $request->user()->id]);

        return (new RekeningResource($rekening))->response()->setStatusCode(201);
    }

    public function update(RekeningRequest $request, PembayaranRekening $rekening): RekeningResource
    {
        $rekening->update($request->rekeningData());

        return new RekeningResource($rekening);
    }

    public function setAktif(Request $request, PembayaranRekening $rekening): RekeningResource
    {
        $request->validate(['aktif' => ['required', 'boolean']]);

        $rekening->update(['aktif' => $request->boolean('aktif')]);

        return new RekeningResource($rekening);
    }
}
