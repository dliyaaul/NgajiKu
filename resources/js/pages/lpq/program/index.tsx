import { useForm } from '@inertiajs/react';
import { BookMarked, Check, Lock, Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';

import { LpqLayout } from '@/components/lpq/lpq-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { EmptyState } from '@/components/ui/empty-state';
import { Field, fieldAria } from '@/components/ui/field';
import { Icon } from '@/components/ui/icon';
import { Modal } from '@/components/ui/modal';
import lpq from '@/routes/admin/lpq';

interface Program {
    id: string;
    nama: string;
    deskripsi: string | null;
    terkunci: boolean;
    jumlah_santri: number;
    jumlah_pengajar: number;
    jumlah_kelas: number;
    jumlah_lembaga: number;
}

export default function DaftarProgram({ program }: { program: Program[] }) {
    const [form, setForm] = useState<{ open: boolean; data: Program | null }>({ open: false, data: null });

    return (
        <LpqLayout
            title="Program Santri"
            subtitle="Program pembelajaran yang dapat diikuti santri dan diajar oleh pengajar."
            actions={
                <Button icon={Plus} onClick={() => setForm({ open: true, data: null })}>
                    Tambah Program
                </Button>
            }
        >
            {program.length === 0 ? (
                <EmptyState icon={BookMarked} title="Belum ada program" description="Tambahkan program pertama, mis. BBAQ atau Tahfidz." />
            ) : (
                <ul className="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    {program.map((item) => {
                        const statistik = [
                            ['santri', item.jumlah_santri],
                            ['pengajar', item.jumlah_pengajar],
                            ['kelas', item.jumlah_kelas],
                            ['lembaga', item.jumlah_lembaga],
                        ] as const;
                        const dipakai = statistik.some(([, jumlah]) => jumlah > 0);

                        return (
                            <li key={item.id} className="flex flex-col rounded-2xl border border-line bg-surface p-5 shadow-xs">
                                <div className="flex items-start gap-4">
                                    <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-400/10 dark:text-brand-300">
                                        <Icon icon={BookMarked} className="size-5" />
                                    </span>

                                    <div className="min-w-0 flex-1">
                                        <p className="flex flex-wrap items-center gap-2 font-semibold text-fg">
                                            {item.nama}
                                            {item.terkunci && (
                                                <Badge icon={Lock} tone="neutral">
                                                    Program inti
                                                </Badge>
                                            )}
                                        </p>
                                        <p className="mt-1 text-sm text-fg-muted">{item.deskripsi || 'Belum ada deskripsi.'}</p>
                                    </div>

                                    <div className="-mt-1 -mr-2 flex shrink-0">
                                        <Button variant="ghost" size="sm" icon={Pencil} onClick={() => setForm({ open: true, data: item })}>
                                            <span className="sr-only">Edit {item.nama}</span>
                                        </Button>
                                        {!item.terkunci && !dipakai && (
                                            <ConfirmDialog
                                                title={`Hapus program ${item.nama}?`}
                                                url={lpq.program.destroy.url(item.id)}
                                                trigger={(open) => (
                                                    <Button variant="ghost" size="sm" icon={Trash2} onClick={open} className="hover:text-error-700 dark:hover:text-error-300">
                                                        <span className="sr-only">Hapus {item.nama}</span>
                                                    </Button>
                                                )}
                                            >
                                                Program yang belum dipakai akan dihapus permanen.
                                            </ConfirmDialog>
                                        )}
                                    </div>
                                </div>

                                <dl className="mt-5 grid grid-cols-4 gap-2 border-t border-line pt-4 text-center">
                                    {statistik.map(([label, jumlah]) => (
                                        <div key={label}>
                                            <dt className="text-xs text-fg-muted capitalize">{label}</dt>
                                            <dd className="text-lg font-semibold text-fg tabular-nums">{jumlah}</dd>
                                        </div>
                                    ))}
                                </dl>
                            </li>
                        );
                    })}
                </ul>
            )}

            <FormProgram open={form.open} program={form.data} onClose={() => setForm((sebelum) => ({ ...sebelum, open: false }))} />
        </LpqLayout>
    );
}

function FormProgram({ open, program, onClose }: { open: boolean; program: Program | null; onClose: () => void }) {
    const form = useForm({ nama: '', deskripsi: '' });
    const { data, setData, errors } = form;
    const namaHint = program?.terkunci ? 'Nama program inti dipakai fitur absensi sehingga tidak dapat diubah.' : undefined;

    useEffect(() => {
        if (open) {
            form.setData({ nama: program?.nama ?? '', deskripsi: program?.deskripsi ?? '' });
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, program?.id]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const opsi = { preserveScroll: true, onSuccess: () => onClose() };

        if (program) {
            form.put(lpq.program.update.url(program.id), opsi);
        } else {
            form.post(lpq.program.store.url(), opsi);
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title={program ? 'Edit Program' : 'Tambah Program'}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Batal
                    </Button>
                    <Button type="submit" form="form-program" icon={program ? Check : Plus} disabled={form.processing}>
                        {program ? 'Simpan' : 'Tambah'}
                    </Button>
                </>
            }
        >
            <form id="form-program" onSubmit={submit} noValidate className="flex flex-col gap-5">
                <Field label="Nama program" htmlFor="nama" error={errors.nama} hint={namaHint} required>
                    <input
                        id="nama"
                        value={data.nama}
                        maxLength={100}
                        readOnly={program?.terkunci}
                        onChange={(event) => setData('nama', event.target.value)}
                        placeholder="Contoh: Tahsin Dewasa"
                        className="form-control read-only:bg-surface-muted"
                        {...fieldAria('nama', errors.nama, namaHint)}
                    />
                </Field>

                <Field label="Deskripsi" htmlFor="deskripsi" error={errors.deskripsi} optional>
                    <textarea
                        id="deskripsi"
                        rows={3}
                        value={data.deskripsi}
                        maxLength={1000}
                        onChange={(event) => setData('deskripsi', event.target.value)}
                        className="form-control h-auto py-2.5"
                        {...fieldAria('deskripsi', errors.deskripsi)}
                    />
                </Field>
            </form>
        </Modal>
    );
}
