import { router, useForm } from '@inertiajs/react';
import { CheckCheck, Check, CircleAlert, Clock, Info, Lock, Plus, RotateCw, Search, Users } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type FormEvent, type ReactNode } from 'react';

import { PageHeader } from '@/components/page-header';
import { Alert } from '@/components/ui/alert';
import { Badge, StatusBadge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import { Icon } from '@/components/ui/icon';
import { SelectMenu, type SelectOption } from '@/components/ui/select-menu';
import AppLayout from '@/layouts/app-layout';
import { BELUM_ABSEN, PROGRAM, STATUSES, TONE_DOT, statusTone } from '@/lib/program';
import { cn, formatTanggal, formatWaktu, inisial } from '@/lib/utils';
import absensi from '@/routes/absensi';
import type { BreadcrumbItem, KelasRingkas, ProgramKey, StatusKehadiran } from '@/types';

interface Santri {
    id: string;
    nama: string;
    email: string | null;
    tersimpan: boolean;
    status: string;
    jenis_tahfidz: string | null;
    surat: string | null;
    ayat_mulai: number | null;
    ayat_selesai: number | null;
    keterangan: string | null;
}

interface Props {
    program: ProgramKey;
    kelas: KelasRingkas;
    pertemuan: {
        id: string;
        tanggal: string;
        metode: string | null;
        materi: string | null;
        pengajar: string | null;
        pengganti: string | null;
    };
    santriList: Santri[];
    ringkasanTersimpan: Record<StatusKehadiran, number>;
    editable: boolean;
    batasEdit: string | null;
    jenisTahfidz: string[];
    daftarSurat: Array<{ nomor: number; nama: string; jumlah_ayat: number }>;
}

/** Isian satu santri di form (angka ayat disimpan sebagai teks input). */
interface Baris {
    santri_program_id: string;
    status: string;
    jenis_tahfidz: string;
    surat: string;
    ayat_mulai: string;
    ayat_selesai: string;
    keterangan: string;
}

const OPSI_STATUS: SelectOption[] = [
    ...STATUSES.map((status) => ({ value: status, label: status, tone: statusTone(status) })),
    { value: BELUM_ABSEN, label: 'Belum absen', tone: 'neutral' },
];

export default function AbsensiShow(props: Props) {
    const { program, kelas, pertemuan, santriList, ringkasanTersimpan, editable, batasEdit } = props;
    const meta = PROGRAM[program];
    const tanggal = formatTanggal(pertemuan.tanggal, 'panjang');

    const breadcrumbs: BreadcrumbItem[] = [
        { label: 'Absensi', href: absensi.index.url() },
        { label: meta.label, href: absensi.kelas.url(program) },
        { label: kelas.nama_kelas, href: absensi.pertemuan.index.url({ program, kelas: kelas.id }) },
        { label: tanggal },
    ];

    return (
        <AppLayout title={`Absensi ${meta.label}`} breadcrumbs={breadcrumbs}>
            <PageHeader title={`Absensi ${meta.label}`} subtitle={`${kelas.nama_kelas} · ${tanggal}`} breadcrumbs={breadcrumbs} />

            {/* Informasi pertemuan */}
            <dl className="mb-6 grid grid-cols-2 gap-x-4 gap-y-5 rounded-2xl border border-line bg-surface p-5 text-sm shadow-xs *:min-w-0 sm:p-6 md:grid-cols-4">
                <div>
                    <dt className="text-fg-muted">Pengajar</dt>
                    <dd className="mt-1 font-semibold text-fg">{pertemuan.pengajar ?? '-'}</dd>
                    {pertemuan.pengganti && <dd className="text-xs text-fg-muted">Pengganti: {pertemuan.pengganti}</dd>}
                </div>
                <div>
                    <dt className="text-fg-muted">Metode</dt>
                    <dd className="mt-1 font-semibold text-fg">{pertemuan.metode ?? '-'}</dd>
                </div>
                <div>
                    <dt className="text-fg-muted">Kehadiran tersimpan</dt>
                    <dd className="mt-1.5 flex flex-wrap gap-1">
                        {STATUSES.map((status) => (
                            <StatusBadge key={status} status={status} count={ringkasanTersimpan[status]} />
                        ))}
                    </dd>
                </div>
                <div>
                    <dt className="text-fg-muted">Batas edit</dt>
                    <dd className="mt-1.5">
                        {editable && batasEdit ? (
                            <Badge tone="success" icon={Clock}>
                                {formatWaktu(batasEdit)}
                            </Badge>
                        ) : (
                            <Badge tone="neutral" icon={Lock}>
                                Terkunci
                            </Badge>
                        )}
                    </dd>
                </div>
                {pertemuan.materi && (
                    <div className="col-span-2 border-t border-line pt-4 md:col-span-4">
                        <dt className="text-fg-muted">Materi</dt>
                        <dd className="mt-1 whitespace-pre-line text-fg">{pertemuan.materi}</dd>
                    </div>
                )}
            </dl>

            {!editable && (
                <Alert tone="info" className="mb-5" title="Absensi terkunci">
                    Pertemuan ini sudah lewat 24 jam sejak dibuat, sehingga absensinya hanya dapat dilihat.
                </Alert>
            )}

            {santriList.length === 0 ? (
                <EmptyState icon={Users} title="Belum ada santri pada kelas ini" description="Daftarkan santri ke kelas pengajaran terlebih dahulu." />
            ) : (
                <FormAbsensi {...props} />
            )}
        </AppLayout>
    );
}

function FormAbsensi({ program, kelas, pertemuan, santriList, editable, jenisTahfidz, daftarSurat }: Props) {
    const hasHafalan = PROGRAM[program].hasHafalan;
    const adaBelumTersimpan = editable && santriList.some((santri) => !santri.tersimpan);

    const form = useForm<{ absensi: Baris[] }>({
        absensi: santriList.map((santri) => ({
            santri_program_id: santri.id,
            status: santri.status,
            jenis_tahfidz: santri.jenis_tahfidz ?? '',
            surat: santri.surat ?? '',
            ayat_mulai: santri.ayat_mulai?.toString() ?? '',
            ayat_selesai: santri.ayat_selesai?.toString() ?? '',
            keterangan: santri.keterangan ?? '',
        })),
    });

    const rows = form.data.absensi;
    const errors = form.errors as Record<string, string | undefined>;
    const jumlahError = Object.keys(errors).length;

    // Santri yang belum diabsen sudah otomatis "Hadir", jadi form dianggap berubah sejak awal.
    const dirty = editable && (form.isDirty || adaBelumTersimpan);

    const [cari, setCari] = useState('');
    const cocok = (nama: string) => !cari.trim() || nama.toLowerCase().includes(cari.trim().toLowerCase());
    const adaYangCocok = santriList.some((santri) => cocok(santri.nama));

    const opsiSurat = useMemo<SelectOption[]>(
        () => daftarSurat.map((surat) => ({ value: surat.nama, label: `${surat.nomor}. ${surat.nama}`, meta: `${surat.jumlah_ayat} ayat` })),
        [daftarSurat],
    );
    const jumlahAyat = useMemo(() => Object.fromEntries(daftarSurat.map((surat) => [surat.nama, surat.jumlah_ayat])), [daftarSurat]);

    const ubahBaris = (index: number, perubahan: Partial<Baris>) => {
        form.setData('absensi', rows.map((baris, i) => (i === index ? { ...baris, ...perubahan } : baris)));
    };

    const tandaiSemuaHadir = () => {
        form.setData('absensi', rows.map((baris) => ({ ...baris, status: 'Hadir' })));
    };

    const hitung = (status: string) => rows.filter((baris) => baris.status === status).length;

    // Peringatkan bila meninggalkan halaman sebelum menyimpan.
    useEffect(() => {
        if (!dirty) {
            return;
        }

        const onBeforeUnload = (event: BeforeUnloadEvent) => {
            event.preventDefault();
        };

        const hapusListenerInertia = router.on('before', (event) => {
            const visit = event.detail.visit;

            if (visit.method === 'get' && !window.confirm('Perubahan absensi belum disimpan. Tinggalkan halaman ini?')) {
                event.preventDefault();
            }
        });

        window.addEventListener('beforeunload', onBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', onBeforeUnload);
            hapusListenerInertia();
        };
    }, [dirty]);

    const errorAlert = useRef<HTMLDivElement>(null);
    useEffect(() => {
        if (jumlahError > 0) {
            errorAlert.current?.focus();
        }
    }, [jumlahError, form.errors]);

    const fokusErrorPertama = () => {
        const field = document.querySelector<HTMLElement>('#form-absensi [aria-invalid="true"]');

        if (!field) {
            return;
        }

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        field.scrollIntoView({ block: 'center', behavior: reduceMotion ? 'auto' : 'smooth' });
        field.focus({ preventScroll: true });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        // Setoran hafalan hanya dikirim untuk santri yang hadir.
        form.transform((data) => ({
            absensi: data.absensi.map((baris) => {
                if (!hasHafalan) {
                    return { santri_program_id: baris.santri_program_id, status: baris.status };
                }

                const hadir = baris.status === 'Hadir';
                const angka = (nilai: string) => (hadir && nilai !== '' ? Number(nilai) : null);

                return {
                    santri_program_id: baris.santri_program_id,
                    status: baris.status,
                    jenis_tahfidz: hadir ? baris.jenis_tahfidz || null : null,
                    surat: hadir ? baris.surat || null : null,
                    ayat_mulai: angka(baris.ayat_mulai),
                    ayat_selesai: angka(baris.ayat_selesai),
                    keterangan: baris.keterangan || null,
                };
            }),
        }));

        form.put(absensi.update.url({ program, pertemuan: pertemuan.id }), { preserveScroll: true });
    };

    return (
        <form id="form-absensi" onSubmit={submit} noValidate>
            {jumlahError > 0 ? (
                <Alert ref={errorAlert} tone="danger" title="Absensi belum tersimpan" className="mb-5">
                    <p>Ada {jumlahError} isian yang perlu diperbaiki.</p>
                    <button type="button" onClick={fokusErrorPertama} className="mt-1 font-semibold underline underline-offset-2 hover:no-underline">
                        Tuju isian pertama yang salah
                    </button>
                </Alert>
            ) : (
                adaBelumTersimpan && (
                    <Alert tone="info" className="mb-5">
                        Santri yang belum diabsen otomatis ditandai <strong>Hadir</strong>. Ubah bila perlu, lalu tekan <strong>Simpan Absensi</strong>.
                    </Alert>
                )
            )}

            {/* Pencarian & aksi massal */}
            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="relative sm:w-80">
                    <Icon icon={Search} className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-fg-subtle" />
                    <input
                        type="search"
                        value={cari}
                        onChange={(event) => setCari(event.target.value)}
                        placeholder="Cari nama santri…"
                        aria-label="Cari nama santri"
                        className="form-control pl-10"
                    />
                </div>

                {editable && (
                    <Button variant="success-outline" icon={CheckCheck} onClick={tandaiSemuaHadir}>
                        Tandai semua hadir
                    </Button>
                )}
            </div>

            <fieldset disabled={!editable} className="flex flex-col gap-3">
                <legend className="sr-only">Kehadiran santri</legend>

                {santriList.map((santri, index) =>
                    cocok(santri.nama) ? (
                        <BarisSantri
                            key={santri.id}
                            index={index}
                            santri={santri}
                            baris={rows[index]}
                            onChange={(perubahan) => ubahBaris(index, perubahan)}
                            errors={errors}
                            editable={editable}
                            hasHafalan={hasHafalan}
                            jenisTahfidz={jenisTahfidz}
                            opsiSurat={opsiSurat}
                            jumlahAyat={jumlahAyat}
                        />
                    ) : null,
                )}
            </fieldset>

            {!adaYangCocok && (
                <p className="rounded-2xl border border-dashed border-line px-6 py-8 text-center text-sm text-fg-muted">
                    Tidak ada santri yang cocok dengan pencarian.
                </p>
            )}

            {/* Bar simpan: melayang di bawah, di atas navigasi ponsel */}
            {editable && (
                <div className="sticky bottom-(--bottom-nav-space) z-10 mt-6 rounded-2xl border border-line bg-surface/95 px-4 py-3 shadow-lg shadow-gray-950/10 backdrop-blur-xl sm:px-5 lg:bottom-4 dark:shadow-black/40">
                    <div className="flex items-center justify-between gap-3">
                        <div className="min-w-0" aria-live="polite">
                            <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-semibold text-fg tabular-nums">
                                <span>{santriList.length} santri</span>
                                <span className="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs font-medium text-fg-muted">
                                    {STATUSES.map((status) => (
                                        <span key={status} className="inline-flex items-center gap-1">
                                            <span className={cn('size-2 rounded-full', TONE_DOT[statusTone(status)])} aria-hidden="true" />
                                            {status}
                                            <span className="font-semibold text-fg">{hitung(status)}</span>
                                        </span>
                                    ))}
                                </span>
                            </p>
                            {dirty && (
                                <p className="mt-0.5 flex items-center gap-1 text-xs font-medium text-warning-700 dark:text-warning-300">
                                    <Icon icon={CircleAlert} className="size-3.5" />
                                    Belum disimpan
                                </p>
                            )}
                        </div>

                        <div className="flex shrink-0 items-center gap-2">
                            <Button variant="ghost" className="max-sm:hidden" href={absensi.pertemuan.index.url({ program, kelas: kelas.id })}>
                                Kembali
                            </Button>
                            <Button type="submit" icon={Check} disabled={form.processing} aria-busy={form.processing}>
                                {form.processing ? 'Menyimpan…' : 'Simpan Absensi'}
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </form>
    );
}

interface BarisSantriProps {
    index: number;
    santri: Santri;
    baris: Baris;
    onChange: (perubahan: Partial<Baris>) => void;
    errors: Record<string, string | undefined>;
    editable: boolean;
    hasHafalan: boolean;
    jenisTahfidz: string[];
    opsiSurat: SelectOption[];
    jumlahAyat: Record<string, number>;
}

const INPUT_AYAT =
    'form-control text-center tabular-nums [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';

/** Satu baris santri pada form isi absensi. */
function BarisSantri({ index: i, santri, baris, onChange, errors, editable, hasHafalan, jenisTahfidz, opsiSurat, jumlahAyat }: BarisSantriProps) {
    const hadir = baris.status === 'Hadir';
    const maksAyat = baris.surat ? jumlahAyat[baris.surat] : undefined;
    const error = (field: keyof Baris) => errors[`absensi.${i}.${field}`];

    const errorAttrs = (field: keyof Baris, id: string) =>
        error(field) ? { 'aria-invalid': true as const, 'aria-describedby': `${id}-error` } : {};

    return (
        <div className="rounded-2xl border border-line bg-surface shadow-xs">
            {/* Identitas & status */}
            <div className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:p-5">
                <div className="flex min-w-0 items-center gap-3.5">
                    <span
                        className="relative grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-sm font-bold text-brand-700 ring-1 ring-brand-100 dark:bg-brand-400/10 dark:text-brand-300 dark:ring-brand-400/20"
                        aria-hidden="true"
                    >
                        {inisial(santri.nama) || '?'}
                        <span className="absolute -right-1 -bottom-1 grid h-5 min-w-5 place-items-center rounded-full bg-surface px-1 text-[0.625rem] font-semibold text-fg-muted ring-1 ring-line tabular-nums">
                            {i + 1}
                        </span>
                    </span>

                    <div className="min-w-0">
                        <div className="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                            <p className="truncate font-semibold text-fg">{santri.nama}</p>
                            {!santri.tersimpan && editable && <Badge tone="warning">Belum disimpan</Badge>}
                        </div>
                        {santri.email && <p className="truncate text-xs text-fg-muted">{santri.email}</p>}
                    </div>
                </div>

                <div className="w-full shrink-0 sm:w-52">
                    <span id={`status-${i}-label`} className="sr-only">
                        Status kehadiran {santri.nama}
                    </span>
                    <SelectMenu
                        variant="status"
                        id={`status-${i}`}
                        value={baris.status}
                        onChange={(status) => onChange({ status })}
                        options={OPSI_STATUS}
                        disabled={!editable}
                        labelledBy={`status-${i}-label`}
                        invalid={Boolean(error('status'))}
                        describedBy={error('status') ? `status-${i}-error` : undefined}
                    />
                    {error('status') && <PesanError id={`status-${i}-error`}>{error('status')}</PesanError>}
                </div>
            </div>

            {hasHafalan && (
                <div className="border-t border-line px-4 py-4 sm:px-5 sm:py-5">
                    {/* Setoran hafalan: hanya aktif bila hadir */}
                    {hadir ? (
                        <div className="mb-4 grid gap-4 *:min-w-0 md:grid-cols-12">
                            <fieldset className="md:col-span-3">
                                <legend className="mb-1.5 text-xs font-semibold text-fg-muted">Jenis setoran</legend>

                                <div className="grid grid-cols-2 gap-1 rounded-xl bg-surface-muted p-1 ring-1 ring-line ring-inset">
                                    {jenisTahfidz.map((jenis) => (
                                        <label
                                            key={jenis}
                                            className="flex h-9 cursor-pointer items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-fg-muted transition-colors duration-150 hover:text-fg has-checked:bg-surface has-checked:font-semibold has-checked:text-brand-700 has-checked:shadow-sm has-checked:ring-1 has-checked:ring-line has-disabled:cursor-not-allowed has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500 dark:has-checked:bg-white/10 dark:has-checked:text-brand-300"
                                        >
                                            <input
                                                type="radio"
                                                className="sr-only"
                                                name={`jenis-tahfidz-${i}`}
                                                value={jenis}
                                                checked={baris.jenis_tahfidz === jenis}
                                                onChange={() => onChange({ jenis_tahfidz: jenis })}
                                            />
                                            <Icon icon={jenis === 'Ziyadah' ? Plus : RotateCw} className="size-4" />
                                            {jenis}
                                        </label>
                                    ))}
                                </div>
                            </fieldset>

                            <div className="md:col-span-5">
                                <span id={`surat-${i}-label`} className="mb-1.5 block text-xs font-semibold text-fg-muted">
                                    Surat
                                </span>
                                <SelectMenu
                                    searchable
                                    id={`surat-${i}`}
                                    value={baris.surat}
                                    onChange={(surat) => onChange({ surat })}
                                    options={opsiSurat}
                                    disabled={!editable}
                                    labelledBy={`surat-${i}-label`}
                                    placeholder="Pilih surat"
                                    searchPlaceholder="Cari nama atau nomor surat…"
                                    emptyText="Surat tidak ditemukan."
                                    invalid={Boolean(error('surat'))}
                                    describedBy={error('surat') ? `surat-${i}-error` : undefined}
                                />
                                {error('surat') && <PesanError id={`surat-${i}-error`}>{error('surat')}</PesanError>}
                            </div>

                            <div className="md:col-span-4">
                                <span id={`ayat-${i}-label`} className="mb-1.5 block text-xs font-semibold text-fg-muted">
                                    Ayat
                                </span>

                                <div className="flex items-center gap-2" role="group" aria-labelledby={`ayat-${i}-label`}>
                                    <input
                                        type="number"
                                        inputMode="numeric"
                                        id={`ayat-mulai-${i}`}
                                        value={baris.ayat_mulai}
                                        onChange={(event) => onChange({ ayat_mulai: event.target.value })}
                                        min={1}
                                        max={maksAyat}
                                        placeholder="Dari"
                                        aria-label="Ayat mulai"
                                        className={INPUT_AYAT}
                                        {...errorAttrs('ayat_mulai', `ayat-mulai-${i}`)}
                                    />
                                    <span className="text-fg-subtle" aria-hidden="true">
                                        –
                                    </span>
                                    <input
                                        type="number"
                                        inputMode="numeric"
                                        id={`ayat-selesai-${i}`}
                                        value={baris.ayat_selesai}
                                        onChange={(event) => onChange({ ayat_selesai: event.target.value })}
                                        min={baris.ayat_mulai || 1}
                                        max={maksAyat}
                                        placeholder="Sampai"
                                        aria-label="Ayat selesai"
                                        className={INPUT_AYAT}
                                        {...errorAttrs('ayat_selesai', `ayat-selesai-${i}`)}
                                    />
                                </div>

                                <div className="mt-1.5 flex min-h-5 items-center justify-between gap-2 text-xs">
                                    <span className="text-fg-muted tabular-nums">
                                        {baris.surat ? `Maks. ${maksAyat ?? '-'} ayat` : 'Pilih surat terlebih dahulu'}
                                    </span>
                                    {editable && baris.surat && maksAyat && (
                                        <button
                                            type="button"
                                            onClick={() => onChange({ ayat_mulai: '1', ayat_selesai: String(maksAyat) })}
                                            className="rounded font-semibold text-brand-600 hover:underline dark:text-brand-300"
                                        >
                                            Satu surat penuh
                                        </button>
                                    )}
                                </div>

                                {error('ayat_mulai') && <PesanError id={`ayat-mulai-${i}-error`}>{error('ayat_mulai')}</PesanError>}
                                {error('ayat_selesai') && <PesanError id={`ayat-selesai-${i}-error`}>{error('ayat_selesai')}</PesanError>}
                            </div>
                        </div>
                    ) : (
                        <p className="mb-3 flex items-center gap-1.5 text-xs text-fg-muted">
                            <Icon icon={Info} className="size-3.5" />
                            Input setoran hafalan tersedia bila santri hadir.
                        </p>
                    )}

                    <div>
                        <label htmlFor={`keterangan-${i}`} className="mb-1.5 block text-xs font-semibold text-fg-muted">
                            Keterangan <span className="font-normal text-fg-subtle">(opsional)</span>
                        </label>
                        <input
                            type="text"
                            id={`keterangan-${i}`}
                            maxLength={500}
                            value={baris.keterangan}
                            onChange={(event) => onChange({ keterangan: event.target.value })}
                            placeholder="Catatan setoran, kelancaran, tajwid, dll."
                            className="form-control"
                            {...errorAttrs('keterangan', `keterangan-${i}`)}
                        />
                        {error('keterangan') && <PesanError id={`keterangan-${i}-error`}>{error('keterangan')}</PesanError>}
                    </div>
                </div>
            )}
        </div>
    );
}

function PesanError({ id, children }: { id: string; children: ReactNode }) {
    return (
        <p id={id} className="mt-1.5 text-xs font-medium text-error-700 dark:text-error-300">
            {children}
        </p>
    );
}
