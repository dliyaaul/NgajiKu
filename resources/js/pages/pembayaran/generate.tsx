import { router, useForm, usePage } from '@inertiajs/react';
import { CheckCheck, Eye, Users } from 'lucide-react';
import { type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { MenuPembayaran } from '@/components/pembayaran/menu-pembayaran';
import { Alert } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/empty-state';
import { Field, fieldAria } from '@/components/ui/field';
import { StatCard } from '@/components/ui/stat-card';
import AppLayout from '@/layouts/app-layout';
import { formatPeriode, formatRupiah } from '@/lib/pembayaran';
import admin from '@/routes/admin';
import type { BreadcrumbItem, SharedProps } from '@/types';

interface Values {
    periode_mulai: string;
    periode_selesai: string;
    tanggal_jatuh_tempo: string;
}

interface Pratinjau {
    nama_tarif: string;
    nominal_per_santri: number;
    periode_mulai: string;
    periode_selesai: string;
    total_siap_generate: number;
    total_duplikat: number;
    daftar: Array<{
        santri_program_id: string;
        nama_santri: string;
        nama_program: string | null;
        status: 'duplikat' | 'siap_generate';
    }>;
}

interface Props {
    values: Values;
    pratinjau: Pratinjau | null;
    galat: string | null;
}

export default function GenerateTagihan({ values, pratinjau, galat }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { label: 'Pembayaran', href: admin.pembayaran.index.url() },
        { label: 'Tagihan', href: admin.pembayaran.index.url() },
        { label: 'Generate' },
    ];
    const { errors } = usePage<SharedProps>().props;
    const form = useForm<Values>(values);
    const { data, setData } = form;
    const periodeBerubah =
        pratinjau !== null &&
        (data.periode_mulai !== pratinjau.periode_mulai || data.periode_selesai !== pratinjau.periode_selesai || data.tanggal_jatuh_tempo !== values.tanggal_jatuh_tempo);

    const lihatPratinjau = (event: FormEvent) => {
        event.preventDefault();
        router.get(admin.pembayaran.generate.create.url({ query: data as unknown as Record<string, string> }), {}, { preserveScroll: true });
    };

    const generate = () => {
        form.post(admin.pembayaran.generate.store.url());
    };

    return (
        <AppLayout title="Generate Tagihan" breadcrumbs={breadcrumbs}>
            <PageHeader
                title="Generate Tagihan Reguler"
                subtitle="Membuat tagihan bulanan untuk semua santri layanan Reguler berdasarkan tarif Reguler yang berlaku."
                breadcrumbs={breadcrumbs}
            />

            <MenuPembayaran />

            <form onSubmit={lihatPratinjau} noValidate className="mb-6 max-w-3xl rounded-2xl border border-line bg-surface shadow-xs">
                <div className="grid grid-cols-1 gap-5 p-5 sm:grid-cols-3 sm:p-6">
                    <Field label="Periode mulai" htmlFor="periode_mulai" error={errors.periode_mulai} required>
                        <input
                            type="date"
                            id="periode_mulai"
                            value={data.periode_mulai}
                            onChange={(event) => setData('periode_mulai', event.target.value)}
                            className="form-control"
                            {...fieldAria('periode_mulai', errors.periode_mulai)}
                        />
                    </Field>
                    <Field label="Periode selesai" htmlFor="periode_selesai" error={errors.periode_selesai} required>
                        <input
                            type="date"
                            id="periode_selesai"
                            value={data.periode_selesai}
                            min={data.periode_mulai}
                            onChange={(event) => setData('periode_selesai', event.target.value)}
                            className="form-control"
                            {...fieldAria('periode_selesai', errors.periode_selesai)}
                        />
                    </Field>
                    <Field label="Jatuh tempo" htmlFor="tanggal_jatuh_tempo" error={errors.tanggal_jatuh_tempo} optional>
                        <input
                            type="date"
                            id="tanggal_jatuh_tempo"
                            value={data.tanggal_jatuh_tempo}
                            min={data.periode_mulai}
                            onChange={(event) => setData('tanggal_jatuh_tempo', event.target.value)}
                            className="form-control"
                            {...fieldAria('tanggal_jatuh_tempo', errors.tanggal_jatuh_tempo)}
                        />
                    </Field>
                </div>
                <div className="flex justify-end border-t border-line px-5 py-4 sm:px-6">
                    <Button type="submit" variant={pratinjau ? 'secondary' : 'primary'} icon={Eye}>
                        Lihat Pratinjau
                    </Button>
                </div>
            </form>

            {galat && (
                <Alert tone="danger" title="Tagihan belum dapat dibuat" className="mb-6 max-w-3xl">
                    {galat} Atur tarif di menu <strong>Tarif</strong>.
                </Alert>
            )}

            {pratinjau && (
                <div className="flex flex-col gap-5">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
                        <StatCard label="Akan ditagih" value={pratinjau.total_siap_generate} icon={Users} tone="brand" hint={formatPeriode(pratinjau.periode_mulai, pratinjau.periode_selesai)} />
                        <StatCard label="Sudah punya tagihan" value={pratinjau.total_duplikat} icon={CheckCheck} tone="neutral" hint="Akan dilewati" />
                        <StatCard
                            label="Nominal per santri"
                            value={formatRupiah(pratinjau.nominal_per_santri)}
                            icon={CheckCheck}
                            tone="success"
                            hint={pratinjau.nama_tarif}
                        />
                    </div>

                    <Card
                        title="Daftar santri"
                        description="Santri layanan Reguler beserta status tagihannya pada periode ini."
                        padded={false}
                        actions={
                            <Button icon={CheckCheck} onClick={generate} disabled={form.processing || periodeBerubah || pratinjau.total_siap_generate === 0}>
                                Buat {pratinjau.total_siap_generate} Tagihan
                            </Button>
                        }
                    >
                        {periodeBerubah && (
                            <div className="px-5 pt-4 sm:px-6">
                                <Alert tone="warning">Periode diubah. Tekan Lihat Pratinjau lagi sebelum membuat tagihan.</Alert>
                            </div>
                        )}

                        {pratinjau.daftar.length === 0 ? (
                            <EmptyState icon={Users} title="Tidak ada santri Reguler" className="m-5 border-0" />
                        ) : (
                            <ul className="divide-y divide-line">
                                {pratinjau.daftar.map((baris) => (
                                    <li key={baris.santri_program_id} className="flex items-center justify-between gap-3 px-5 py-3 sm:px-6">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium text-fg">{baris.nama_santri}</p>
                                            <p className="text-sm text-fg-muted">{baris.nama_program ?? '-'}</p>
                                        </div>
                                        {baris.status === 'siap_generate' ? (
                                            <Badge tone="brand">Akan ditagih</Badge>
                                        ) : (
                                            <Badge tone="neutral">Sudah ada</Badge>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </div>
            )}
        </AppLayout>
    );
}
