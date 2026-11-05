import { router } from '@inertiajs/react';
import { Pencil, Plus, Power, Tags } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { MenuPembayaran } from '@/components/pembayaran/menu-pembayaran';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/pembayaran';
import { cn, formatTanggal } from '@/lib/utils';
import admin from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';

interface TarifBaris {
    id: string;
    nama_tarif: string;
    jenis: 'reguler' | 'privat' | 'lembaga' | null;
    jenis_label: string;
    target: string;
    nominal: number;
    satuan: string;
    berlaku_mulai: string;
    berlaku_sampai: string | null;
    aktif: boolean;
}

const JENIS_TONE = { reguler: 'brand', privat: 'violet', lembaga: 'amber' } as const;

export default function DaftarTarif({ tarif }: { tarif: TarifBaris[] }) {
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Pembayaran', href: admin.pembayaran.index.url() }, { label: 'Tarif' }];

    return (
        <AppLayout title="Tarif Pembayaran" breadcrumbs={breadcrumbs}>
            <PageHeader
                title="Tarif Pembayaran"
                subtitle="Reguler ditagih per bulan untuk semua santri; Privat & Lembaga per pertemuan."
                breadcrumbs={breadcrumbs}
                actions={
                    <Button icon={Plus} href={admin.pembayaran.tarif.create.url()}>
                        Tambah Tarif
                    </Button>
                }
            />

            <MenuPembayaran />

            {tarif.length === 0 ? (
                <EmptyState icon={Tags} title="Belum ada tarif" description="Tambahkan tarif Reguler agar tagihan bulanan dapat dibuat." />
            ) : (
                <ul className="flex flex-col gap-3">
                    {tarif.map((item) => (
                        <li key={item.id} className={cn('flex flex-wrap items-center gap-4 rounded-2xl border border-line bg-surface p-4 shadow-xs sm:p-5', !item.aktif && 'opacity-70')}>
                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="font-semibold text-fg">{item.nama_tarif}</p>
                                    <Badge tone={item.jenis ? JENIS_TONE[item.jenis] : 'neutral'}>{item.jenis_label}</Badge>
                                    {!item.aktif && <Badge>Nonaktif</Badge>}
                                </div>
                                <p className="mt-1 text-sm text-fg-muted">
                                    {item.target} · berlaku {formatTanggal(item.berlaku_mulai, 'ringkas')}
                                    {item.berlaku_sampai ? ` s.d. ${formatTanggal(item.berlaku_sampai, 'ringkas')}` : ' seterusnya'}
                                </p>
                            </div>

                            <p className="text-right font-bold whitespace-nowrap text-fg tabular-nums">
                                {formatRupiah(item.nominal)}
                                <span className="block text-xs font-medium text-fg-muted">per {item.satuan}</span>
                            </p>

                            <div className="flex gap-2">
                                <Button variant="secondary" size="sm" icon={Pencil} href={admin.pembayaran.tarif.edit.url(item.id)}>
                                    Edit
                                </Button>
                                <Button
                                    variant={item.aktif ? 'ghost' : 'success-outline'}
                                    size="sm"
                                    icon={Power}
                                    onClick={() => router.patch(admin.pembayaran.tarif.aktif.url(item.id), { aktif: !item.aktif }, { preserveScroll: true })}
                                >
                                    {item.aktif ? 'Nonaktifkan' : 'Aktifkan'}
                                    <span className="sr-only"> tarif {item.nama_tarif}</span>
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
