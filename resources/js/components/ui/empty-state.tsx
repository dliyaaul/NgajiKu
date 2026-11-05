import { Inbox, type LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { Icon } from '@/components/ui/icon';
import { cn } from '@/lib/utils';

/**
 * Keadaan kosong/galat yang ramah: ikon, judul, penjelasan, dan aksi pemulihan.
 */
export function EmptyState({
    icon = Inbox,
    title,
    description,
    tone = 'neutral',
    headingLevel: Heading = 'h2',
    className,
    children,
}: {
    icon?: LucideIcon;
    title: string;
    description?: string;
    tone?: 'neutral' | 'danger';
    headingLevel?: 'h1' | 'h2';
    className?: string;
    children?: ReactNode;
}) {
    return (
        <div className={cn('flex flex-col items-center rounded-2xl border border-dashed border-line bg-surface px-6 py-12 text-center', className)}>
            <div
                className={cn(
                    'mb-4 grid size-12 place-items-center rounded-2xl',
                    tone === 'neutral' && 'bg-surface-muted text-fg-muted',
                    tone === 'danger' && 'bg-error-50 text-error-600 dark:bg-error-400/10 dark:text-error-300',
                )}
            >
                <Icon icon={icon} className="size-6" />
            </div>

            <Heading className="text-base font-semibold text-fg">{title}</Heading>

            {description && <p className="mt-1 max-w-md text-sm text-fg-muted">{description}</p>}

            {children && <div className="mt-6 flex flex-wrap justify-center gap-3">{children}</div>}
        </div>
    );
}
