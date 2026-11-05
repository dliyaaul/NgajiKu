import { Link, router } from '@inertiajs/react';
import { ChevronRight, FilePlus2, ReceiptText, Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { FilterChips, type Chip } from '@/components/pembayaran/filter-chips';
import { MenuPembayaran } from '@/components/pembayaran/menu-pembayaran';
import { Pager } from '@/components/pembayaran/pager';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';
import { formatPeriode, formatRupiah, STATUS_TAGIHAN, STATUS_TAGIHAN_URUT } from '@/lib/pembayaran';
import { formatTanggal } from '@/lib/utils';
import admin from '@/routes/admin';
import santri from '@/routes/santri';
import type { BreadcrumbItem, Pagination, StatusTagihan, Tagihan } from '@/types';

interface Props {
    mode: 'admin' | 'santri';
    tagihan: { data: Tagihan[]; pagination: Pagination };
    jumlahPerStatus: Record<StatusTagihan, number>;
    filter: { status: StatusTagihan | null; cari: string };
    menungguVerifikasi: number;
}

export default function DaftarTagihan({ mode, tagihan, jumlahPerStatus, filter, menungguVerifikasi }: Props) {
    const isAdmin = mode === 'admin';
    const judul = isAdmin ? 'Tagihan' : 'Tagihan Saya';
    const breadcrumbs: BreadcrumbItem[] = isAdmin ? [{ label: 'Pembayaran', href: admin.pembayaran.index.url() }, { label: 'Tagihan' }] : [{ label: 'Tagihan' }];
    const [cari, setCari] = useState(filter.cari);

    const daftarUrl = (query: Record<string, string>) =>
        isAdmin ? admin.pembayaran.index.url({ query }) : santri.tagihan.index.url({ query });
    const detailUrl = (id: string) => (isAdmin ? admin.pembayaran.tagihan.show.url(id) : santri.tagihan.show.url(id));
    const queryDasar: Record<string, string> = filter.cari ? { cari: filter.cari } : {};
    const total = Object.values(jumlahPerStatus).reduce((a, b) => a + b, 0);

    const chips: Chip[] = [
        { label: 'Semua', href: daftarUrl(queryDasar), aktif: filter.status === null, jumlah: total },
        ...STATUS_TAGIHAN_URUT.filter((status) => isAdmin || jumlahPerStatus[status] > 0 || filter.status === status).map((status) => ({
            label: STATUS_TAGIHAN[status].label,
            href: daftarUrl({ ...queryDasar, status }),
            aktif: filter.status === status,
            jumlah: jumlahPerStatus[status],
        })),
    ];

    const submitCari = (event: FormEvent) => {
        event.preventDefault();
        router.get(daftarUrl({ ...(filter.status ? { status: filter.status } : {}), ...(cari.trim() ? { cari: cari.trim() } : {}) }), {}, { preserveState: true });
    };

    return (
        <AppLayout title={judul} breadcrumbs={breadcrumbs}>
            <PageHeader
                title={judul}
                subtitle={isAdmin ? 'Pantau tagihan santri & lembaga beserta status pembayarannya.' : 'Tagihan biaya belajar Anda dan status pembayarannya.'}
                breadcrumbs={breadcrumbs}
                actions={
                    isAdmin && (
                        <Button icon={FilePlus2} href={admin.pembayaran.generate.create.url()}>
                            Generate Tagihan
                        </Button>
                    )
                }
            />

            {isAdmin && <MenuPembayaran menungguVerifikasi={menungguVerifikasi} />}

            {isAdmin && (
                <form onSubmit={submitCari} role="search" className="mb-4 flex max-w-xl gap-2">
                    <label htmlFor="cari" className="sr-only">
                        Cari tagihan
                    </label>
                    <div className="relative flex-1">
                        <Icon icon={Search} className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-fg-subtle" />
                        <input
                            id="cari"
                            type="search"
                            value={cari}
                            onChange={(event) => setCari(event.target.value)}
                            placeholder="Nomor tagihan, nama santri, atau lembaga"
                            className="form-control pl-10"
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        Cari
                    </Button>
                </form>
            )}

            <FilterChips label="Filter status tagihan" chips={chips} />

            {tagihan.data.length === 0 ? (
                <EmptyState
                    icon={ReceiptText}
                    title={filter.status || filter.cari ? 'Tidak ada tagihan yang cocok' : 'Belum ada tagihan'}
                    description={
                        isAdmin
                            ? 'Buat tagihan bulanan santri Reguler melalui tombol Generate Tagihan.'
                            : 'Tagihan akan muncul di sini setelah dibuat oleh admin.'
                    }
                />
            ) : (
                <ul className="flex flex-col gap-3">
                    {tagihan.data.map((item) => (
                        <li key={item.id}>
                            <Link
                                href={detailUrl(item.id)}
                                className="group flex items-center gap-4 rounded-2xl border border-line bg-surface p-4 shadow-xs transition-colors hover:border-brand-200 hover:bg-brand-50/40 sm:p-5 dark:hover:border-brand-400/30 dark:hover:bg-brand-400/5"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-semibold text-fg">
                                            {isAdmin ? (item.nama_santri ?? item.nama_lembaga ?? '-') : `Periode ${formatPeriode(item.periode_mulai, item.periode_selesai)}`}
                                        </p>
                                        <Badge tone={STATUS_TAGIHAN[item.status].tone}>{STATUS_TAGIHAN[item.status].label}</Badge>
                                    </div>

                                    <p className="mt-1 text-sm text-fg-muted">
                                        <span className="tabular-nums">{item.nomor_tagihan}</span>
                                        {isAdmin && <> · {formatPeriode(item.periode_mulai, item.periode_selesai)}</>}
                                        {item.nama_program && <> · {item.nama_program}</>}
                                    </p>

                                    {item.tanggal_jatuh_tempo && item.bisa_dibayar && (
                                        <p className="mt-1 text-xs text-fg-subtle">Jatuh tempo {formatTanggal(item.tanggal_jatuh_tempo, 'ringkas')}</p>
                                    )}
                                </div>

                                <p className="text-right text-base font-bold whitespace-nowrap text-fg tabular-nums sm:text-lg">{formatRupiah(item.total)}</p>
                                <Icon icon={ChevronRight} className="size-5 shrink-0 text-fg-subtle transition-transform group-hover:translate-x-0.5" />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}

            <Pager pagination={tagihan.pagination} />
        </AppLayout>
    );
}
