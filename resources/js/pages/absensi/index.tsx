import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { ProgramIcon } from '@/components/ui/badge';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';
import { PROGRAM } from '@/lib/program';
import absensi from '@/routes/absensi';
import type { BreadcrumbItem, ProgramKey } from '@/types';

export default function AbsensiIndex({ programs }: { programs: ProgramKey[] }) {
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Absensi' }];

    return (
        <AppLayout title="Absensi" breadcrumbs={breadcrumbs}>
            <PageHeader title="Absensi" subtitle="Pilih program untuk mengelola pertemuan dan absensi santri." breadcrumbs={breadcrumbs} />

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                {programs.map((program) => (
                    <Link
                        key={program}
                        href={absensi.kelas.url(program)}
                        className="group flex flex-col gap-5 rounded-2xl border border-line bg-surface p-6 shadow-xs transition-[border-color,box-shadow] duration-150 hover:border-brand-500/40 hover:shadow-md"
                    >
                        <ProgramIcon program={program} size="lg" />

                        <div>
                            <h2 className="text-lg font-semibold text-fg">{PROGRAM[program].label}</h2>
                            <p className="mt-1 text-sm text-fg-muted">{PROGRAM[program].description}</p>
                        </div>

                        <span className="mt-auto inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 dark:text-brand-300">
                            {PROGRAM[program].hasHafalan ? 'Absensi & input hafalan' : 'Kelola absensi'}
                            <Icon icon={ArrowRight} className="size-4 transition-transform duration-150 group-hover:translate-x-0.5" />
                        </span>
                    </Link>
                ))}
            </div>
        </AppLayout>
    );
}
