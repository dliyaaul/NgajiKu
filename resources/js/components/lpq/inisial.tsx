import { cn, inisial } from '@/lib/utils';

/** Foto atau inisial nama dalam lingkaran (dekoratif; nama tetap ditulis di sebelahnya). */
export function Inisial({ nama, foto, className }: { nama: string; foto?: string | null; className?: string }) {
    if (foto) {
        return <img src={foto} alt="" className={cn('size-10 shrink-0 rounded-full object-cover', className)} />;
    }

    return (
        <span
            aria-hidden="true"
            className={cn(
                'grid size-10 shrink-0 place-items-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600 dark:bg-brand-400/10 dark:text-brand-300',
                className,
            )}
        >
            {inisial(nama) || '?'}
        </span>
    );
}
