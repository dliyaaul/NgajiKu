import type { LucideIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

/**
 * Ikon garis (Lucide, stroke 1.75). Bersifat dekoratif; beri label teks pada elemen induknya.
 */
export function Icon({ icon: Glyph, className }: { icon: LucideIcon; className?: string }) {
    return <Glyph strokeWidth={1.75} aria-hidden="true" focusable="false" className={cn('shrink-0', className ?? 'size-5')} />;
}
