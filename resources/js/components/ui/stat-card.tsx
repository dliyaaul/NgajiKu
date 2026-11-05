import type { LucideIcon } from 'lucide-react';

import { Icon } from '@/components/ui/icon';
import { cn } from '@/lib/utils';

const ICON_TONE = {
    brand: 'bg-brand-50 text-brand-600 dark:bg-brand-400/10 dark:text-brand-300',
    success: 'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300',
    warning: 'bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-300',
    danger: 'bg-error-50 text-error-700 dark:bg-error-400/10 dark:text-error-300',
    info: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-400/10 dark:text-indigo-300',
    neutral: 'bg-surface-muted text-fg-muted',
};

export type StatTone = keyof typeof ICON_TONE;

/**
 * Kartu angka ringkas (KPI). Tone mewarnai ikon saja; angka tetap warna teks utama.
 */
export function StatCard({
    label,
    value,
    icon,
    tone = 'brand',
    hint,
}: {
    label: string;
    value: string | number;
    icon: LucideIcon;
    tone?: StatTone;
    hint?: string;
}) {
    return (
        <div className="rounded-2xl border border-line bg-surface p-5 shadow-xs">
            <div className="flex items-start justify-between gap-3">
                <p className="text-sm font-medium text-fg-muted">{label}</p>
                <span className={cn('grid size-9 shrink-0 place-items-center rounded-xl', ICON_TONE[tone])}>
                    <Icon icon={icon} className="size-[1.125rem]" />
                </span>
            </div>

            <p className="mt-2 text-3xl font-bold tracking-tight text-fg tabular-nums">{value}</p>

            {hint && <p className="mt-1 text-xs text-fg-muted">{hint}</p>}
        </div>
    );
}
