import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { Icon } from '@/components/ui/icon';
import { PROGRAM, statusTone, type Tone } from '@/lib/program';
import { cn } from '@/lib/utils';
import type { ProgramKey } from '@/types';

const TONE: Record<Tone, string> = {
    neutral: 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10',
    brand: 'bg-brand-50 text-brand-600 ring-brand-200 dark:bg-brand-400/10 dark:text-brand-300 dark:ring-brand-400/20',
    success: 'bg-success-50 text-success-700 ring-success-200 dark:bg-success-400/10 dark:text-success-300 dark:ring-success-400/20',
    warning: 'bg-warning-50 text-warning-700 ring-warning-200 dark:bg-warning-400/10 dark:text-warning-300 dark:ring-warning-400/20',
    danger: 'bg-error-50 text-error-700 ring-error-200 dark:bg-error-400/10 dark:text-error-300 dark:ring-error-400/20',
    info: 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-400/10 dark:text-indigo-300 dark:ring-indigo-400/20',
    sky: 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/20',
    amber: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20',
    violet: 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-400/10 dark:text-violet-300 dark:ring-violet-400/20',
};

/**
 * Label status/kategori ringkas. Warna selalu didampingi teks (tidak bergantung warna saja).
 */
export function Badge({
    tone = 'neutral',
    icon,
    className,
    children,
}: {
    tone?: Tone;
    icon?: LucideIcon;
    className?: string;
    children: ReactNode;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset',
                TONE[tone],
                className,
            )}
        >
            {icon && <Icon icon={icon} className="size-3.5" />}
            {children}
        </span>
    );
}

/** Status kehadiran (Hadir/Izin/Sakit/Alpha), opsional dengan jumlah. */
export function StatusBadge({ status, count }: { status: string; count?: number }) {
    return (
        <Badge tone={statusTone(status)}>
            {status}
            {count !== undefined && ` ${count}`}
        </Badge>
    );
}

/** Label program dengan warna khas masing-masing. */
export function ProgramBadge({ program }: { program: ProgramKey }) {
    return <Badge tone={PROGRAM[program].tone}>{PROGRAM[program].label}</Badge>;
}

const PROGRAM_ICON_TONE = {
    sky: 'bg-sky-50 text-sky-700 dark:bg-sky-400/10 dark:text-sky-300',
    amber: 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300',
    violet: 'bg-violet-50 text-violet-700 dark:bg-violet-400/10 dark:text-violet-300',
};

const PROGRAM_ICON_SIZE = {
    sm: ['size-9 rounded-xl', 'size-[1.125rem]'],
    md: ['size-11 rounded-xl', 'size-5'],
    lg: ['size-12 rounded-2xl', 'size-6'],
};

/** Ikon program dalam kotak berwarna khas program. */
export function ProgramIcon({ program, size = 'md' }: { program: ProgramKey; size?: keyof typeof PROGRAM_ICON_SIZE }) {
    const meta = PROGRAM[program];
    const [boxClass, iconClass] = PROGRAM_ICON_SIZE[size];

    return (
        <span className={cn('grid shrink-0 place-items-center', boxClass, PROGRAM_ICON_TONE[meta.tone])}>
            <Icon icon={meta.icon} className={iconClass} />
        </span>
    );
}
