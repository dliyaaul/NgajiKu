import { Link } from '@inertiajs/react';
import { ChevronRight, Pencil, Plus, Trash2, UserRoundSearch } from 'lucide-react';

import { Inisial } from '@/components/lpq/inisial';
import { LpqLayout } from '@/components/lpq/lpq-layout';
import { Toolbar } from '@/components/lpq/toolbar';
import { Pager } from '@/components/pembayaran/pager';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { EmptyState } from '@/components/ui/empty-state';
import { Icon } from '@/components/ui/icon';
import { PERAN, type Peran } from '@/lib/lpq';
import lpq from '@/routes/admin/lpq';
import type { Opsi, Pagination } from '@/types';

interface Baris {
    id: string;
    username: string;
    email: string | null;
    no_hp: string | null;
    avatar: string | null;
    angkatan: string | null;
    program: string[];
    jumlah_kelas: number | null;
    created_at: string | null;
    saya: boolean;
}

interface Props {
    peran: Peran;
    pengguna: { data: Baris[]; pagination: Pagination };
    filter: { cari: string; program: string };
    opsi: { program: Opsi[] };
}

export default function DaftarPengguna({ peran, pengguna, filter, opsi }: Props) {
    const meta = PERAN[peran];
    const punyaDetail = peran === 'santri' || peran === 'pengajar';
    const punyaProgram = opsi.program.length > 0;
    const tautan = (id: string) => (punyaDetail ? lpq.pengguna.show.url([peran, id]) : lpq.pengguna.edit.url([peran, id]));
    const adaFilter = Boolean(filter.cari || filter.program);

    return (
        <LpqLayout
            title={`Data ${meta.jamak}`}
            subtitle={meta.deskripsi}
            actions={
                <Button icon={Plus} href={lpq.pengguna.create.url(peran)}>
                    Tambah {meta.label}
                </Button>
            }
        >
            <Toolbar
                url={(query) => lpq.pengguna.index.url(peran, { query })}
                filter={filter}
                placeholder="Cari nama, email, atau nomor HP…"
                selects={
                    punyaProgram
                        ? [{ name: 'program', label: 'Filter program', semua: 'Semua program', options: opsi.program.map((item) => ({ value: item.id, label: item.nama })) }]
                        : []
                }
            />

            {pengguna.data.length === 0 ? (
                <EmptyState
                    icon={UserRoundSearch}
                    title={adaFilter ? 'Tidak ada yang cocok' : `Belum ada data ${meta.label.toLowerCase()}`}
                    description={adaFilter ? 'Coba kata kunci atau filter lain.' : `Tambahkan akun ${meta.label.toLowerCase()} pertama.`}
                />
            ) : (
                <>
                    <p className="mb-3 text-sm text-fg-muted tabular-nums">
                        {pengguna.pagination.total} {meta.label.toLowerCase()}
                    </p>

                    <div className="overflow-hidden rounded-2xl border border-line bg-surface shadow-xs">
                        {/* Tabel (tablet & desktop) */}
                        <table className="hidden w-full text-left text-sm md:table">
                            <thead className="border-b border-line bg-surface-muted/60 text-xs font-semibold tracking-wide text-fg-muted uppercase">
                                <tr>
                                    <th scope="col" className="px-5 py-3">
                                        Nama
                                    </th>
                                    <th scope="col" className="px-5 py-3">
                                        No. HP
                                    </th>
                                    {punyaProgram && (
                                        <th scope="col" className="px-5 py-3">
                                            Program
                                        </th>
                                    )}
                                    {peran === 'pengajar' && (
                                        <th scope="col" className="px-5 py-3 text-right">
                                            Kelas aktif
                                        </th>
                                    )}
                                    {peran === 'santri' && (
                                        <th scope="col" className="px-5 py-3">
                                            Angkatan
                                        </th>
                                    )}
                                    <th scope="col" className="px-5 py-3 text-right">
                                        <span className="sr-only">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {pengguna.data.map((item) => (
                                    <tr key={item.id} className="transition-colors hover:bg-surface-muted/50">
                                        <td className="px-5 py-3.5">
                                            <Link href={tautan(item.id)} className="flex min-w-0 items-center gap-3">
                                                <Inisial nama={item.username} foto={item.avatar} className="size-9" />
                                                <span className="min-w-0">
                                                    <span className="flex items-center gap-2 font-medium text-fg">
                                                        <span className="truncate">{item.username}</span>
                                                        {item.saya && <Badge tone="brand">Anda</Badge>}
                                                    </span>
                                                    <span className="block truncate text-xs text-fg-muted">{item.email ?? '-'}</span>
                                                </span>
                                            </Link>
                                        </td>
                                        <td className="px-5 py-3.5 text-fg-muted tabular-nums">{item.no_hp ?? '-'}</td>
                                        {punyaProgram && (
                                            <td className="px-5 py-3.5">
                                                <DaftarProgram program={item.program} />
                                            </td>
                                        )}
                                        {peran === 'pengajar' && <td className="px-5 py-3.5 text-right text-fg tabular-nums">{item.jumlah_kelas ?? 0}</td>}
                                        {peran === 'santri' && <td className="px-5 py-3.5 text-fg-muted">{item.angkatan ?? '-'}</td>}
                                        <td className="px-5 py-3.5">
                                            <Aksi peran={peran} item={item} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Daftar (ponsel) */}
                        <ul className="divide-y divide-line md:hidden">
                            {pengguna.data.map((item) => (
                                <li key={item.id}>
                                    <Link href={tautan(item.id)} className="flex items-center gap-3 px-4 py-3.5">
                                        <Inisial nama={item.username} foto={item.avatar} />
                                        <span className="min-w-0 flex-1">
                                            <span className="flex items-center gap-2 font-medium text-fg">
                                                <span className="truncate">{item.username}</span>
                                                {item.saya && <Badge tone="brand">Anda</Badge>}
                                            </span>
                                            <span className="block truncate text-xs text-fg-muted">{item.email ?? item.no_hp ?? '-'}</span>
                                            {item.program.length > 0 && <span className="mt-1 block truncate text-xs text-fg-subtle">{item.program.join(' · ')}</span>}
                                        </span>
                                        <Icon icon={ChevronRight} className="size-4 text-fg-subtle" />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <Pager pagination={pengguna.pagination} />
                </>
            )}
        </LpqLayout>
    );
}

function DaftarProgram({ program }: { program: string[] }) {
    if (program.length === 0) {
        return <span className="text-fg-subtle">Belum ada</span>;
    }

    return (
        <span className="flex flex-wrap gap-1">
            {program.map((nama) => (
                <Badge key={nama}>{nama}</Badge>
            ))}
        </span>
    );
}

function Aksi({ peran, item }: { peran: Peran; item: Baris }) {
    return (
        <div className="flex justify-end gap-1">
            <Button variant="ghost" size="sm" icon={Pencil} href={lpq.pengguna.edit.url([peran, item.id])}>
                <span className="sr-only lg:not-sr-only">Edit</span>
                <span className="sr-only"> {item.username}</span>
            </Button>
            {!item.saya && (
                <ConfirmDialog
                    title={`Hapus akun ${item.username}?`}
                    url={lpq.pengguna.destroy.url([peran, item.id])}
                    trigger={(open) => (
                        <Button variant="ghost" size="sm" icon={Trash2} onClick={open} className="hover:text-error-700 dark:hover:text-error-300">
                            <span className="sr-only">Hapus {item.username}</span>
                        </Button>
                    )}
                >
                    Akun tidak dapat dipulihkan. Akun yang sudah memiliki absensi, tagihan, atau kelas akan ditolak agar riwayatnya tetap utuh.
                </ConfirmDialog>
            )}
        </div>
    );
}
