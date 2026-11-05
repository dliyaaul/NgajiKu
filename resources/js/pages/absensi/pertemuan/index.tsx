import { Calendar, Clock, Lock, Pencil, Plus, Trash2 } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { Badge, StatusBadge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { EmptyState } from '@/components/ui/empty-state';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';
import { PROGRAM } from '@/lib/program';
import { cn, formatTanggal, sisaWaktu } from '@/lib/utils';
import absensi from '@/routes/absensi';
import type { BreadcrumbItem, KelasRingkas, ProgramKey, Rekap } from '@/types';

interface Pertemuan {
    id: string;
    tanggal: string;
    metode: string | null;
    pengajar: string | null;
    pengganti: string | null;
    materi: string | null;
    rekap: Rekap;
    editable: boolean;
    batas_edit: string | null;
}

interface Props {
    program: ProgramKey;
    kelas: KelasRingkas;
    pertemuanList: Pertemuan[];
}

export default function PertemuanIndex({ program, kelas, pertemuanList }: Props) {
    const label = PROGRAM[program].label;
    const breadcrumbs: BreadcrumbItem[] = [
        { label: 'Absensi', href: absensi.index.url() },
        { label, href: absensi.kelas.url(program) },
        { label: kelas.nama_kelas },
    ];
    const tambahUrl = absensi.pertemuan.create.url({ program, kelas: kelas.id });

    return (
        <AppLayout title={`Pertemuan ${label}`} breadcrumbs={breadcrumbs}>
            <PageHeader
                title={kelas.nama_kelas}
                subtitle={`Daftar pertemuan ${label}.`}
                breadcrumbs={breadcrumbs}
                actions={
                    <Button href={tambahUrl} icon={Plus}>
                        Tambah Pertemuan
                    </Button>
                }
            />

            <p className="-mt-2 mb-5 flex items-center gap-2 text-sm text-fg-muted">
                <Icon icon={Clock} className="size-4" />
                Absensi dapat diisi atau diubah maksimal 24 jam sejak pertemuan dibuat.
            </p>

            {pertemuanList.length === 0 ? (
                <EmptyState icon={Calendar} title="Belum ada pertemuan" description="Tambahkan pertemuan untuk mulai mengisi absensi santri.">
                    <Button href={tambahUrl} icon={Plus}>
                        Tambah Pertemuan
                    </Button>
                </EmptyState>
            ) : (
                <>
                    {/* Desktop: tabel */}
                    <div className="hidden overflow-hidden rounded-2xl border border-line bg-surface shadow-xs md:block">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-line bg-surface-muted text-xs font-semibold tracking-wide text-fg-muted uppercase">
                                <tr>
                                    <th scope="col" className="px-5 py-3">
                                        Tanggal
                                    </th>
                                    <th scope="col" className="px-5 py-3">
                                        Pengajar
                                    </th>
                                    <th scope="col" className="px-5 py-3">
                                        Materi
                                    </th>
                                    <th scope="col" className="px-5 py-3">
                                        Kehadiran
                                    </th>
                                    <th scope="col" className="px-5 py-3">
                                        Batas Edit
                                    </th>
                                    <th scope="col" className="px-5 py-3 text-right">
                                        <span className="sr-only">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {pertemuanList.map((pertemuan) => (
                                    <tr key={pertemuan.id} className="align-top transition-colors hover:bg-surface-muted/60">
                                        <td className="px-5 py-4">
                                            <p className="font-semibold whitespace-nowrap text-fg">{formatTanggal(pertemuan.tanggal, 'sedang')}</p>
                                            <p className="mt-0.5 text-xs text-fg-muted">{pertemuan.metode ?? '-'}</p>
                                        </td>
                                        <td className="px-5 py-4 text-fg">
                                            {pertemuan.pengajar ?? '-'}
                                            {pertemuan.pengganti && <p className="mt-0.5 text-xs text-fg-muted">Pengganti: {pertemuan.pengganti}</p>}
                                        </td>
                                        <td className="max-w-64 px-5 py-4 text-fg-muted">
                                            <p className="line-clamp-2">{pertemuan.materi || '-'}</p>
                                        </td>
                                        <td className="px-5 py-4">
                                            <RekapBadges rekap={pertemuan.rekap} />
                                        </td>
                                        <td className="px-5 py-4 whitespace-nowrap">
                                            <BatasEditBadge pertemuan={pertemuan} />
                                        </td>
                                        <td className="px-5 py-4">
                                            <Aksi program={program} pertemuan={pertemuan} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Ponsel: kartu */}
                    <ul className="flex flex-col gap-3 md:hidden">
                        {pertemuanList.map((pertemuan) => (
                            <li key={pertemuan.id} className="rounded-2xl border border-line bg-surface p-4 shadow-xs">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="font-semibold text-fg">{formatTanggal(pertemuan.tanggal, 'sedang')}</p>
                                        <p className="mt-0.5 text-sm text-fg-muted">
                                            {pertemuan.metode ?? '-'} · {pertemuan.pengajar ?? '-'}
                                        </p>
                                    </div>

                                    <BatasEditBadge pertemuan={pertemuan} />
                                </div>

                                {pertemuan.materi && <p className="mt-3 line-clamp-2 text-sm text-fg-muted">{pertemuan.materi}</p>}

                                <div className="mt-3">
                                    <RekapBadges rekap={pertemuan.rekap} />
                                </div>

                                <div className="mt-4 border-t border-line pt-4">
                                    <Aksi program={program} pertemuan={pertemuan} penuh />
                                </div>
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </AppLayout>
    );
}

function RekapBadges({ rekap }: { rekap: Rekap }) {
    const entries = Object.entries(rekap);

    return (
        <div className="flex flex-wrap gap-1">
            {entries.length === 0 ? (
                <Badge tone="warning">Belum diisi</Badge>
            ) : (
                entries.map(([status, jumlah]) => <StatusBadge key={status} status={status} count={jumlah} />)
            )}
        </div>
    );
}

function BatasEditBadge({ pertemuan }: { pertemuan: Pertemuan }) {
    if (pertemuan.editable && pertemuan.batas_edit) {
        return (
            <Badge tone="success" icon={Clock}>
                {sisaWaktu(pertemuan.batas_edit)} lagi
            </Badge>
        );
    }

    return (
        <Badge tone="neutral" icon={Lock}>
            Terkunci
        </Badge>
    );
}

/** Aksi per pertemuan. `penuh` = tombol melebar (tampilan kartu di ponsel). */
function Aksi({ program, pertemuan, penuh = false }: { program: ProgramKey; pertemuan: Pertemuan; penuh?: boolean }) {
    const args = { program, pertemuan: pertemuan.id };

    return (
        <div className={cn('flex gap-2', !penuh && 'justify-end')}>
            <Button
                size="sm"
                href={absensi.show.url(args)}
                variant={pertemuan.editable ? 'primary' : 'secondary'}
                className={cn(penuh && 'flex-1')}
            >
                {pertemuan.editable ? 'Isi Absensi' : 'Lihat'}
            </Button>

            {pertemuan.editable && (
                <>
                    <Button size="sm" variant="secondary" icon={Pencil} href={absensi.pertemuan.edit.url(args)}>
                        Edit
                    </Button>

                    <ConfirmDialog
                        title="Hapus pertemuan ini?"
                        url={absensi.pertemuan.destroy.url(args)}
                        trigger={(open) => (
                            <Button size="sm" variant="danger-outline" icon={Trash2} onClick={open}>
                                Hapus<span className="sr-only"> pertemuan {pertemuan.tanggal}</span>
                            </Button>
                        )}
                    >
                        Pertemuan {formatTanggal(pertemuan.tanggal, 'sedang')} beserta seluruh data absensinya akan dihapus permanen.
                    </ConfirmDialog>
                </>
            )}
        </div>
    );
}
