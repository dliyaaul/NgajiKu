import { Link, usePage } from "@inertiajs/react";
import {
    Alert,
    Avatar,
    Button,
    Card,
    Col,
    ConfigProvider,
    Empty,
    Row,
    Space,
    Statistic,
    Timeline,
    Typography,
} from "antd";
import {
    AppstoreOutlined,
    ArrowRightOutlined,
    BookOutlined,
    CalendarOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    FileTextOutlined,
    ReadOutlined,
    RiseOutlined,
    SwapOutlined,
    TeamOutlined,
    UserAddOutlined,
    WalletOutlined,
} from "@ant-design/icons";
import { useEffect, useState, type ReactNode } from "react";

import {
    RingkasanAbsensi,
    type RingkasanAbsensiProps,
} from "@/components/dashboard/ringkasan-absensi";
import AppLayout from "@/layouts/app-layout";
import { formatRupiah } from "@/lib/pembayaran";
import { formatWaktu } from "@/lib/utils";
import absensi from "@/routes/absensi";
import admin from "@/routes/admin";
import { getNgajikuAntdTheme, type NgajiKuThemeMode } from "@/lib/antd-theme";

const { Title, Text } = Typography;

interface RingkasanNominal {
    jumlah: number;
    nominal: number;
}

interface Aktivitas {
    id: string;
    jenis: string;
    judul: string;
    deskripsi: string;
    waktu: string;
}

interface Props extends RingkasanAbsensiProps {
    pembayaranMenunggu: number;

    akademik: {
        totalSantri: number;
        totalPendaftaran: number;
        totalPengajar: number;
        totalKelasAktif: number;
        totalProgram: number;
    };

    keuangan: {
        totalTagihan: RingkasanNominal;
        lunas: RingkasanNominal;
        belumDibayar: RingkasanNominal;
        menungguVerifikasi: RingkasanNominal;
    };

    aktivitasTerbaru: Aktivitas[];
}

interface StatistikItem {
    label: string;
    nilai: number;
    keterangan: string;
    ikon: ReactNode;
    warna: string;
}

const IKON_AKTIVITAS: Record<string, ReactNode> = {
    santri_baru: <UserAddOutlined />,
    pendaftaran_program: <BookOutlined />,
    perpindahan_kelas: <SwapOutlined />,
    pertemuan: <CalendarOutlined />,
    verifikasi_pembayaran: <CheckCircleOutlined />,
};

function BagianJudul({
    judul,
    deskripsi,
}: {
    judul: string;
    deskripsi: string;
}) {
    return (
        <div className="mb-4">
            <Title level={5} className="mb-1!">
                {judul}
            </Title>
            <Text type="secondary" className="text-sm">
                {deskripsi}
            </Text>
        </div>
    );
}

function KartuStatistik({ item }: { item: StatistikItem }) {
    return (
        <Card
            className="h-full relative overflow-hidden"
            styles={{
                body: {
                    padding: 14,
                    position: "relative",
                },
            }}
            style={{
                borderRadius: 13,
                border: "1px solid #EAECF0",
                boxShadow: "0 2px 8px rgba(16, 24, 40, 0.04)",
            }}
        >
            {/* Konten utama */}
            <div className="relative z-10 flex items-center gap-3">
                {/* Ikon */}
                <Avatar
                    shape="square"
                    size={40}
                    style={{
                        flexShrink: 0,
                        backgroundColor: `${item.warna}18`,
                        color: item.warna,
                        fontSize: 19,
                        borderRadius: 11,
                    }}
                >
                    {item.ikon}
                </Avatar>

                {/* Informasi statistik */}
                <div className="min-w-0 flex-1">
                    {/* Label */}
                    <Text
                        className="block"
                        style={{
                            fontSize: 12,
                            fontWeight: 500,
                            color: "#667085",
                            lineHeight: "18px",
                            marginBottom: 1,
                        }}
                    >
                        {item.label}
                    </Text>

                    {/* Nilai statistik */}
                    <Statistic
                        value={item.nilai}
                        valueStyle={{
                            fontSize: 23,
                            fontWeight: 700,
                            lineHeight: "28px",
                            color: "#101828",
                            letterSpacing: "-0.7px",
                            margin: 0,
                        }}
                    />

                    {/* Keterangan */}
                    <Text
                        className="block"
                        style={{
                            fontSize: 10,
                            fontWeight: 400,
                            color: "#98A2B3",
                            lineHeight: "15px",
                            marginTop: 1,
                        }}
                    >
                        {item.keterangan}
                    </Text>
                </div>
            </div>

            {/* Dekorasi gelombang SVG */}
            <svg
                className="absolute bottom-0 right-0 pointer-events-none"
                width="100%"
                height="38"
                viewBox="0 0 300 38"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg"
                style={{
                    zIndex: 0,
                }}
            >
                {/* Gelombang utama */}
                <path
                    d="
                        M 100 38
                        C 155 38, 175 29, 205 19
                        C 235 9, 260 13, 300 0
                        L 300 38
                        Z
                    "
                    fill={item.warna}
                    opacity="0.12"
                />

                {/* Gelombang kedua */}
                <path
                    d="
                        M 165 38
                        C 195 34, 220 25, 245 23
                        C 270 21, 285 15, 300 10
                        L 300 38
                        Z
                    "
                    fill={item.warna}
                    opacity="0.10"
                />
            </svg>
        </Card>
    );
}

function KartuKeuangan({
    judul,
    data,
    warna,
    ikon,
}: {
    judul: string;
    data: RingkasanNominal;
    warna: string;
    ikon: ReactNode;
}) {
    return (
        <Card
            className="h-full relative overflow-hidden"
            styles={{
                body: {
                    padding: 16,
                    position: "relative",
                },
            }}
            style={{
                borderRadius: 13,
                border: "1px solid #EAECF0",
                boxShadow: "0 2px 8px rgba(16, 24, 40, 0.04)",
            }}
        >
            {/* Konten utama */}
            <div className="relative z-10">
                {/* Header */}
                <div className="flex items-center gap-3 mb-4">
                    <Avatar
                        shape="square"
                        size={40}
                        style={{
                            flexShrink: 0,
                            backgroundColor: `${warna}18`,
                            color: warna,
                            fontSize: 18,
                            borderRadius: 11,
                        }}
                    >
                        {ikon}
                    </Avatar>

                    <Text
                        className="block"
                        style={{
                            fontSize: 12,
                            fontWeight: 500,
                            color: "#667085",
                            lineHeight: "18px",
                        }}
                    >
                        {judul}
                    </Text>
                </div>

                {/* Nominal */}
                <Statistic
                    value={data.nominal}
                    formatter={() => formatRupiah(data.nominal)}
                    valueStyle={{
                        fontSize: 22,
                        fontWeight: 700,
                        lineHeight: "30px",
                        letterSpacing: "-0.6px",
                        color: "#101828",
                        margin: 0,
                    }}
                />

                {/* Jumlah transaksi */}
                <div className="mt-1">
                    <Text
                        className="block"
                        style={{
                            fontSize: 10,
                            fontWeight: 400,
                            color: "#98A2B3",
                            lineHeight: "16px",
                        }}
                    >
                        {data.jumlah.toLocaleString("id-ID")} data
                        transaksi/tagihan
                    </Text>
                </div>
            </div>

            {/* Dekorasi gelombang */}
            <svg
                className="absolute bottom-0 right-0 pointer-events-none"
                width="100%"
                height="38"
                viewBox="0 0 300 38"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg"
                style={{ zIndex: 0 }}
            >
                <path
                    d="
                        M 100 38
                        C 155 38, 175 29, 205 19
                        C 235 9, 260 13, 300 0
                        L 300 38
                        Z
                    "
                    fill={warna}
                    opacity="0.12"
                />

                <path
                    d="
                        M 165 38
                        C 195 34, 220 25, 245 23
                        C 270 21, 285 15, 300 10
                        L 300 38
                        Z
                    "
                    fill={warna}
                    opacity="0.10"
                />
            </svg>
        </Card>
    );
}

export default function DashboardAdmin({
    pembayaranMenunggu,
    akademik,
    keuangan,
    aktivitasTerbaru,
    ...props
}: Props) {
    const { auth, tanggal } = usePage().props;

    const statistikAkademik: StatistikItem[] = [
        {
            label: "Total Santri",
            nilai: akademik.totalSantri,
            keterangan: "Seluruh akun santri",
            ikon: <TeamOutlined />,
            warna: "#047857",
        },
        {
            label: "Pendaftaran",
            nilai: akademik.totalPendaftaran,
            keterangan: "Program yang diikuti",
            ikon: <ReadOutlined />,
            warna: "#2563eb",
        },
        {
            label: "Pengajar",
            nilai: akademik.totalPengajar,
            keterangan: "Akun pengajar",
            ikon: <ReadOutlined />,
            warna: "#7c3aed",
        },
        {
            label: "Kelas Aktif",
            nilai: akademik.totalKelasAktif,
            keterangan: "Kelas pembelajaran",
            ikon: <AppstoreOutlined />,
            warna: "#d97706",
        },
        {
            label: "Program",
            nilai: akademik.totalProgram,
            keterangan: "Program tersedia",
            ikon: <BookOutlined />,
            warna: "#0891b2",
        },
    ];

    const [themeMode, setThemeMode] = useState<NgajiKuThemeMode>(() => {
        return document.documentElement.classList.contains("dark")
            ? "dark"
            : "light";
    });

    useEffect(() => {
        const observer = new MutationObserver(() => {
            setThemeMode(
                document.documentElement.classList.contains("dark")
                    ? "dark"
                    : "light",
            );
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["class"],
        });

        return () => observer.disconnect();
    }, []);

    const antdTheme = getNgajikuAntdTheme(themeMode);

    return (
        <ConfigProvider theme={antdTheme}>
            <AppLayout title="Dashboard Admin">
                <div className="space-y-8">
                    {/* Header dashboard */}

                    <section className="relative isolate flex min-h-[104px] items-center justify-between overflow-hidden rounded-xl border border-emerald-100 bg-gradient-to-r from-[#f2faf6] via-[#e8f8f0] to-[#d0f2e1] px-4 py-3 shadow-sm sm:px-6">
                        {/* Dekorasi latar */}
                        <div className="pointer-events-none absolute -right-10 -top-20 h-48 w-48 rounded-full bg-emerald-200/30 blur-3xl" />

                        {/* Ilustrasi masjid */}

                        {/* Ilustrasi masjid NgajiKu */}
                        <svg
                            viewBox="0 0 360 120"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                            className="pointer-events-none absolute bottom-0 right-[100px] hidden h-full w-[310px] sm:block"
                            preserveAspectRatio="xMidYMax meet"
                            aria-hidden="true"
                        >
                            <defs>
                                <linearGradient
                                    id="domeMain"
                                    x1="175"
                                    y1="35"
                                    x2="225"
                                    y2="105"
                                    gradientUnits="userSpaceOnUse"
                                >
                                    <stop stopColor="#A5E5A9" />
                                    <stop offset=".48" stopColor="#55B982" />
                                    <stop offset="1" stopColor="#159768" />
                                </linearGradient>

                                <linearGradient
                                    id="domeSmall"
                                    x1="85"
                                    y1="65"
                                    x2="125"
                                    y2="100"
                                    gradientUnits="userSpaceOnUse"
                                >
                                    <stop stopColor="#A8E5AE" />
                                    <stop offset="1" stopColor="#38A976" />
                                </linearGradient>

                                <linearGradient
                                    id="towerGreen"
                                    x1="275"
                                    y1="10"
                                    x2="310"
                                    y2="120"
                                    gradientUnits="userSpaceOnUse"
                                >
                                    <stop stopColor="#80D3A0" />
                                    <stop offset="1" stopColor="#36A879" />
                                </linearGradient>

                                <linearGradient
                                    id="buildingGreen"
                                    x1="150"
                                    y1="75"
                                    x2="240"
                                    y2="120"
                                    gradientUnits="userSpaceOnUse"
                                >
                                    <stop stopColor="#C1EBD0" />
                                    <stop offset="1" stopColor="#72C99A" />
                                </linearGradient>

                                <linearGradient
                                    id="windowGreen"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop stopColor="#F0FFF5" />
                                    <stop offset="1" stopColor="#D6F4E2" />
                                </linearGradient>
                            </defs>

                            {/* Bukit latar */}
                            <path
                                d="M0 109C35 95 56 95 83 106C110 117 130 109 155 105C183 99 207 105 235 110C277 118 311 97 360 102V120H0V109Z"
                                fill="#BCEBD0"
                                opacity=".55"
                            />

                            {/* Ornamen bintang */}
                            <path
                                d="M37 40L42 31L47 40L56 45L47 50L42 59L37 50L28 45L37 40Z"
                                fill="#BDEBD1"
                                opacity=".65"
                            />
                            <circle
                                cx="65"
                                cy="65"
                                r="2.5"
                                fill="#A7DFC0"
                                opacity=".65"
                            />

                            {/* Kubah kecil kiri */}
                            <path
                                d="M84 91C84 78 94 68 107 68C120 68 130 78 130 91V95H84V91Z"
                                fill="url(#domeSmall)"
                            />
                            <path
                                d="M81 91C88 77 98 71 107 71C117 71 127 78 133 91H81Z"
                                fill="#53B983"
                            />
                            <path d="M105 70V59H109V70" fill="#72C99A" />
                            <path
                                d="M103 59L107 49L111 59H103Z"
                                fill="#159768"
                            />
                            <path
                                d="M107 47V43"
                                stroke="#159768"
                                strokeWidth="1.5"
                            />

                            {/* Badan bangunan kiri */}
                            <path d="M88 94H130V120H88V94Z" fill="#B9E9CC" />
                            <path
                                d="M96 120V108C96 102 100 99 105 99C110 99 114 102 114 108V120"
                                fill="url(#windowGreen)"
                            />

                            {/* Bangunan utama */}
                            <path
                                d="M145 91H260V120H145V91Z"
                                fill="url(#buildingGreen)"
                            />

                            {/* Kubah utama */}
                            <path
                                d="M151 78C151 48 173 25 202 25C231 25 253 48 253 78V91H151V78Z"
                                fill="url(#domeMain)"
                            />
                            <path
                                d="M147 79C161 47 180 34 202 34C225 34 244 48 257 79H147Z"
                                fill="url(#domeMain)"
                            />

                            {/* Ujung kubah utama */}
                            <path d="M199 31V14H205V31" fill="#7ACF9A" />
                            <path
                                d="M197 15L202 2L207 15H197Z"
                                fill="#159768"
                            />
                            <path
                                d="M202 2V-2"
                                stroke="#159768"
                                strokeWidth="1.5"
                            />

                            {/* Jendela utama */}
                            <path
                                d="M163 120V105C163 96 168 91 174 91C180 91 185 96 185 105V120H163Z"
                                fill="url(#windowGreen)"
                            />
                            <path
                                d="M192 120V103C192 94 197 89 203 89C209 89 214 94 214 103V120H192Z"
                                fill="url(#windowGreen)"
                            />
                            <path
                                d="M222 120V105C222 96 227 91 233 91C239 91 244 96 244 105V120H222Z"
                                fill="url(#windowGreen)"
                            />

                            {/* Menara pendek */}
                            <path d="M261 76H282V120H261V76Z" fill="#A4DFBA" />
                            <path
                                d="M257 76C260 66 267 59 272 59C278 59 284 66 287 76H257Z"
                                fill="#43B27C"
                            />
                            <path d="M270 59V47H275V59" fill="#79CEA0" />
                            <path
                                d="M268 47L272.5 37L277 47H268Z"
                                fill="#159768"
                            />
                            <path
                                d="M266 92C266 87 269 84 272 84C275 84 278 87 278 92V103H266V92Z"
                                fill="#DDF6E7"
                            />

                            {/* Menara tinggi kanan */}
                            <path
                                d="M302 36H328V120H302V36Z"
                                fill="url(#towerGreen)"
                            />
                            <path
                                d="M297 36C300 25 308 18 315 18C322 18 330 25 333 36H297Z"
                                fill="#3EAE77"
                            />
                            <path d="M311 19V7H319V19" fill="#83D4A3" />
                            <path d="M308 8L315 0L322 8H308Z" fill="#159768" />

                            {/* Jendela menara */}
                            <path
                                d="M310 51C310 45 313 41 315 41C318 41 320 45 320 51V65H310V51Z"
                                fill="#DFF7E8"
                                opacity=".8"
                            />

                            {/* Bukit depan */}
                            <path
                                d="M0 119C41 108 62 105 91 111C123 118 142 113 171 110C205 106 230 116 263 113C300 109 329 98 360 103V120H0V119Z"
                                fill="#A7E5C2"
                                opacity=".75"
                            />
                            <path
                                d="M0 119C47 111 69 111 101 116C140 123 170 112 207 114C248 116 282 121 318 111C333 107 347 106 360 107"
                                stroke="#8BD5AC"
                                strokeWidth="1"
                                opacity=".7"
                            />
                        </svg>

                        {/* Konten banner */}
                        <div className="relative z-10 min-w-0">
                            <div className="mb-1 text-[11px] font-medium text-slate-500">
                                Dashboard / Overview
                            </div>

                            <h1 className="text-base font-bold tracking-tight text-slate-900 sm:text-lg">
                                Assalamu'alaikum,{" "}
                                {auth?.username ?? "Administrator"} 👋
                            </h1>

                            <p className="mt-0.5 max-w-xl text-[11px] leading-relaxed text-slate-600 sm:text-xs">
                                Selamat datang kembali. Berikut ringkasan
                                aktivitas, data akademik, dan keuangan NgajiKu.
                            </p>
                        </div>

                        {/* Tombol aksi */}
                        <div className="relative z-10 ml-3 shrink-0">
                            <Button
                                type="primary"
                                size="middle"
                                icon={<CalendarOutlined />}
                                href={absensi.index.url()}
                                className="!rounded-lg !border-emerald-700 !bg-emerald-700 !text-xs !font-semibold !shadow-sm hover:!bg-emerald-800"
                            >
                                Kelola Absensi
                            </Button>
                        </div>
                    </section>

                    {/* Pemberitahuan pembayaran */}
                    {pembayaranMenunggu > 0 && (
                        <Alert
                            type="warning"
                            showIcon
                            icon={<WalletOutlined />}
                            message={`${pembayaranMenunggu} pembayaran menunggu verifikasi`}
                            description="Periksa bukti transfer untuk memperbarui status tagihan santri."
                            action={
                                <Link
                                    href={admin.pembayaran.verifikasi.index.url()}
                                >
                                    <Button type="primary" size="small">
                                        Verifikasi <ArrowRightOutlined />
                                    </Button>
                                </Link>
                            }
                        />
                    )}

                    {/* Statistik akademik */}
                    <section>
                        <BagianJudul
                            judul="Statistik Akademik"
                            deskripsi="Gambaran umum data santri dan kegiatan pembelajaran."
                        />

                        <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                            {statistikAkademik.map((item) => (
                                <KartuStatistik key={item.label} item={item} />
                            ))}
                        </div>
                    </section>

                    {/* Statistik keuangan */}
                    <section>
                        <BagianJudul
                            judul="Ringkasan Keuangan"
                            deskripsi="Pantau tagihan dan pembayaran seluruh lembaga."
                        />

                        <Row gutter={[16, 16]}>
                            <Col xs={24} sm={12} xl={6}>
                                <KartuKeuangan
                                    judul="Total Tagihan"
                                    data={keuangan.totalTagihan}
                                    warna="#047857"
                                    ikon={<FileTextOutlined />}
                                />
                            </Col>

                            <Col xs={24} sm={12} xl={6}>
                                <KartuKeuangan
                                    judul="Sudah Lunas"
                                    data={keuangan.lunas}
                                    warna="#039855"
                                    ikon={<CheckCircleOutlined />}
                                />
                            </Col>

                            <Col xs={24} sm={12} xl={6}>
                                <KartuKeuangan
                                    judul="Belum Dibayar"
                                    data={keuangan.belumDibayar}
                                    warna="#d97706"
                                    ikon={<WalletOutlined />}
                                />
                            </Col>

                            <Col xs={24} sm={12} xl={6}>
                                <KartuKeuangan
                                    judul="Menunggu Verifikasi"
                                    data={keuangan.menungguVerifikasi}
                                    warna="#2563eb"
                                    ikon={<ClockCircleOutlined />}
                                />
                            </Col>
                        </Row>
                    </section>

                    {/* Grafik Kehadiran dan Rekap Absensi */}
                    <section>
                        <RingkasanAbsensi
                            {...props}
                            cakupan="seluruh lembaga"
                        />
                    </section>

                    {/* Aktivitas terbaru */}
                    <section>
                        <BagianJudul
                            judul="Aktivitas Terbaru"
                            deskripsi="Aktivitas akademik dan keuangan yang baru tercatat."
                        />

                        <Card>
                            {aktivitasTerbaru.length === 0 ? (
                                <Empty
                                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                                    description="Belum ada aktivitas terbaru"
                                />
                            ) : (
                                <Timeline
                                    items={aktivitasTerbaru.map((item) => ({
                                        color:
                                            item.jenis ===
                                            "verifikasi_pembayaran"
                                                ? "green"
                                                : "blue",
                                        dot: IKON_AKTIVITAS[item.jenis] ?? (
                                            <RiseOutlined />
                                        ),
                                        children: (
                                            <div className="pb-3">
                                                <div className="flex flex-col justify-between gap-1 sm:flex-row sm:items-start">
                                                    <Text strong>
                                                        {item.judul}
                                                    </Text>

                                                    <Text
                                                        type="secondary"
                                                        className="text-xs"
                                                    >
                                                        {formatWaktu(
                                                            item.waktu,
                                                        )}
                                                    </Text>
                                                </div>

                                                <Text
                                                    type="secondary"
                                                    className="text-sm"
                                                >
                                                    {item.deskripsi}
                                                </Text>
                                            </div>
                                        ),
                                    }))}
                                />
                            )}
                        </Card>
                    </section>
                </div>
            </AppLayout>
        </ConfigProvider>
    );
}
