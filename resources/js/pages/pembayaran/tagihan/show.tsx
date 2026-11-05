import { useForm } from '@inertiajs/react';
import { Copy, ExternalLink, FileText, Landmark, Send, Upload } from 'lucide-react';
import { useEffect, useRef, useState, type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { MenuPembayaran } from '@/components/pembayaran/menu-pembayaran';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Field, fieldAria } from '@/components/ui/field';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';
import { formatPeriode, formatRupiah, STATUS_PEMBAYARAN, STATUS_TAGIHAN } from '@/lib/pembayaran';
import { cn, formatTanggal, formatWaktu } from '@/lib/utils';
import admin from '@/routes/admin';
import santri from '@/routes/santri';
import type { BreadcrumbItem, Pembayaran, Rekening, Tagihan } from '@/types';

interface Props {
    mode: 'admin' | 'santri';
    tagihan: Tagihan;
    bisaBayar: boolean;
    rekeningOptions: Rekening[];
}

export default function DetailTagihan({ mode, tagihan, bisaBayar, rekeningOptions }: Props) {
    const isAdmin = mode === 'admin';
    const status = STATUS_TAGIHAN[tagihan.status];
    const breadcrumbs: BreadcrumbItem[] = isAdmin
        ? [{ label: 'Pembayaran', href: admin.pembayaran.index.url() }, { label: 'Tagihan', href: admin.pembayaran.index.url() }, { label: tagihan.nomor_tagihan }]
        : [{ label: 'Tagihan', href: santri.tagihan.index.url() }, { label: tagihan.nomor_tagihan }];
    const pembayaran = tagihan.pembayaran ?? [];
    const ditolakTerakhir = pembayaran.find((item) => item.status === 'ditolak');

    return (
        <AppLayout title={`Tagihan ${tagihan.nomor_tagihan}`} breadcrumbs={breadcrumbs}>
            <PageHeader title={`Tagihan ${formatPeriode(tagihan.periode_mulai, tagihan.periode_selesai)}`} subtitle={tagihan.nomor_tagihan} breadcrumbs={breadcrumbs} />

            {isAdmin && <MenuPembayaran />}

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_22rem] *:min-w-0">
                <div className="flex flex-col gap-5">
                    <Card padded={false}>
                        <div className="flex flex-wrap items-start justify-between gap-4 p-5 sm:p-6">
                            <div>
                                <p className="text-sm text-fg-muted">Total tagihan</p>
                                <p className="mt-1 text-3xl font-bold tracking-tight text-fg tabular-nums">{formatRupiah(tagihan.total)}</p>
                                {tagihan.tanggal_jatuh_tempo && (
                                    <p className="mt-1 text-sm text-fg-muted">Jatuh tempo {formatTanggal(tagihan.tanggal_jatuh_tempo, 'sedang')}</p>
                                )}
                            </div>
                            <Badge tone={status.tone} className="text-sm">
                                {status.label}
                            </Badge>
                        </div>

                        <dl className="grid grid-cols-1 gap-x-6 gap-y-3 border-t border-line p-5 text-sm sm:grid-cols-2 sm:p-6">
                            <Info label={tagihan.tipe_pembayar === 'santri' ? 'Santri' : 'Lembaga'} value={tagihan.nama_santri ?? tagihan.nama_lembaga ?? '-'} />
                            <Info label="Program" value={tagihan.nama_program ?? '-'} />
                            <Info label="Periode" value={formatPeriode(tagihan.periode_mulai, tagihan.periode_selesai)} />
                            <Info label="Tanggal tagihan" value={formatTanggal(tagihan.tanggal_tagihan, 'sedang')} />
                        </dl>
                    </Card>

                    <Card title="Rincian" padded={false}>
                        <ul className="divide-y divide-line">
                            {(tagihan.items ?? []).map((item) => (
                                <li key={item.id} className="flex items-start justify-between gap-4 px-5 py-4 sm:px-6">
                                    <div className="min-w-0">
                                        <p className="font-medium text-fg">{item.deskripsi}</p>
                                        <p className="text-sm text-fg-muted tabular-nums">
                                            {item.jumlah} {item.satuan} × {formatRupiah(item.harga_satuan)}
                                        </p>
                                    </div>
                                    <p className="font-semibold text-fg tabular-nums">{formatRupiah(item.subtotal)}</p>
                                </li>
                            ))}
                            {tagihan.diskon > 0 && (
                                <li className="flex justify-between gap-4 px-5 py-4 text-sm sm:px-6">
                                    <span className="text-fg-muted">Diskon</span>
                                    <span className="font-semibold text-success-700 tabular-nums dark:text-success-300">− {formatRupiah(tagihan.diskon)}</span>
                                </li>
                            )}
                            <li className="flex justify-between gap-4 bg-surface-muted px-5 py-4 sm:px-6">
                                <span className="font-semibold text-fg">Total</span>
                                <span className="font-bold text-fg tabular-nums">{formatRupiah(tagihan.total)}</span>
                            </li>
                        </ul>
                    </Card>

                    <Card title="Riwayat Pembayaran" padded={pembayaran.length === 0}>
                        {pembayaran.length === 0 ? (
                            <p className="text-sm text-fg-muted">Belum ada pembayaran untuk tagihan ini.</p>
                        ) : (
                            <ul className="divide-y divide-line">
                                {pembayaran.map((item) => (
                                    <RiwayatPembayaran key={item.id} pembayaran={item} />
                                ))}
                            </ul>
                        )}
                    </Card>
                </div>

                <aside className="flex flex-col gap-5">
                    {!isAdmin && bisaBayar && (
                        <>
                            {tagihan.status === 'ditolak' && ditolakTerakhir?.alasan_penolakan && (
                                <Alert tone="danger" title="Pembayaran sebelumnya ditolak">
                                    {ditolakTerakhir.alasan_penolakan} Silakan kirim ulang bukti pembayaran.
                                </Alert>
                            )}
                            <FormBayar tagihan={tagihan} rekeningOptions={rekeningOptions} />
                        </>
                    )}

                    {!isAdmin && tagihan.status === 'menunggu_verifikasi' && (
                        <Alert tone="info" title="Menunggu verifikasi">
                            Bukti pembayaran Anda sedang diperiksa admin.
                        </Alert>
                    )}

                    {tagihan.status === 'lunas' && (
                        <Alert tone="success" title="Lunas">
                            Pembayaran tagihan ini sudah diterima.
                        </Alert>
                    )}
                </aside>
            </div>
        </AppLayout>
    );
}

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-fg-muted">{label}</dt>
            <dd className="font-medium text-fg">{value}</dd>
        </div>
    );
}

function RiwayatPembayaran({ pembayaran }: { pembayaran: Pembayaran }) {
    const status = STATUS_PEMBAYARAN[pembayaran.status];

    return (
        <li className="px-5 py-4 sm:px-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="font-semibold text-fg tabular-nums">{formatRupiah(pembayaran.nominal_transfer)}</p>
                        <Badge tone={status.tone}>{status.label}</Badge>
                    </div>
                    <p className="mt-1 text-sm text-fg-muted">
                        Transfer {formatTanggal(pembayaran.tanggal_transfer, 'ringkas')}
                        {pembayaran.rekening && <> ke {pembayaran.rekening.nama_bank}</>}
                        {pembayaran.nama_pengirim && <> · a.n. {pembayaran.nama_pengirim}</>}
                    </p>
                    <p className="text-xs text-fg-subtle tabular-nums">{pembayaran.nomor_pembayaran}</p>
                </div>

                <a
                    href={pembayaran.bukti_url}
                    target="_blank"
                    rel="noopener"
                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:underline dark:text-brand-300"
                >
                    <Icon icon={pembayaran.bukti_pdf ? FileText : ExternalLink} className="size-4" />
                    Lihat bukti
                </a>
            </div>

            {pembayaran.alasan_penolakan && (
                <p className="mt-2 rounded-xl bg-error-50 px-3 py-2 text-sm text-error-800 dark:bg-error-400/10 dark:text-error-200">
                    Alasan penolakan: {pembayaran.alasan_penolakan}
                </p>
            )}

            {pembayaran.catatan && <p className="mt-2 text-sm text-fg-muted italic">“{pembayaran.catatan}”</p>}

            {pembayaran.diverifikasi_at && <p className="mt-1 text-xs text-fg-subtle">Diverifikasi {formatWaktu(pembayaran.diverifikasi_at)}</p>}
        </li>
    );
}

function FormBayar({ tagihan, rekeningOptions }: { tagihan: Tagihan; rekeningOptions: Rekening[] }) {
    const form = useForm<{
        rekening_tujuan_id: string;
        tanggal_transfer: string;
        nominal_transfer: number;
        nama_pengirim: string;
        rekening_pengirim: string;
        catatan: string;
        bukti_transfer: File | null;
    }>({
        rekening_tujuan_id: rekeningOptions[0]?.id ?? '',
        tanggal_transfer: new Date().toLocaleDateString('sv-SE'),
        nominal_transfer: tagihan.total,
        nama_pengirim: '',
        rekening_pengirim: '',
        catatan: '',
        bukti_transfer: null,
    });
    const { data, setData, errors } = form;
    const [disalin, setDisalin] = useState<string | null>(null);
    const adaError = Object.keys(errors).length > 0;
    const errorAlert = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (adaError) {
            errorAlert.current?.focus();
        }
    }, [adaError, errors]);

    const salin = (rekening: Rekening) => {
        navigator.clipboard?.writeText(rekening.nomor_rekening).then(() => setDisalin(rekening.id));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(santri.tagihan.bayar.url(tagihan.id), { forceFormData: true, preserveScroll: true });
    };

    if (rekeningOptions.length === 0) {
        return (
            <Alert tone="warning" title="Rekening belum tersedia">
                Admin belum menyiapkan rekening tujuan transfer. Silakan hubungi admin.
            </Alert>
        );
    }

    return (
        <form onSubmit={submit} noValidate className="rounded-2xl border border-line bg-surface shadow-xs">
            <header className="border-b border-line px-5 py-4">
                <h2 className="text-base font-semibold text-fg">Kirim Bukti Pembayaran</h2>
                <p className="mt-0.5 text-sm text-fg-muted">
                    Transfer tepat <strong className="text-fg tabular-nums">{formatRupiah(tagihan.total)}</strong> ke salah satu rekening berikut.
                </p>
            </header>

            <div className="flex flex-col gap-5 p-5">
                {adaError && (
                    <Alert ref={errorAlert} tone="danger" title="Bukti belum terkirim">
                        Periksa kembali isian yang ditandai.
                    </Alert>
                )}

                <Field as="fieldset" label="Rekening tujuan" htmlFor="rekening_tujuan_id" error={errors.rekening_tujuan_id} required>
                    <div className="flex flex-col gap-2">
                        {rekeningOptions.map((rekening) => (
                            <label
                                key={rekening.id}
                                className={cn(
                                    'flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors',
                                    data.rekening_tujuan_id === rekening.id
                                        ? 'border-brand-500 bg-brand-50 dark:border-brand-400 dark:bg-brand-400/10'
                                        : 'border-line-strong hover:bg-surface-muted',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="rekening_tujuan_id"
                                    value={rekening.id}
                                    checked={data.rekening_tujuan_id === rekening.id}
                                    onChange={() => setData('rekening_tujuan_id', rekening.id)}
                                    className="mt-1 accent-brand-500"
                                />
                                <Icon icon={Landmark} className="mt-0.5 size-5 shrink-0 text-fg-muted" />
                                <span className="min-w-0 flex-1 text-sm">
                                    <span className="block font-semibold text-fg">{rekening.nama_bank}</span>
                                    <span className="block font-mono text-fg tabular-nums">{rekening.nomor_rekening}</span>
                                    <span className="block text-fg-muted">a.n. {rekening.nama_pemilik}</span>
                                    {rekening.keterangan && <span className="block text-xs text-fg-subtle">{rekening.keterangan}</span>}
                                </span>
                                <button
                                    type="button"
                                    onClick={() => salin(rekening)}
                                    className="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-brand-600 hover:bg-brand-50 dark:text-brand-300 dark:hover:bg-brand-400/10"
                                >
                                    <Icon icon={Copy} className="size-3.5" />
                                    {disalin === rekening.id ? 'Tersalin' : 'Salin'}
                                    <span className="sr-only"> nomor rekening {rekening.nama_bank}</span>
                                </button>
                            </label>
                        ))}
                    </div>
                </Field>

                <Field label="Tanggal transfer" htmlFor="tanggal_transfer" error={errors.tanggal_transfer} required>
                    <input
                        type="date"
                        id="tanggal_transfer"
                        value={data.tanggal_transfer}
                        max={new Date().toLocaleDateString('sv-SE')}
                        onChange={(event) => setData('tanggal_transfer', event.target.value)}
                        className="form-control"
                        {...fieldAria('tanggal_transfer', errors.tanggal_transfer)}
                    />
                </Field>

                <Field label="Nominal transfer" htmlFor="nominal_transfer" error={errors.nominal_transfer} hint="Harus sama dengan total tagihan.">
                    <input
                        type="text"
                        id="nominal_transfer"
                        value={formatRupiah(data.nominal_transfer)}
                        readOnly
                        className="form-control bg-surface-muted font-semibold tabular-nums"
                        {...fieldAria('nominal_transfer', errors.nominal_transfer, 'Harus sama dengan total tagihan.')}
                    />
                </Field>

                <Field label="Nama pengirim" htmlFor="nama_pengirim" error={errors.nama_pengirim} optional>
                    <input
                        type="text"
                        id="nama_pengirim"
                        value={data.nama_pengirim}
                        maxLength={255}
                        onChange={(event) => setData('nama_pengirim', event.target.value)}
                        placeholder="Nama pemilik rekening pengirim"
                        className="form-control"
                        {...fieldAria('nama_pengirim', errors.nama_pengirim)}
                    />
                </Field>

                <Field label="Rekening pengirim" htmlFor="rekening_pengirim" error={errors.rekening_pengirim} optional>
                    <input
                        type="text"
                        id="rekening_pengirim"
                        inputMode="numeric"
                        value={data.rekening_pengirim}
                        maxLength={50}
                        onChange={(event) => setData('rekening_pengirim', event.target.value)}
                        className="form-control"
                        {...fieldAria('rekening_pengirim', errors.rekening_pengirim)}
                    />
                </Field>

                <Field label="Bukti transfer" htmlFor="bukti_transfer" error={errors.bukti_transfer} hint="JPG, PNG, WebP, atau PDF. Maksimal 10 MB." required>
                    <input
                        type="file"
                        id="bukti_transfer"
                        accept="image/jpeg,image/png,image/webp,application/pdf"
                        onChange={(event) => setData('bukti_transfer', event.target.files?.[0] ?? null)}
                        className="peer sr-only"
                        {...fieldAria('bukti_transfer', errors.bukti_transfer, 'JPG, PNG, WebP, atau PDF. Maksimal 10 MB.')}
                    />
                    <label
                        htmlFor="bukti_transfer"
                        aria-hidden="true"
                        className="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-line-strong p-4 text-sm transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-surface hover:bg-surface-muted"
                    >
                        <Icon icon={Upload} className="size-5 shrink-0 text-fg-muted" />
                        <span className="min-w-0 flex-1 truncate text-fg">{data.bukti_transfer?.name ?? 'Pilih file bukti transfer'}</span>
                    </label>
                </Field>

                <Field label="Catatan" htmlFor="catatan" error={errors.catatan} optional>
                    <textarea
                        id="catatan"
                        rows={2}
                        maxLength={1000}
                        value={data.catatan}
                        onChange={(event) => setData('catatan', event.target.value)}
                        className="form-control h-auto py-2.5"
                        {...fieldAria('catatan', errors.catatan)}
                    />
                </Field>

                {form.progress && (
                    <progress value={form.progress.percentage} max={100} className="h-2 w-full overflow-hidden rounded-full accent-brand-500">
                        {form.progress.percentage}%
                    </progress>
                )}

                <Button type="submit" icon={Send} disabled={form.processing} className="w-full">
                    Kirim Bukti Pembayaran
                </Button>
            </div>
        </form>
    );
}
