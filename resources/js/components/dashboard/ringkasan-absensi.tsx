import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { ArrowRight, Calendar, ChevronRight, CircleCheck, ClipboardList, Clock, Lock, TrendingUp, Users } from 'lucide-react';

import { Alert } from '@/components/ui/alert';
import { Badge, ProgramBadge, ProgramIcon, StatusBadge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Icon } from '@/components/ui/icon';
import { StatCard } from '@/components/ui/stat-card';
import { PROGRAM } from '@/lib/program';
import { formatTanggal, sisaWaktu } from '@/lib/utils';
import absensi from '@/routes/absensi';
import type { PertemuanRingkas, ProgramKey, Rekap } from '@/types';

export interface RingkasanAbsensiProps {
    programs: Array<{ program: ProgramKey; jumlah_kelas: number }>;
    gagal: string[];
    rentangHari: number;
    jumlahKelas: number;
    jumlahPertemuan: number;
    persenHadir: number | null;
    terbuka: PertemuanRingkas[];
    belumDiisi: number;
    terbaru: PertemuanRingkas[];
}

function RekapBadges({ rekap, kosong }: { rekap: Rekap; kosong: ReactNode }) {
    const entries = Object.entries(rekap);

    if (entries.length === 0) {
        return <>{kosong}</>;
    }

    return (
        <>
            {entries.map(([status, jumlah]) => (
                <StatusBadge key={status} status={status} count={jumlah} />
            ))}
        </>
    );
}

/**
 * Ringkasan absensi untuk dashboard admin & pengajar.
 * `cakupan`: teks "kelas Anda" / "seluruh lembaga".
 */
export function RingkasanAbsensi({ cakupan, ...data }: RingkasanAbsensiProps & { cakupan: string }) {
    const { gagal, rentangHari, persenHadir, terbuka, belumDiisi, terbaru } = data;

    return (
        <>
            {gagal.length > 0 && (
                <Alert tone="warning" className="mb-6">
                    Data {gagal.join(', ')} belum dapat dimuat, sehingga angka di bawah belum lengkap. Muat ulang halaman beberapa saat lagi.
                </Alert>
            )}

            {/* Angka ringkas */}
            <div className="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <StatCard label="Kelas aktif" value={data.jumlahKelas} icon={Users} hint={`Pada ${cakupan}`} />
                <StatCard label="Pertemuan" value={data.jumlahPertemuan} icon={Calendar} tone="info" hint={`${rentangHari} hari terakhir`} />
                <StatCard
                    label="Kehadiran"
                    value={persenHadir === null ? '—' : `${persenHadir}%`}
                    icon={TrendingUp}
                    tone="success"
                    hint={persenHadir === null ? 'Belum ada absensi tercatat' : `Santri hadir, ${rentangHari} hari terakhir`}
                />
                <StatCard
                    label="Belum diisi"
                    value={belumDiisi}
                    icon={ClipboardList}
                    tone={belumDiisi > 0 ? 'warning' : 'neutral'}
                    hint={`Dari ${terbuka.length} absensi yang masih terbuka`}
                />
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Perlu tindakan */}
                <Card
                    title="Absensi masih terbuka"
                    description="Bisa diisi atau diubah hingga 24 jam sejak pertemuan dibuat."
                    padded={false}
                    className="lg:col-span-2"
                >
                    {terbuka.length === 0 ? (
                        <div className="flex flex-col items-center px-6 py-10 text-center">
                            <span className="mb-3 grid size-11 place-items-center rounded-2xl bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300">
                                <Icon icon={CircleCheck} className="size-5" />
                            </span>
                            <p className="font-semibold text-fg">Tidak ada absensi yang menunggu</p>
                            <p className="mt-1 max-w-sm text-sm text-fg-muted">Buat pertemuan baru dari menu Absensi untuk mulai mencatat kehadiran.</p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-line">
                            {terbuka.map((item) => (
                                <li key={item.id} className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                    <div className="flex min-w-0 items-start gap-3">
                                        <ProgramIcon program={item.program} size="sm" />

                                        <div className="min-w-0">
                                            <p className="truncate font-semibold text-fg">{item.kelas}</p>
                                            <p className="text-sm text-fg-muted">
                                                {PROGRAM[item.program].label} · {formatTanggal(item.tanggal)}
                                            </p>

                                            <div className="mt-2 flex flex-wrap gap-1.5">
                                                <RekapBadges
                                                    rekap={item.rekap}
                                                    kosong={
                                                        <Badge tone="warning" icon={ClipboardList}>
                                                            Belum diisi
                                                        </Badge>
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex shrink-0 items-center justify-between gap-3 sm:flex-col sm:items-end">
                                        {item.batas_edit && (
                                            <span className="inline-flex items-center gap-1 text-xs font-medium text-fg-muted">
                                                <Icon icon={Clock} className="size-3.5" />
                                                Terkunci {sisaWaktu(item.batas_edit)} lagi
                                            </span>
                                        )}

                                        <Button size="sm" href={absensi.show.url({ program: item.program, pertemuan: item.id })} iconRight={ArrowRight}>
                                            Isi Absensi
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                {/* Pintasan program */}
                <Card title="Program" padded={false}>
                    <ul className="divide-y divide-line">
                        {data.programs.map((row) => (
                            <li key={row.program}>
                                <Link
                                    href={absensi.kelas.url(row.program)}
                                    className="flex items-center gap-3 px-5 py-4 transition-colors hover:bg-surface-muted sm:px-6"
                                >
                                    <ProgramIcon program={row.program} size="sm" />

                                    <span className="min-w-0 flex-1">
                                        <span className="block font-semibold text-fg">{PROGRAM[row.program].label}</span>
                                        <span className="block text-sm text-fg-muted">{row.jumlah_kelas} kelas aktif</span>
                                    </span>

                                    <Icon icon={ChevronRight} className="size-5 text-fg-subtle" />
                                </Link>
                            </li>
                        ))}
                    </ul>
                </Card>

                {/* Riwayat singkat */}
                {terbaru.length > 0 && (
                    <Card
                        title="Pertemuan terkunci terbaru"
                        description={`Pertemuan dalam ${rentangHari} hari terakhir yang sudah lewat batas edit.`}
                        padded={false}
                        className="lg:col-span-3"
                    >
                        <ul className="divide-y divide-line">
                            {terbaru.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={absensi.show.url({ program: item.program, pertemuan: item.id })}
                                        className="flex flex-col gap-2 px-5 py-3.5 transition-colors hover:bg-surface-muted sm:flex-row sm:items-center sm:gap-4 sm:px-6"
                                    >
                                        <span className="w-36 shrink-0 text-sm font-medium text-fg tabular-nums">{formatTanggal(item.tanggal)}</span>

                                        <span className="flex min-w-0 flex-1 items-center gap-2">
                                            <ProgramBadge program={item.program} />
                                            <span className="truncate text-sm text-fg">{item.kelas}</span>
                                        </span>

                                        <span className="flex flex-wrap items-center gap-1.5">
                                            <RekapBadges rekap={item.rekap} kosong={<Badge tone="neutral">Tidak diisi</Badge>} />
                                            <Icon icon={Lock} className="ml-1 size-4 text-fg-subtle" />
                                            <span className="sr-only">Terkunci</span>
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </Card>
                )}
            </div>
        </>
    );
}
