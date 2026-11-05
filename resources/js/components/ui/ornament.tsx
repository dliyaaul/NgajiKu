import { useId } from 'react';

import { cn } from '@/lib/utils';

/**
 * Ornamen geometris Islami (dekoratif). Warna mengikuti `currentColor`.
 * pattern   — pola bintang delapan yang berulang (beri ukuran lewat class)
 * medallion — medali bintang bertingkat
 * divider   — garis pembatas dengan belah ketupat
 */
export function Ornament({ type = 'pattern', className }: { type?: 'pattern' | 'medallion' | 'divider'; className?: string }) {
    const patternId = `ornamen-${useId().replace(/:/g, '')}`;

    if (type === 'pattern') {
        return (
            <svg className={cn('pointer-events-none', className)} aria-hidden="true" focusable="false">
                <defs>
                    <pattern id={patternId} width="80" height="80" patternUnits="userSpaceOnUse">
                        <g fill="none" stroke="currentColor" strokeWidth="0.8">
                            <path d="M23 23H57V57H23Z M40 16L64 40L40 64L16 40Z" />
                            <circle cx="40" cy="40" r="7" />
                            <path d="M40 0V16 M40 64V80 M0 40H16 M64 40H80" />
                            <path d="M-10 0L0 -10L10 0L0 10Z M70 0L80 -10L90 0L80 10Z M-10 80L0 70L10 80L0 90Z M70 80L80 70L90 80L80 90Z" />
                        </g>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill={`url(#${patternId})`} />
            </svg>
        );
    }

    if (type === 'medallion') {
        return <Medallion className={className} strokeWidth={0.5} />;
    }

    return (
        <div className={cn('flex items-center gap-1.5', className)} aria-hidden="true">
            <span className="h-px w-8 bg-linear-to-r from-transparent to-current opacity-70" />
            <span className="size-1 rotate-45 bg-current" />
            <span className="size-2 rotate-45 border border-current" />
            <span className="size-1 rotate-45 bg-current" />
            <span className="h-px w-8 bg-linear-to-l from-transparent to-current opacity-70" />
        </div>
    );
}

export function Medallion({ className, strokeWidth = 0.5 }: { className?: string; strokeWidth?: number }) {
    return (
        <svg
            viewBox="-100 -100 200 200"
            className={cn('pointer-events-none', className)}
            fill="none"
            stroke="currentColor"
            strokeWidth={strokeWidth}
            aria-hidden="true"
            focusable="false"
        >
            <circle r="98" />
            <circle r="94" strokeDasharray="1 2" />
            {[0, 45].map((angle) => (
                <rect key={`a${angle}`} x="-66" y="-66" width="132" height="132" transform={`rotate(${angle})`} />
            ))}
            {[22.5, 67.5].map((angle) => (
                <rect key={`b${angle}`} x="-50" y="-50" width="100" height="100" transform={`rotate(${angle})`} />
            ))}
            <circle r="50" />
            {Array.from({ length: 8 }, (_, step) => (
                <line key={`l${step}`} y1="-50" y2="-94" transform={`rotate(${22.5 + step * 45})`} />
            ))}
            {[0, 45].map((angle) => (
                <rect key={`c${angle}`} x="-33" y="-33" width="66" height="66" transform={`rotate(${angle})`} />
            ))}
            <circle r="20" />
            <circle r="6" />
        </svg>
    );
}
