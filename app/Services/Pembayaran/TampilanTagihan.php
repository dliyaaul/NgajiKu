<?php

namespace App\Services\Pembayaran;

use App\Enums\StatusTagihan;
use App\Http\Resources\TagihanResource;
use App\Models\Tagihan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Props halaman tagihan (web) yang dipakai bersama halaman admin & santri.
 */
class TampilanTagihan
{
    /**
     * Daftar tagihan terfilter + info halaman.
     *
     * @param  Builder<Tagihan>  $query  sudah dibatasi sesuai pengguna
     * @return array{data: array<int, mixed>, pagination: array{halaman: int, total_halaman: int, total: int, sebelumnya: ?string, berikutnya: ?string}}
     */
    public function daftar(Builder $query, ?StatusTagihan $status, string $cari, Request $request): array
    {
        /** @var LengthAwarePaginator<int, Tagihan> $halaman */
        $halaman = $query->clone()
            ->denganPembayar()
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->cari($cari)
            ->latest('tanggal_tagihan')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return [
            'data' => TagihanResource::collection($halaman->getCollection())->resolve($request),
            'pagination' => [
                'halaman' => $halaman->currentPage(),
                'total_halaman' => $halaman->lastPage(),
                'total' => $halaman->total(),
                'sebelumnya' => $halaman->previousPageUrl(),
                'berikutnya' => $halaman->nextPageUrl(),
            ],
        ];
    }

    /**
     * Jumlah tagihan per status untuk tombol filter.
     *
     * @param  Builder<Tagihan>  $query  sudah dibatasi sesuai pengguna
     * @return array<string, int>
     */
    public function jumlahPerStatus(Builder $query): array
    {
        $jumlah = $query->clone()->toBase()->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        return collect(StatusTagihan::cases())
            ->mapWithKeys(fn (StatusTagihan $status) => [$status->value => (int) ($jumlah[$status->value] ?? 0)])
            ->all();
    }

    /**
     * Detail tagihan: rincian, pembayaran terbaru lebih dulu, dan log statusnya.
     *
     * @return array<string, mixed>
     */
    public function detail(Tagihan $tagihan, Request $request): array
    {
        $tagihan->load([
            'items',
            'pembayaran' => fn ($query) => $query->latest(),
            'pembayaran.rekeningTujuan',
            'pembayaran.log',
            'santriProgram.santri:id,username',
            'santriProgram.program:id,nama',
            'lembaga:id,nama_lembaga',
        ]);

        return (new TagihanResource($tagihan))->resolve($request);
    }
}
