import { Link } from '@inertiajs/react';
import { ArrowUpRight, BookMarked, Building2, CircleAlert, Layers, Repeat2, type LucideIcon } from 'lucide-react';

import { LpqLayout } from '@/components/lpq/lpq-layout';
import { Icon } from '@/components/ui/icon';
import { PERAN, type Peran } from '@/lib/lpq';
import lpq from '@/routes/admin/lpq';

interface Props {
    jumlah: Record<Peran | 'pengajar_pengganti' | 'jenis_kelas' | 'kelas_aktif' | 'program' | 'lembaga', number>;
    perhatian: {
        santri_tanpa_program: number;
        pendaftaran_tanpa_kelas: number;
        kelas_tanpa_pengajar: number;
    };
}

interface Kartu {
    label: string;
    deskripsi: string;
    href: string;
    icon: LucideIcon;
    angka: number;
    satuan: string;
}

export default function ManajemenLpq({ jumlah, perhatian }: Props) {
    const pengguna: Kartu[] = [
        ...(['santri', 'pengajar', 'staff', 'admin'] as Peran[]).map((peran) => ({
            label: PERAN[peran].jamak,
            deskripsi: PERAN[peran].deskripsi,
            href: lpq.pengguna.index.url(peran),
            icon: PERAN[peran].icon,
            angka: jumlah[peran],
            satuan: 'akun',
        })),
        {
            label: 'Pengajar Pengganti',
            deskripsi: 'Pengajar dari luar untuk menggantikan sementara.',
            href: lpq.pengajarPengganti.index.url(),
            icon: Repeat2,
            angka: jumlah.pengajar_pengganti,
            satuan: 'aktif',
        },
    ];

    const akademik: Kartu[] = [
        {
            label: 'Jenis Kelas',
            deskripsi: `${jumlah.jenis_kelas} jenis layanan beserta kelas-kelasnya.`,
            href: lpq.jenisKelas.index.url(),
            icon: Layers,
            angka: jumlah.kelas_aktif,
            satuan: 'kelas aktif',
        },
        {
            label: 'Program Santri',
            deskripsi: 'Program pembelajaran seperti BBAQ, Tahfidz, dan Tarjamah.',
            href: lpq.program.index.url(),
            icon: BookMarked,
            angka: jumlah.program,
            satuan: 'program',
        },
        {
            label: 'Lembaga',
            deskripsi: 'Lembaga mitra beserta kelas dan santrinya.',
            href: lpq.lembaga.index.url(),
            icon: Building2,
            angka: jumlah.lembaga,
            satuan: 'lembaga',
        },
    ];

    const catatan = [
        perhatian.santri_tanpa_program > 0 && {
            teks: `${perhatian.santri_tanpa_program} santri belum terdaftar di program mana pun.`,
            href: lpq.pengguna.index.url('santri'),
        },
        perhatian.pendaftaran_tanpa_kelas > 0 && {
            teks: `${perhatian.pendaftaran_tanpa_kelas} pendaftaran program belum ditempatkan di kelas.`,
            href: lpq.pengguna.index.url('santri'),
        },
        perhatian.kelas_tanpa_pengajar > 0 && {
            teks: `${perhatian.kelas_tanpa_pengajar} kelas aktif belum memiliki pengajar.`,
            href: lpq.jenisKelas.index.url(),
        },
    ].filter((item): item is { teks: string; href: string } => Boolean(item));

    return (
        <LpqLayout title="Manajemen LPQ" subtitle="Kelola data pengguna, kelas, program, dan lembaga dalam satu tempat." breadcrumbs={[]}>
            {catatan.length > 0 && (
                <section aria-labelledby="perhatian" className="mb-8 rounded-2xl border border-warning-200 bg-warning-50/60 p-4 sm:p-5 dark:border-warning-400/20 dark:bg-warning-400/5">
                    <h2 id="perhatian" className="flex items-center gap-2 text-sm font-semibold text-warning-800 dark:text-warning-200">
                        <Icon icon={CircleAlert} className="size-4" />
                        Perlu dilengkapi
                    </h2>
                    <ul className="mt-2 flex flex-col gap-1.5 text-sm">
                        {catatan.map((item) => (
                            <li key={item.teks}>
                                <Link href={item.href} className="text-warning-800 underline-offset-4 hover:underline dark:text-warning-200">
                                    {item.teks}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <Bagian judul="Pengguna" kartu={pengguna} />
            <Bagian judul="Akademik" kartu={akademik} />
        </LpqLayout>
    );
}

function Bagian({ judul, kartu }: { judul: string; kartu: Kartu[] }) {
    return (
        <section aria-labelledby={`bagian-${judul}`} className="mb-8 last:mb-0">
            <h2 id={`bagian-${judul}`} className="mb-3 text-[0.6875rem] font-semibold tracking-[0.12em] text-fg-subtle uppercase">
                {judul}
            </h2>

            <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {kartu.map((item) => (
                    <li key={item.href}>
                        <Link
                            href={item.href}
                            className="group flex h-full flex-col rounded-2xl border border-line bg-surface p-5 shadow-xs transition-colors hover:border-brand-300 dark:hover:border-brand-400/40"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <span className="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-400/10 dark:text-brand-300">
                                    <Icon icon={item.icon} className="size-5" />
                                </span>
                                <Icon icon={ArrowUpRight} className="size-4 text-fg-subtle transition-colors group-hover:text-brand-600 dark:group-hover:text-brand-300" />
                            </div>

                            <p className="mt-4 font-semibold text-fg">{item.label}</p>
                            <p className="mt-1 flex-1 text-sm text-fg-muted">{item.deskripsi}</p>

                            <p className="mt-4 text-sm text-fg-muted">
                                <span className="text-2xl font-bold tracking-tight text-fg tabular-nums">{item.angka}</span> {item.satuan}
                            </p>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}
