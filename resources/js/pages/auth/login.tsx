import { Head, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, Check, CircleAlert, CircleCheck, Eye, EyeOff, LoaderCircle, Lock, Mail } from 'lucide-react';
import { useState, type CSSProperties, type FormEvent } from 'react';

import { Icon } from '@/components/ui/icon';
import { Medallion, Ornament } from '@/components/ui/ornament';
import { cn } from '@/lib/utils';
import { process as prosesLogin } from '@/routes/login';

const FITUR = [
    'Pantau perkembangan hafalan dan bacaan santri',
    'Jadwal dan presensi yang terorganisir',
    'Akses sesuai peran masing-masing pengguna',
];

/** Jeda animasi bertahap (dibaca keyframe lewat var(--delay)). */
const jeda = (ms: number) => ({ '--delay': `${ms}ms` }) as CSSProperties;

const INPUT =
    'h-12 w-full rounded-xl border border-slate-200 bg-slate-50/60 pl-12 text-base text-slate-900 placeholder:text-slate-400 outline-none transition duration-200 hover:border-slate-300 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/15 aria-invalid:border-red-300 aria-invalid:focus:ring-red-500/15 sm:text-sm';

export default function Login() {
    const { flash } = usePage().props;
    const [showPassword, setShowPassword] = useState(false);
    const [percobaanGagal, setPercobaanGagal] = useState(0);

    const form = useForm({ email: '', password: '' });
    const pesanError = form.errors.email ?? form.errors.password;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post(prosesLogin.url(), {
            onError: () => {
                form.reset('password');
                setPercobaanGagal((jumlah) => jumlah + 1);
            },
        });
    };

    const tahun = new Date().getFullYear();

    return (
        <>
            <Head title="Login - NgajiKu" />

            <div className="flex min-h-dvh bg-slate-50 text-slate-800">
                {/* Panel brand (desktop) */}
                <aside className="relative hidden w-1/2 overflow-hidden bg-linear-to-br from-emerald-700 via-emerald-800 to-teal-950 lg:flex lg:flex-col lg:justify-between lg:p-14 xl:p-20">
                    {/* Animasi selalu di wrapper agar keyframe opacity tidak menimpa opacity ornamen */}
                    <div className="pointer-events-none absolute inset-0 motion-safe:animate-fade-in" aria-hidden="true">
                        <Ornament
                            type="pattern"
                            className="h-full w-full text-white opacity-[0.09] mask-[radial-gradient(ellipse_at_top_right,black_15%,transparent_75%)]"
                        />
                    </div>

                    {/* Medali ornamen emas di pojok kanan bawah */}
                    <div className="pointer-events-none absolute -right-48 -bottom-48 size-144 text-amber-200 motion-safe:animate-fade-in" style={jeda(200)}>
                        <Medallion className="size-full opacity-[0.22]" strokeWidth={0.35} />
                    </div>

                    {/* Bingkai garis ganda tipis */}
                    <div className="pointer-events-none absolute inset-5 rounded-4xl border border-amber-200/20" aria-hidden="true" />
                    <div className="pointer-events-none absolute inset-7 rounded-[1.6rem] border border-white/5" aria-hidden="true" />

                    {/* Cahaya lembut yang bergerak sangat pelan */}
                    <div
                        className="pointer-events-none absolute -top-32 -right-32 size-96 rounded-full bg-emerald-400/30 blur-3xl motion-safe:animate-drift"
                        aria-hidden="true"
                    />
                    <div
                        className="pointer-events-none absolute -bottom-40 -left-24 size-112 rounded-full bg-teal-300/20 blur-3xl motion-safe:animate-drift motion-safe:[animation-direction:alternate-reverse]"
                        aria-hidden="true"
                    />

                    <div className="relative flex items-center gap-3 motion-safe:animate-fade-up">
                        <div className="flex size-12 items-center justify-center rounded-2xl bg-white/95 p-2 shadow-lg shadow-emerald-950/20">
                            <img src="/assets/images/logo/Ngajiku_Logo.png" alt="" width={32} height={32} className="size-full object-contain" />
                        </div>
                        <span className="text-2xl font-semibold tracking-tight text-white">NgajiKu</span>
                    </div>

                    <div className="relative max-w-md">
                        <div className="mb-6 motion-safe:animate-fade-up" style={jeda(80)}>
                            <Ornament type="divider" className="text-amber-200" />
                        </div>

                        <h2
                            className="text-4xl leading-tight font-semibold text-balance text-white motion-safe:animate-fade-up xl:text-[2.75rem] xl:leading-[1.15]"
                            style={jeda(120)}
                        >
                            Kelola pembelajaran{' '}
                            <span className="whitespace-nowrap bg-linear-to-r from-amber-100 via-amber-200 to-amber-100 bg-clip-text text-transparent">
                                Al-Qur’an
                            </span>{' '}
                            dengan lebih mudah.
                        </h2>
                        <p className="mt-5 text-base leading-relaxed text-emerald-50/90 motion-safe:animate-fade-up" style={jeda(200)}>
                            Sistem informasi administrasi lembaga pendidikan Al-Qur’an untuk santri, pengajar, staff, dan admin dalam satu tempat.
                        </p>

                        <ul className="mt-10 space-y-4">
                            {FITUR.map((fitur, index) => (
                                <li key={fitur} className="flex items-center gap-3 text-sm text-emerald-50 motion-safe:animate-fade-up" style={jeda(300 + index * 60)}>
                                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-amber-100 ring-1 ring-amber-200/40">
                                        <Icon icon={Check} className="size-3.5" />
                                    </span>
                                    {fitur}
                                </li>
                            ))}
                        </ul>
                    </div>

                    <p className="relative text-sm text-emerald-50/80 motion-safe:animate-fade-in" style={jeda(500)}>
                        © {tahun} NgajiKu. All rights reserved.
                    </p>
                </aside>

                {/* Panel form */}
                <main className="relative flex w-full items-center justify-center px-4 py-10 sm:px-6 lg:w-1/2">
                    <div className="pointer-events-none absolute inset-x-0 top-0 h-64 bg-linear-to-b from-emerald-100/60 to-transparent lg:hidden" aria-hidden="true" />

                    <div className="relative w-full max-w-md">
                        {/* Logo (mobile) */}
                        <div className="mb-8 flex flex-col items-center text-center lg:hidden">
                            <div className="mb-3 flex size-16 items-center justify-center rounded-2xl bg-white p-2.5 shadow-lg shadow-emerald-900/10 ring-1 ring-slate-100 motion-safe:animate-scale-in">
                                <img src="/assets/images/logo/Ngajiku_Logo.png" alt="" width={44} height={44} className="size-full object-contain" />
                            </div>
                            <span className="text-2xl font-semibold tracking-tight text-emerald-700 motion-safe:animate-fade-up" style={jeda(80)}>
                                NgajiKu
                            </span>
                        </div>

                        <div
                            className="rounded-3xl bg-white p-7 shadow-xl shadow-slate-200/70 ring-1 ring-slate-100 motion-safe:animate-fade-up sm:p-10 lg:bg-transparent lg:p-0 lg:shadow-none lg:ring-0 lg:motion-safe:animate-none"
                            style={jeda(120)}
                        >
                            <div className="mb-8">
                                <h1 className="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl lg:motion-safe:animate-fade-up" style={jeda(100)}>
                                    Assalamu’alaikum
                                </h1>
                                <p className="mt-2 text-sm text-slate-500 lg:motion-safe:animate-fade-up" style={jeda(160)}>
                                    Silakan masuk ke akun Anda untuk melanjutkan.
                                </p>
                            </div>

                            {flash.status && !pesanError && (
                                <div
                                    role="status"
                                    className="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 motion-safe:animate-fade-up"
                                    style={jeda(200)}
                                >
                                    <Icon icon={CircleCheck} className="mt-0.5 size-5 text-emerald-600" />
                                    <span>{flash.status}</span>
                                </div>
                            )}

                            {/* Notifikasi error: bergetar sekali setiap percobaan gagal */}
                            {pesanError && (
                                <div
                                    key={percobaanGagal}
                                    id="login-error"
                                    role="alert"
                                    className="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 motion-safe:animate-shake"
                                >
                                    <Icon icon={CircleAlert} className="mt-0.5 size-5 text-red-500" />
                                    <span>{pesanError}</span>
                                </div>
                            )}

                            <form onSubmit={submit} className="space-y-5" noValidate>
                                <div className="group/field lg:motion-safe:animate-fade-up" style={jeda(220)}>
                                    <label
                                        htmlFor="email"
                                        className="mb-2 block text-sm font-medium text-slate-700 transition-colors duration-200 group-focus-within/field:text-emerald-700"
                                    >
                                        Email
                                    </label>

                                    <div className="relative">
                                        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition-colors duration-200 group-focus-within/field:text-emerald-600">
                                            <Icon icon={Mail} className="size-5" />
                                        </span>

                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            value={form.data.email}
                                            onChange={(event) => form.setData('email', event.target.value)}
                                            required
                                            autoFocus
                                            autoComplete="username"
                                            placeholder="nama@email.com"
                                            aria-invalid={form.errors.email ? true : undefined}
                                            aria-describedby={form.errors.email ? 'login-error' : undefined}
                                            className={cn(INPUT, 'pr-4')}
                                        />
                                    </div>
                                </div>

                                <div className="group/field lg:motion-safe:animate-fade-up" style={jeda(280)}>
                                    <label
                                        htmlFor="password"
                                        className="mb-2 block text-sm font-medium text-slate-700 transition-colors duration-200 group-focus-within/field:text-emerald-700"
                                    >
                                        Password
                                    </label>

                                    <div className="relative">
                                        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition-colors duration-200 group-focus-within/field:text-emerald-600">
                                            <Icon icon={Lock} className="size-5" />
                                        </span>

                                        <input
                                            id="password"
                                            name="password"
                                            type={showPassword ? 'text' : 'password'}
                                            value={form.data.password}
                                            onChange={(event) => form.setData('password', event.target.value)}
                                            required
                                            autoComplete="current-password"
                                            placeholder="Masukkan password"
                                            className={cn(INPUT, 'pr-12')}
                                        />

                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                                            aria-pressed={showPassword}
                                            className="absolute inset-y-0 right-0 grid w-12 place-items-center rounded-r-xl text-slate-400 transition-colors duration-200 hover:text-emerald-600 focus-visible:text-emerald-600 focus-visible:outline-none"
                                        >
                                            <span key={String(showPassword)} className="motion-safe:animate-scale-in">
                                                <Icon icon={showPassword ? EyeOff : Eye} className="size-5" />
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <div className="pt-2 lg:motion-safe:animate-fade-up" style={jeda(340)}>
                                    <button
                                        type="submit"
                                        disabled={form.processing}
                                        aria-busy={form.processing}
                                        className="group relative flex h-12 w-full items-center justify-center gap-2 overflow-hidden rounded-xl bg-linear-to-r from-emerald-700 to-teal-700 px-4 text-sm font-semibold text-white shadow-lg shadow-emerald-700/25 transition duration-200 hover:-translate-y-px hover:shadow-xl hover:shadow-emerald-700/30 focus-visible:ring-4 focus-visible:ring-emerald-500/30 focus-visible:outline-none active:translate-y-0 active:scale-[0.98] disabled:cursor-wait disabled:opacity-80 motion-reduce:transform-none"
                                    >
                                        {/* Kilau tipis yang menyapu saat hover */}
                                        <span
                                            className="pointer-events-none absolute inset-y-0 -left-1/2 w-1/2 -translate-x-full skew-x-[-20deg] bg-linear-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 ease-out group-hover:translate-x-[400%] motion-reduce:hidden"
                                            aria-hidden="true"
                                        />

                                        {form.processing ? (
                                            <>
                                                <Icon icon={LoaderCircle} className="size-5 animate-spin" />
                                                Memproses...
                                            </>
                                        ) : (
                                            <>
                                                Masuk
                                                <Icon
                                                    icon={ArrowRight}
                                                    className="size-4 transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transition-none"
                                                />
                                            </>
                                        )}
                                    </button>
                                </div>
                            </form>

                            <div
                                className="mt-8 flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-600 ring-1 ring-slate-100 motion-safe:animate-fade-in"
                                style={jeda(450)}
                            >
                                <Icon icon={Lock} className="size-4 text-emerald-600" />
                                Belum punya akun atau lupa password? Hubungi admin lembaga Anda.
                            </div>
                        </div>

                        <p className="mt-8 text-center text-xs text-slate-500 motion-safe:animate-fade-in lg:hidden" style={jeda(500)}>
                            © {tahun} NgajiKu. All rights reserved.
                        </p>
                    </div>
                </main>
            </div>
        </>
    );
}
