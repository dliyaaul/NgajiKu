<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <meta name="application-name" content="NgajiKu">
    <meta name="description" content="NgajiKu - Sistem Informasi Administrasi Lembaga Pendidikan Al-Qur’an">
    <meta name="theme-color" content="#047857">

    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo/Ngajiku_Logo.png') }}">

    {{-- Terapkan tema & status sidebar sebelum halaman digambar agar tidak berkedip --}}
    <script>
        (() => {
            const read = (key) => {
                try {
                    return localStorage.getItem(key);
                } catch (error) {
                    return null;
                }
            };

            const theme = read('theme');
            const isDark = theme ? theme === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            const html = document.documentElement;

            html.classList.toggle('dark', isDark);
            html.style.colorScheme = isDark ? 'dark' : 'light';
            html.dataset.sidebar = read('sidebar') === 'collapsed' ? 'collapsed' : 'expanded';
        })();
    </script>

    @fonts
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    <x-inertia::head>
        <title>NgajiKu</title>
    </x-inertia::head>
</head>

<body>
    <x-inertia::app />
</body>

</html>
