import { Link } from '@inertiajs/react';
import { ArrowRight, BookMarked, Building2, CalendarDays, ChevronRight, History, Mail, MapPin, Pencil, Phone, Plus, School, Trash2, UserRound } from 'lucide-react';
import { useState } from 'react';

import { FormPendaftaran, type OpsiPendaftaran, type Pendaftaran } from '@/components/lpq/form-pendaftaran';
import { Inisial } from '@/components/lpq/inisial';
import { LpqLayout } from '@/components/lpq/lpq-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { EmptyState } from '@/components/ui/empty-state';
import { Icon } from '@/components/ui/icon';
import { JENIS, PERAN, type Jenis, type Peran } from '@/lib/lpq';
import { cn, formatTanggal } from '@/lib/utils';
import lpq from '@/routes/admin/lpq';
import type { BreadcrumbItem } from '@/types';

interface Profil {
    id: string;
    username: string;
    email: string | null;
    no_hp: string | null;
    alamat: string | null;
    angkatan: string | null;
    avatar: string | null;
    created_at: string | null;
}

interface KelasDiajar {
    id: string;
    nama_kelas: string;
    aktif: boolean;
    program: string | null;
    jenis_kelas: string | null;
    kelas_type_id: string;
    lembaga: string | null;
    jumlah_santri: number;
}

interface Props {
    peran: Peran;
    pengguna: Profil;
    // Santri
    pendaftaran?: Pendaftaran[];
    opsi?: OpsiPendaftaran;
    // Pengajar
    program?: string[];
    kelas?: KelasDiajar[];
}

export default function DetailPengguna({ peran, pengguna, pendaftaran, opsi, program, kelas }: Props) {
    const meta = PERAN[peran];
    const breadcrumbs: BreadcrumbItem[] = [{ label: meta.jamak, href: lpq.pengguna.index.url(peran) }, { label: pengguna.username }];

    return (
        <LpqLayout
            title={pengguna.username}
            subtitle={`Detail ${meta.label.toLowerCase()}`}
            breadcrumbs={breadcrumbs}
            actions={
                <>
                    <Button variant="secondary" icon={Pencil} href={lpq.pengguna.edit.url([peran, pengguna.id])}>
                        Edit
                    </Button>
                    <ConfirmDialog
                        title={`Hapus akun ${pengguna.username}?`}
                        url={lpq.pengguna.destroy.url([peran, pengguna.id])}
                        trigger={(open) => (
                            <Button variant="danger-outline" icon={Trash2} onClick={open}>
                                Hapus
                            </Button>
                        )}
                    >
                        Akun yang sudah memiliki absensi, tagihan, atau kelas tidak dapat dihapus agar riwayatnya tetap utuh.
                    </ConfirmDialog>
                </>
            }
        >
            <div className="grid grid-cols-1 gap-6 *:min-w-0 xl:grid-cols-[20rem_minmax(0,1fr)]">
                <KartuProfil peran={peran} pengguna={pengguna} />

                <div className="flex flex-col gap-6">
                    {peran === 'santri' && pendaftaran && opsi && <ProgramSantri santriId={pengguna.id} pendaftaran={pendaftaran} opsi={opsi} />}
                    {peran === 'pengajar' && <MengajarPengajar program={program ?? []} kelas={kelas ?? []} />}
                    {(peran === 'admin' || peran === 'staff') && (
                        <Card title="Hak akses">
                            <p className="text-sm text-fg-muted">{meta.deskripsi}</p>
                        </Card>
                    )}
                </div>
            </div>
        </LpqLayout>
    );
}

function KartuProfil({ peran, pengguna }: { peran: Peran; pengguna: Profil }) {
    const baris: Array<{ icon: typeof Mail; label: string; nilai: string | null }> = [
        { icon: Mail, label: 'Email', nilai: pengguna.email },
        { icon: Phone, label: 'Nomor HP', nilai: pengguna.no_hp },
        { icon: MapPin, label: 'Alamat', nilai: pengguna.alamat },
        ...(peran === 'santri' ? [{ icon: School, label: 'Angkatan', nilai: pengguna.angkatan }] : []),
        { icon: CalendarDays, label: 'Terdaftar', nilai: pengguna.created_at ? formatTanggal(pengguna.created_at, 'ringkas') : null },
    ];

    return (
        <section aria-label="Profil" className="self-start rounded-2xl border border-line bg-surface p-5 shadow-xs sm:p-6">
            <div className="flex items-center gap-4">
                <Inisial nama={pengguna.username} foto={pengguna.avatar} className="size-14 text-lg" />
                <div className="min-w-0">
                    <p className="truncate font-semibold text-fg">{pengguna.username}</p>
                    <Badge tone="brand" icon={PERAN[peran].icon} className="mt-1">
                        {PERAN[peran].label}
                    </Badge>
                </div>
            </div>

            <dl className="mt-6 flex flex-col gap-4 border-t border-line pt-5">
                {baris.map((item) => (
                    <div key={item.label} className="flex gap-3">
                        <Icon icon={item.icon} className="mt-0.5 size-4 text-fg-subtle" />
                        <div className="min-w-0">
                            <dt className="text-xs text-fg-muted">{item.label}</dt>
                            <dd className={cn('text-sm wrap-break-word', item.nilai ? 'text-fg' : 'text-fg-subtle')}>{item.nilai || 'Belum diisi'}</dd>
                        </div>
                    </div>
                ))}
            </dl>
        </section>
    );
}

// ---------------------------------------------------------------- Santri

function ProgramSantri({ santriId, pendaftaran, opsi }: { santriId: string; pendaftaran: Pendaftaran[]; opsi: OpsiPendaftaran }) {
    const [form, setForm] = useState<{ open: boolean; data: Pendaftaran | null }>({ open: false, data: null });

    const programTerpakai = pendaftaran.map((item) => item.program_id);

    return (
        <Card
            title="Program & Kelas"
            description="Program yang diikuti santri beserta penempatan kelasnya."
            padded={false}
            actions={
                <Button size="sm" icon={Plus} onClick={() => setForm({ open: true, data: null })} disabled={programTerpakai.length >= opsi.program.length}>
                    Daftarkan Program
                </Button>
            }
        >
            {pendaftaran.length === 0 ? (
                <EmptyState icon={BookMarked} title="Belum mengikuti program" description="Daftarkan santri ke program agar dapat diabsen dan ditagih." className="m-5 sm:m-6" />
            ) : (
                <ul className="divide-y divide-line">
                    {pendaftaran.map((item) => (
                        <li key={item.id} className="p-5 sm:p-6">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-semibold text-fg">{item.program}</p>
                                        {item.jenis_kelas && <Badge tone={item.jenis ? JENIS[item.jenis as Jenis].tone : 'neutral'}>{item.jenis_kelas}</Badge>}
                                        {item.default_pbm && <Badge>{item.default_pbm}</Badge>}
                                    </div>
                                </div>

                                <div className="flex gap-1">
                                    <Button variant="ghost" size="sm" icon={Pencil} onClick={() => setForm({ open: true, data: item })}>
                                        Ubah
                                        <span className="sr-only"> pendaftaran {item.program}</span>
                                    </Button>
                                    {!item.terkunci && (
                                        <ConfirmDialog
                                            title={`Keluarkan dari ${item.program}?`}
                                            url={lpq.santriProgram.destroy.url(item.id)}
                                            confirmLabel="Ya, keluarkan"
                                            trigger={(open) => (
                                                <Button variant="ghost" size="sm" icon={Trash2} onClick={open} className="hover:text-error-700 dark:hover:text-error-300">
                                                    <span className="sr-only">Keluarkan dari {item.program}</span>
                                                </Button>
                                            )}
                                        >
                                            Pendaftaran beserta riwayat kelasnya akan dihapus.
                                        </ConfirmDialog>
                                    )}
                                </div>
                            </div>

                            <dl className="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                                <Info label="Kelas" nilai={item.kelas} kosong="Belum ditempatkan" peringatan />
                                <Info label="Pengajar" nilai={item.pengajar} kosong="Mengikuti kelas" />
                                {item.lembaga && <Info label="Lembaga" nilai={item.lembaga} />}
                            </dl>

                            {item.riwayat.length > 0 && (
                                <details className="group mt-4 rounded-xl bg-surface-muted/60 px-4 py-3">
                                    <summary className="flex cursor-pointer list-none items-center gap-2 text-sm font-medium text-fg-muted hover:text-fg">
                                        <Icon icon={History} className="size-4" />
                                        Riwayat kelas ({item.riwayat.length})
                                        <Icon icon={ChevronRight} className="ml-auto size-4 transition-transform group-open:rotate-90" />
                                    </summary>
                                    <ol className="mt-3 flex flex-col gap-3 border-l border-line pl-4">
                                        {item.riwayat.map((riwayat) => (
                                            <li key={riwayat.id} className="text-sm">
                                                <p className="flex flex-wrap items-center gap-1.5 text-fg">
                                                    {riwayat.dari ? (
                                                        <>
                                                            {riwayat.dari}
                                                            <Icon icon={ArrowRight} className="size-3.5 text-fg-subtle" />
                                                            {riwayat.ke ?? 'Tanpa kelas'}
                                                        </>
                                                    ) : (
                                                        <>Ditempatkan di {riwayat.ke ?? '-'}</>
                                                    )}
                                                </p>
                                                <p className="text-xs text-fg-muted">
                                                    {formatTanggal(riwayat.berlaku_mulai, 'ringkas')}
                                                    {riwayat.oleh && ` · oleh ${riwayat.oleh}`}
                                                    {riwayat.alasan && ` · ${riwayat.alasan}`}
                                                </p>
                                            </li>
                                        ))}
                                    </ol>
                                </details>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <FormPendaftaran
                open={form.open}
                onClose={() => setForm((sebelum) => ({ ...sebelum, open: false }))}
                santriId={santriId}
                pendaftaran={form.data}
                programTerpakai={programTerpakai}
                opsi={opsi}
            />
        </Card>
    );
}

function Info({ label, nilai, kosong = '-', peringatan = false }: { label: string; nilai: string | null; kosong?: string; peringatan?: boolean }) {
    return (
        <div className="min-w-0">
            <dt className="text-xs text-fg-muted">{label}</dt>
            <dd className={cn('truncate font-medium', nilai ? 'text-fg' : peringatan ? 'text-warning-700 dark:text-warning-300' : 'text-fg-subtle')}>{nilai ?? kosong}</dd>
        </div>
    );
}

// ---------------------------------------------------------------- Pengajar

function MengajarPengajar({ program, kelas }: { program: string[]; kelas: KelasDiajar[] }) {
    return (
        <>
            <Card title="Program yang diajar">
                {program.length === 0 ? (
                    <p className="text-sm text-fg-muted">Belum ditetapkan. Atur lewat tombol Edit.</p>
                ) : (
                    <div className="flex flex-wrap gap-2">
                        {program.map((nama) => (
                            <Badge key={nama} tone="brand" icon={BookMarked}>
                                {nama}
                            </Badge>
                        ))}
                    </div>
                )}
            </Card>

            <Card title="Kelas yang dipegang" description="Atur pengajar kelas dari menu Jenis Kelas." padded={false}>
                {kelas.length === 0 ? (
                    <EmptyState icon={School} title="Belum memegang kelas" className="m-5 sm:m-6" />
                ) : (
                    <ul className="divide-y divide-line">
                        {kelas.map((item) => (
                            <li key={item.id}>
                                <Link href={lpq.kelas.index.url(item.kelas_type_id)} className="flex items-center gap-4 px-5 py-4 hover:bg-surface-muted/50 sm:px-6">
                                    <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-surface-muted text-fg-muted">
                                        <Icon icon={item.lembaga ? Building2 : School} className="size-5" />
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="flex flex-wrap items-center gap-2">
                                            <span className="font-medium text-fg">{item.nama_kelas}</span>
                                            {!item.aktif && <Badge>Nonaktif</Badge>}
                                        </span>
                                        <span className="block truncate text-sm text-fg-muted">{[item.program, item.jenis_kelas, item.lembaga].filter(Boolean).join(' · ')}</span>
                                    </span>
                                    <span className="flex items-center gap-1 text-sm text-fg-muted tabular-nums">
                                        <Icon icon={UserRound} className="size-4" />
                                        {item.jumlah_santri}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </>
    );
}
