import { Link, router } from '@inertiajs/react';
import { BadgeCheck, Check, ExternalLink, FileText, TriangleAlert, X } from 'lucide-react';
import { useId, useRef, useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { FilterChips } from '@/components/pembayaran/filter-chips';
import { MenuPembayaran } from '@/components/pembayaran/menu-pembayaran';
import { Pager } from '@/components/pembayaran/pager';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import { FieldError } from '@/components/ui/field';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';
import { formatPeriode, formatRupiah, STATUS_PEMBAYARAN } from '@/lib/pembayaran';
import { formatTanggal, formatWaktu } from '@/lib/utils';
import admin from '@/routes/admin';
import type { BreadcrumbItem, Pagination, Pembayaran, StatusPembayaran } from '@/types';

interface Props {
    pembayaran: Pembayaran[];
    pagination: Pagination;
    filter: StatusPembayaran;
    jumlahPerStatus: Record<StatusPembayaran, number>;
}

const URUTAN: StatusPembayaran[] = ['menunggu_verifikasi', 'diterima', 'ditolak'];

export default function VerifikasiPembayaran({ pembayaran, pagination, filter, jumlahPerStatus }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Pembayaran', href: admin.pembayaran.index.url() }, { label: 'Verifikasi' }];

    return (
        <AppLayout title="Verifikasi Pembayaran" breadcrumbs={breadcrumbs}>
            <PageHeader title="Verifikasi Pembayaran" subtitle="Periksa bukti transfer lalu terima atau tolak pembayaran." breadcrumbs={breadcrumbs} />

            <MenuPembayaran menungguVerifikasi={jumlahPerStatus.menunggu_verifikasi} />

            <FilterChips
                label="Filter status pembayaran"
                chips={URUTAN.map((status) => ({
                    label: STATUS_PEMBAYARAN[status].label,
                    href: admin.pembayaran.verifikasi.index.url({ query: { status } }),
                    aktif: filter === status,
                    jumlah: jumlahPerStatus[status],
                }))}
            />

            {pembayaran.length === 0 ? (
                <EmptyState
                    icon={BadgeCheck}
                    title={filter === 'menunggu_verifikasi' ? 'Tidak ada pembayaran yang menunggu' : 'Belum ada data'}
                    description={filter === 'menunggu_verifikasi' ? 'Semua bukti pembayaran sudah diverifikasi.' : undefined}
                />
            ) : (
                <ul className="flex flex-col gap-4">
                    {pembayaran.map((item) => (
                        <KartuPembayaran key={item.id} pembayaran={item} />
                    ))}
                </ul>
            )}

            <Pager pagination={pagination} />
        </AppLayout>
    );
}

function KartuPembayaran({ pembayaran }: { pembayaran: Pembayaran }) {
    const tagihan = pembayaran.tagihan;
    const status = STATUS_PEMBAYARAN[pembayaran.status];
    const nominalBeda = tagihan !== undefined && Math.round(tagihan.total * 100) !== Math.round(pembayaran.nominal_transfer * 100);

    return (
        <li className="overflow-hidden rounded-2xl border border-line bg-surface shadow-xs">
            <div className="grid grid-cols-1 gap-0 md:grid-cols-[12rem_minmax(0,1fr)]">
                <a
                    href={pembayaran.bukti_url}
                    target="_blank"
                    rel="noopener"
                    className="group relative grid min-h-40 place-items-center border-b border-line bg-surface-muted md:border-r md:border-b-0"
                >
                    {pembayaran.bukti_pdf ? (
                        <span className="flex flex-col items-center gap-2 text-sm font-semibold text-fg-muted">
                            <Icon icon={FileText} className="size-8" />
                            Bukti PDF
                        </span>
                    ) : (
                        <img src={pembayaran.bukti_url} alt={`Bukti transfer ${pembayaran.nomor_pembayaran}`} loading="lazy" className="h-full max-h-56 w-full object-contain" />
                    )}
                    <span className="absolute right-2 bottom-2 inline-flex items-center gap-1 rounded-lg bg-surface/90 px-2 py-1 text-xs font-semibold text-fg shadow-xs">
                        <Icon icon={ExternalLink} className="size-3.5" />
                        Buka
                    </span>
                </a>

                <div className="flex flex-col gap-4 p-5">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                            <p className="font-semibold text-fg">{tagihan?.nama_santri ?? tagihan?.nama_lembaga ?? '-'}</p>
                            <p className="text-sm text-fg-muted">
                                <Link href={admin.pembayaran.tagihan.show.url(pembayaran.tagihan_id)} className="tabular-nums hover:underline">
                                    {tagihan?.nomor_tagihan}
                                </Link>
                                {tagihan && <> · {formatPeriode(tagihan.periode_mulai, tagihan.periode_selesai)}</>}
                            </p>
                        </div>
                        <Badge tone={status.tone}>{status.label}</Badge>
                    </div>

                    <dl className="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-3">
                        <div>
                            <dt className="text-fg-muted">Nominal transfer</dt>
                            <dd className="font-semibold text-fg tabular-nums">{formatRupiah(pembayaran.nominal_transfer)}</dd>
                        </div>
                        <div>
                            <dt className="text-fg-muted">Total tagihan</dt>
                            <dd className="font-semibold text-fg tabular-nums">{formatRupiah(tagihan?.total)}</dd>
                        </div>
                        <div>
                            <dt className="text-fg-muted">Tanggal transfer</dt>
                            <dd className="font-medium text-fg">{formatTanggal(pembayaran.tanggal_transfer, 'ringkas')}</dd>
                        </div>
                        <div>
                            <dt className="text-fg-muted">Ke rekening</dt>
                            <dd className="font-medium text-fg">{pembayaran.rekening ? `${pembayaran.rekening.nama_bank} · ${pembayaran.rekening.nomor_rekening}` : '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-fg-muted">Pengirim</dt>
                            <dd className="font-medium text-fg">
                                {pembayaran.nama_pengirim ?? '-'}
                                {pembayaran.rekening_pengirim && <span className="block text-xs text-fg-muted tabular-nums">{pembayaran.rekening_pengirim}</span>}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-fg-muted">Diajukan</dt>
                            <dd className="font-medium text-fg">{pembayaran.created_at ? formatWaktu(pembayaran.created_at) : '-'}</dd>
                        </div>
                    </dl>

                    {nominalBeda && (
                        <p className="flex items-center gap-2 text-sm font-medium text-warning-700 dark:text-warning-300">
                            <Icon icon={TriangleAlert} className="size-4" />
                            Nominal transfer berbeda dari total tagihan; pembayaran tidak dapat diterima.
                        </p>
                    )}

                    {pembayaran.catatan && <p className="text-sm text-fg-muted italic">Catatan pembayar: “{pembayaran.catatan}”</p>}
                    {pembayaran.alasan_penolakan && (
                        <p className="rounded-xl bg-error-50 px-3 py-2 text-sm text-error-800 dark:bg-error-400/10 dark:text-error-200">
                            Alasan penolakan: {pembayaran.alasan_penolakan}
                        </p>
                    )}

                    {pembayaran.status === 'menunggu_verifikasi' && (
                        <div className="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
                            <DialogVerifikasi pembayaran={pembayaran} keputusan="ditolak" />
                            <DialogVerifikasi pembayaran={pembayaran} keputusan="diterima" disabled={nominalBeda} />
                        </div>
                    )}
                </div>
            </div>
        </li>
    );
}

/**
 * Konfirmasi keputusan verifikasi. Menerima menandai tagihan lunas (tidak bisa dibatalkan);
 * menolak wajib menyertakan alasan yang akan dilihat pembayar.
 */
function DialogVerifikasi({ pembayaran, keputusan, disabled = false }: { pembayaran: Pembayaran; keputusan: 'diterima' | 'ditolak'; disabled?: boolean }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [alasan, setAlasan] = useState('');
    const [galat, setGalat] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);
    const id = useId();
    const tolak = keputusan === 'ditolak';

    const kirim = () => {
        if (tolak && alasan.trim() === '') {
            setGalat('Alasan penolakan wajib diisi.');

            return;
        }

        router.put(
            admin.pembayaran.verifikasi.update.url(pembayaran.id),
            tolak ? { status: 'ditolak', catatan: alasan.trim() } : { status: 'diterima' },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onError: (errors) => setGalat(errors.catatan ?? errors.status ?? 'Verifikasi gagal disimpan.'),
                onSuccess: () => dialog.current?.close(),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Button variant={tolak ? 'danger-outline' : 'primary'} icon={tolak ? X : Check} disabled={disabled} onClick={() => dialog.current?.showModal()}>
                {tolak ? 'Tolak' : 'Terima Pembayaran'}
            </Button>

            <dialog
                ref={dialog}
                aria-labelledby={`${id}-judul`}
                aria-describedby={`${id}-isi`}
                className="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl border border-line bg-surface p-0 text-fg shadow-2xl open:animate-pop-in"
            >
                <div className="p-6">
                    <h2 id={`${id}-judul`} className="text-base font-semibold text-fg">
                        {tolak ? 'Tolak' : 'Terima'} pembayaran {pembayaran.nomor_pembayaran}?
                    </h2>
                    <p id={`${id}-isi`} className="mt-1 text-sm text-fg-muted">
                        {tolak
                            ? 'Pembayar akan melihat alasan ini dan dapat mengirim ulang bukti pembayaran.'
                            : `Tagihan ${pembayaran.tagihan?.nomor_tagihan ?? ''} akan ditandai lunas. Pastikan dana ${formatRupiah(pembayaran.nominal_transfer)} sudah masuk ke rekening.`}
                    </p>

                    {tolak && (
                        <>
                            <label htmlFor={`${id}-alasan`} className="mt-4 mb-1.5 block text-sm font-medium text-fg">
                                Alasan penolakan
                            </label>
                            <textarea
                                id={`${id}-alasan`}
                                rows={3}
                                maxLength={1000}
                                value={alasan}
                                onChange={(event) => {
                                    setAlasan(event.target.value);
                                    setGalat(null);
                                }}
                                placeholder="Contoh: Bukti transfer tidak terbaca, mohon unggah ulang."
                                className="form-control h-auto py-2.5"
                                aria-invalid={galat ? true : undefined}
                                aria-describedby={galat ? `${id}-galat` : undefined}
                            />
                        </>
                    )}
                    {galat && <FieldError id={`${id}-galat`}>{galat}</FieldError>}

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button variant="secondary" autoFocus={!tolak} onClick={() => dialog.current?.close()}>
                            Batal
                        </Button>
                        <Button variant={tolak ? 'danger' : 'primary'} icon={tolak ? X : Check} disabled={processing} onClick={kirim}>
                            {tolak ? 'Tolak Pembayaran' : 'Ya, Terima'}
                        </Button>
                    </div>
                </div>
            </dialog>
        </>
    );
}
