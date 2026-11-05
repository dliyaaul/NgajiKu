import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';

import { Icon } from '@/components/ui/icon';
import { cn } from '@/lib/utils';

export type ButtonVariant =
    | 'primary'
    | 'secondary'
    | 'ghost'
    | 'danger'
    | 'danger-outline'
    | 'success-outline'
    | 'inverse'
    | 'inverse-ghost';

const SIZE = {
    sm: 'h-10 gap-1.5 px-3.5 text-sm sm:h-9',
    md: 'h-11 gap-2 px-4 text-sm sm:h-10',
    lg: 'h-12 gap-2 px-5 text-base',
};

const VARIANT: Record<ButtonVariant, string> = {
    primary: 'bg-brand-500 text-white shadow-xs hover:bg-brand-600 active:bg-brand-700',
    secondary: 'border border-line bg-surface text-fg shadow-xs hover:bg-surface-muted active:bg-surface-muted',
    ghost: 'text-fg-muted hover:bg-surface-muted hover:text-fg',
    danger: 'bg-error-600 text-white shadow-xs hover:bg-error-700',
    'danger-outline':
        'border border-error-200 bg-surface text-error-700 hover:bg-error-50 dark:border-error-400/30 dark:text-error-300 dark:hover:bg-error-400/10',
    'success-outline':
        'border border-success-200 bg-surface text-success-700 hover:bg-success-50 dark:border-success-400/30 dark:text-success-300 dark:hover:bg-success-400/10',
    // Untuk latar emerald gelap (banner, panel brand).
    inverse: 'bg-white text-brand-700 shadow-sm hover:bg-brand-50',
    'inverse-ghost': 'bg-white/10 text-white ring-1 ring-white/25 ring-inset hover:bg-white/15',
};

interface ButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children'> {
    variant?: ButtonVariant;
    size?: keyof typeof SIZE;
    /** Bila diisi, tombol menjadi tautan Inertia. */
    href?: string;
    icon?: LucideIcon;
    iconRight?: LucideIcon;
    children?: ReactNode;
}

/**
 * Tombol aplikasi. Tinggi >= 40px; md/sm sedikit lebih tinggi di layar sentuh.
 */
export function Button({
    variant = 'primary',
    size = 'md',
    href,
    icon,
    iconRight,
    className,
    type = 'button',
    children,
    ...props
}: ButtonProps) {
    const classes = cn(
        'inline-flex shrink-0 select-none items-center justify-center rounded-xl font-semibold whitespace-nowrap transition-colors duration-150 disabled:pointer-events-none disabled:opacity-50',
        SIZE[size],
        VARIANT[variant],
        className,
    );
    const iconClass = size === 'lg' ? 'size-5' : 'size-4';

    const content = (
        <>
            {icon && <Icon icon={icon} className={iconClass} />}
            {children}
            {iconRight && <Icon icon={iconRight} className={iconClass} />}
        </>
    );

    if (href) {
        return (
            <Link href={href} className={classes}>
                {content}
            </Link>
        );
    }

    return (
        <button type={type} className={classes} {...props}>
            {content}
        </button>
    );
}
