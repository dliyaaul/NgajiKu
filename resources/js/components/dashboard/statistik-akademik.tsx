
import { BookOpen, GraduationCap, Layers3, School, Users } from 'lucide-react';

import { StatCard } from '@/components/ui/stat-card';

interface Props {
    totalSantri: number;
    totalPendaftaran: number;
    totalPengajar: number;
    totalKelasAktif: number;
    totalProgram: number;
}

export function StatistikAkademik({
    totalSantri,
    totalPendaftaran,
    totalPengajar,
    totalKelasAktif,
    totalProgram,
}: Props) {
    return (
        <section className="mb-8">
            <div className="mb-4">
                <h2 className="text-lg font-bold text-fg">Statistik Akademik</h2>
                <p className="mt-1 text-sm text-fg-muted">
                    Gambaran umum santri, pengajar, dan kegiatan pembelajaran.
                </p>
            </div>

            <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-5">
                <StatCard
                    label="Total Santri"
                    value={totalSantri}
                    icon={GraduationCap}
                    tone="brand"
                    hint="Seluruh akun santri"
                />
                <StatCard
                    label="Pendaftaran"
                    value={totalPendaftaran}
                    icon={BookOpen}
                    tone="info"
                    hint="Total program santri"
                />
                <StatCard
                    label="Pengajar"
                    value={totalPengajar}
                    icon={Users}
                    tone="success"
                    hint="Akun pengajar"
                />
                <StatCard
                    label="Kelas Aktif"
                    value={totalKelasAktif}
                    icon={School}
                    tone="warning"
                    hint="Kelas pembelajaran aktif"
                />
                <StatCard
                    label="Program"
                    value={totalProgram}
                    icon={Layers3}
                    tone="neutral"
                    hint="Program tersedia"
                />
            </div>
        </section>
    );
}
