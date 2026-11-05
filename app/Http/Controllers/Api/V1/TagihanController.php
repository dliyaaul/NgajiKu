<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusTagihan;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateTagihanRequest;
use App\Http\Resources\TagihanResource;
use App\Models\Tagihan;
use App\Services\Pembayaran\TagihanRegulerGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TagihanController extends Controller
{
    /**
     * Tagihan yang boleh dilihat pengguna (admin: semua; santri/koordinator: miliknya).
     * Filter: `?status=belum_bayar|menunggu_verifikasi|lunas|ditolak|dibatalkan`, `?cari=`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(StatusTagihan::class)],
            'cari' => ['nullable', 'string', 'max:100'],
        ]);

        return TagihanResource::collection(
            Tagihan::query()
                ->terlihatOleh($request->user())
                ->denganPembayar()
                ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
                ->cari($request->query('cari'))
                ->latest('tanggal_tagihan')
                ->latest()
                ->paginate(20)
                ->withQueryString()
        );
    }

    /**
     * Detail tagihan: rincian item dan riwayat pembayaran beserta log statusnya.
     */
    public function show(Tagihan $tagihan): TagihanResource
    {
        Gate::authorize('view', $tagihan);

        return new TagihanResource($tagihan->load([
            'items',
            'pembayaran' => fn ($query) => $query->latest(),
            'pembayaran.rekeningTujuan',
            'pembayaran.log',
            'santriProgram.santri:id,username',
            'santriProgram.program:id,nama',
            'lembaga:id,nama_lembaga',
        ]));
    }

    /**
     * Tagihan bulanan Reguler. `konfirmasi=false` (bawaan) hanya pratinjau,
     * `konfirmasi=true` membuat tagihan.
     */
    public function generateReguler(GenerateTagihanRequest $request, TagihanRegulerGenerator $generator): JsonResponse
    {
        $hasil = $request->boolean('konfirmasi')
            ? $generator->generate($request->mulai(), $request->selesai(), $request->jatuhTempo(), $request->user())
            : $generator->pratinjau($request->mulai(), $request->selesai());

        return response()->json(['data' => $hasil], $request->boolean('konfirmasi') ? 201 : 200);
    }
}
