import { CircleAlert, CircleCheck, Info, TriangleAlert, X } from 'lucide-react';
import { forwardRef, useState, type ReactNode } from 'react';

import { Icon } from '@/components/ui/icon';
import { cn } from '@/lib/utils';

const CONFIG = {
    info: {
        icon: Info,
        classes: 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:border-indigo-400/20 dark:bg-indigo-400/10 dark:text-indigo-200',
        iconClass: 'text-indigo-600 dark:text-indigo-300',
    },
    success: {
        icon: CircleCheck,
        classes: 'border-success-200 bg-success-50 text-success-800 dark:border-success-400/20 dark:bg-success-400/10 dark:text-success-200',
        iconClass: 'text-success-600 dark:text-success-300',
    },
    warning: {
        icon: TriangleAlert,
        classes: 'border-warning-200 bg-warning-50 text-warning-800 dark:border-warning-400/20 dark:bg-warning-400/10 dark:text-warning-200',
        iconClass: 'text-warning-600 dark:text-warning-300',
    },
    danger: {
        icon: CircleAlert,
        classes: 'border-error-200 bg-error-50 text-error-800 dark:border-error-400/20 dark:bg-error-400/10 dark:text-error-200',
        iconClass: 'text-error-600 dark:text-error-300',
    },
};

interface AlertProps {
    tone?: keyof typeof CONFIG;
    title?: string;
    dismissible?: boolean;
    className?: string;
    children: ReactNode;
}

/**
 * Pesan sebaris. Peran ARIA otomatis: danger/warning => alert, lainnya => status.
 */
export const Alert = forwardRef<HTMLDivElement, AlertProps>(function Alert(
    { tone = 'info', title, dismissible = false, className, children },
    ref,
) {
    const [open, setOpen] = useState(true);
    const config = CONFIG[tone];

    if (!open) {
        return null;
    }

    return (
        <div
            ref={ref}
            tabIndex={-1}
            role={tone === 'danger' || tone === 'warning' ? 'alert' : 'status'}
            className={cn('flex items-start gap-3 rounded-xl border px-4 py-3 text-sm', config.classes, className)}
        >
            <Icon icon={config.icon} className={cn('mt-px size-5', config.iconClass)} />

            <div className="min-w-0 flex-1">
                {title && <p className="font-semibold">{title}</p>}
                <div className={cn(title && 'mt-0.5')}>{children}</div>
            </div>

            {dismissible && (
                <button
                    type="button"
                    onClick={() => setOpen(false)}
                    className="-m-1.5 grid size-8 shrink-0 place-items-center rounded-lg opacity-70 hover:opacity-100"
                    aria-label="Tutup pesan"
                >
                    <Icon icon={X} className="size-4" />
                </button>
            )}
        </div>
    );
});
