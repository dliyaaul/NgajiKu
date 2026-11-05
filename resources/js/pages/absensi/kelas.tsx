import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ChevronRight, User, Users } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { ProgramIcon } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';
import { PROGRAM } from '@/lib/program';
import absensi from '@/routes/absensi';
import type { BreadcrumbItem, ProgramKey } from '@/types';

interface Props {
    program: ProgramKey;
    kelasList: Array<{ id: string; nama_kelas: string; pengajar: string | null }>;
}

export default function AbsensiKelas({ program, kelasList }: Props) {
    const { auth } = usePage().props;
    const label = PROGRAM[program].label;
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Absensi', href: absensi.index.url() }, { label }];

    return (
        <AppLayout title={`Absensi ${label}`} breadcrumbs={breadcrumbs}>
            <PageHeader title={`Absensi ${label}`} subtitle="Pilih kelas pengajaran." breadcrumbs={breadcrumbs} />

            {kelasList.length === 0 ? (
                <EmptyState
                    icon={Users}
                    title="Belum ada kelas pengajaran"
                    description={
                        auth?.role === 'pengajar'
                            ? `Anda belum menjadi pengajar pada kelas aktif program ${label}.`
                            : `Belum ada kelas aktif pada program ${label}.`
                    }
                >
                    <Button variant="secondary" href={absensi.index.url()} icon={ArrowLeft}>
                        Pilih program lain
                    </Button>
                </EmptyState>
            ) : (
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3">
                    {kelasList.map((kelas) => (
                        <Link
                            key={kelas.id}
                            href={absensi.pertemuan.index.url({ program, kelas: kelas.id })}
                            className="group flex items-center gap-4 rounded-2xl border border-line bg-surface p-5 shadow-xs transition-[border-color,box-shadow] duration-150 hover:border-brand-500/40 hover:shadow-md"
                        >
                            <ProgramIcon program={program} />

                            <div className="min-w-0 flex-1">
                                <p className="truncate font-semibold text-fg">{kelas.nama_kelas}</p>
                                <p className="mt-0.5 flex items-center gap-1.5 truncate text-sm text-fg-muted">
                                    <Icon icon={User} className="size-4" />
                                    {kelas.pengajar ?? 'Pengajar belum ditentukan'}
                                </p>
                            </div>

                            <Icon icon={ChevronRight} className="size-5 text-fg-subtle transition-transform duration-150 group-hover:translate-x-0.5" />
                        </Link>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
