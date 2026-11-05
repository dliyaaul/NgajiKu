<?php

namespace App\Http\Requests;

use App\Models\PembayaranRekening;
use App\Models\Tagihan;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Konfirmasi transfer beserta bukti (gambar atau PDF, maks. 10 MB seperti aplikasi mobile).
 */
class AjukanPembayaranRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('bayar', $this->tagihan());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rekening_tujuan_id' => ['required', 'uuid', Rule::exists(PembayaranRekening::class, 'id')->where('aktif', true)],
            'tanggal_transfer' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'nominal_transfer' => ['required', 'numeric', 'gt:0'],
            'nama_pengirim' => ['nullable', 'string', 'max:255'],
            'rekening_pengirim' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'bukti_transfer' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    /**
     * Nominal harus persis sama dengan total tagihan.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('nominal_transfer')) {
                    return;
                }

                $total = (float) $this->tagihan()->total;

                if (round((float) $this->input('nominal_transfer') * 100) !== round($total * 100)) {
                    $validator->errors()->add(
                        'nominal_transfer',
                        'Nominal transfer harus sama dengan total tagihan (Rp '.number_format($total, 0, ',', '.').').'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rekening_tujuan_id' => 'rekening tujuan',
            'tanggal_transfer' => 'tanggal transfer',
            'nominal_transfer' => 'nominal transfer',
            'nama_pengirim' => 'nama pengirim',
            'rekening_pengirim' => 'rekening pengirim',
            'bukti_transfer' => 'bukti transfer',
        ];
    }

    /**
     * @return array{rekening_tujuan_id: string, tanggal_transfer: string, nominal_transfer: string, nama_pengirim: ?string, rekening_pengirim: ?string, catatan: ?string}
     */
    public function pembayaranData(): array
    {
        return [
            'rekening_tujuan_id' => $this->validated('rekening_tujuan_id'),
            'tanggal_transfer' => $this->validated('tanggal_transfer'),
            'nominal_transfer' => (string) $this->validated('nominal_transfer'),
            'nama_pengirim' => $this->validated('nama_pengirim'),
            'rekening_pengirim' => $this->validated('rekening_pengirim'),
            'catatan' => $this->validated('catatan'),
        ];
    }

    public function tagihan(): Tagihan
    {
        return $this->route('tagihan');
    }
}
