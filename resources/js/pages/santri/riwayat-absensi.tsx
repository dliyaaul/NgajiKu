import { Link } from '@inertiajs/react';
import { CircleAlert, CircleCheck, History, Info, TriangleAlert, type LucideIcon } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { Alert } from '@/components/ui/alert';
import { ProgramBadge, StatusBadge } from '@/components/ui/badge';
import { EmptyState } from '@/components/ui/empty-state';
import { StatCard, type StatTone } from '@/components/ui/stat-card';
import AppLayout from '@/layouts/app-layout';
import { PROGRAM, STATUSES } from '@/lib/program';
import { cn, formatTanggal } from '@/lib/utils';
import santri from '@/routes/santri';
import type { BreadcrumbItem, ProgramKey, RiwayatItem, StatusKehadiran } from '@/types';

interface Props {
    items: RiwayatItem[];
    gagal: string[];
    ringkasan: Record<StatusKehadiran, number>;
    programs: ProgramKey[];
    filter: ProgramKey | null;
}

const STAT: Record<StatusKehadiran, { icon: LucideIcon; tone: StatTone }> = {
    Hadir: { icon: CircleCheck, tone: 'success' },
    Izin: { icon: Info, tone: 'info' },
    Sakit: { icon: TriangleAlert, tone: 'warning' },
    Alpha: { icon: CircleAlert, tone: 'danger' },
};

export default function RiwayatAbsensi({ items, gagal, ringkasan, programs, filter }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Riwayat Absensi' }];
    const filterLinks: Array<{ label: string; program: ProgramKey | null }> = [
        { label: 'Semua', program: null },
        ...programs.map((program) => ({ label: PROGRAM[program].label, program })),
    ];

    return (
        <AppLayout title="Riwayat Absensi" breadcrumbs={breadcrumbs}>
            <PageHeader
                title="Riwayat Absensi"
                subtitle="Rekam kehadiran Anda pada program BBAQ, Tahfidz, dan Tarjamah."
                breadcrumbs={breadcrumbs}
            />

            {/* Filter program */}
            <nav aria-label="Filter program" className="no-scrollbar -mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <ul className="flex w-max gap-2">
                    {filterLinks.map(({ label, program }) => {
                        const aktif = filter === program;

                        return (
                            <li key={label}>
                                <Link
                                    href={santri.riwayatAbsensi.url(program ? { query: { program } } : undefined)}
                                    preserveScroll
                                    aria-current={aktif ? 'page' : undefined}
                                    className={cn(
                                        'inline-flex h-10 items-center rounded-full border px-4 text-sm font-semibold transition-colors',
                                        aktif ? 'border-brand-500 bg-brand-500 text-white' : 'border-line bg-surface text-fg-muted hover:bg-surface-muted hover:text-fg',
                                    )}
                                >
                                    {label}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            {/* Ringkasan */}
            <div className="mb-6 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
                {STATUSES.map((status) => (
                    <StatCard key={status} label={status} value={ringkasan[status]} icon={STAT[status].icon} tone={STAT[status].tone} />
                ))}
            </div>

            {gagal.length > 0 && (
                <Alert tone="warning" className="mb-5">
                    Data absensi {gagal.join(', ')} belum dapat dimuat. Silakan coba lagi nanti.
                </Alert>
            )}

            {items.length === 0 ? (
                <EmptyState icon={History} title="Belum ada riwayat absensi" description="Riwayat akan muncul setelah pengajar mengisi absensi." />
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
                                        Program
                                    </th>
                                    <th scope="col" className="px-5 py-3">
                                        Status
                                    </th>
                                    <th scope="col" className="px-5 py-3">
                                        Pengajar
                                    </th>
                                    <th scope="col" className="px-5 py-3">
                                        Materi / Hafalan
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {items.map((item, index) => (
                                    <tr key={`${item.program}-${item.tanggal}-${index}`} className="align-top">
                                        <td className="px-5 py-4 font-semibold whitespace-nowrap text-fg tabular-nums">{formatTanggal(item.tanggal, 'ringkas')}</td>
                                        <td className="px-5 py-4">
                                            <ProgramBadge program={item.program} />
                                        </td>
                                        <td className="px-5 py-4">
                                            <StatusBadge status={item.status} />
                                        </td>
                                        <td className="px-5 py-4 text-fg">{item.pengajar ?? '-'}</td>
                                        <td className="max-w-80 px-5 py-4">
                                            <DetailRiwayat item={item} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Ponsel: kartu */}
                    <ul className="flex flex-col gap-3 md:hidden">
                        {items.map((item, index) => (
                            <li key={`${item.program}-${item.tanggal}-${index}`} className="rounded-2xl border border-line bg-surface p-4 shadow-xs">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="font-semibold text-fg tabular-nums">{formatTanggal(item.tanggal, 'ringkas')}</p>
                                        <div className="mt-1.5 flex flex-wrap items-center gap-1.5">
                                            <ProgramBadge program={item.program} />
                                            {item.pengajar && <span className="text-sm text-fg-muted">{item.pengajar}</span>}
                                        </div>
                                    </div>
                                    <StatusBadge status={item.status} />
                                </div>

                                {(item.surat || item.materi || item.keterangan) && (
                                    <div className="mt-3 border-t border-line pt-3 text-sm">
                                        <DetailRiwayat item={item} />
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </AppLayout>
    );
}

/** Materi, hafalan, dan keterangan satu baris riwayat absensi. */
function DetailRiwayat({ item }: { item: RiwayatItem }) {
    if (!item.surat && !item.materi && !item.keterangan) {
        return <span className="text-fg-subtle">-</span>;
    }

    return (
        <>
            {item.surat && (
                <p className="font-semibold text-fg">
                    {item.jenis_tahfidz && `${item.jenis_tahfidz} · `}
                    {item.surat}
                    {item.ayat_mulai && (
                        <span className="tabular-nums">
                            : {item.ayat_mulai}–{item.ayat_selesai}
                        </span>
                    )}
                </p>
            )}

            {item.materi && <p className="line-clamp-2 text-fg-muted">{item.materi}</p>}

            {item.keterangan && <p className="mt-0.5 text-xs text-fg-muted italic">{item.keterangan}</p>}
        </>
    );
}
