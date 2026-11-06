
import { Link, usePage } from '@inertiajs/react';
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
} from 'antd';
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
} from '@ant-design/icons';
import type { ReactNode } from 'react';

import {
    RingkasanAbsensi,
    type RingkasanAbsensiProps,
} from '@/components/dashboard/ringkasan-absensi';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/pembayaran';
import { formatWaktu } from '@/lib/utils';
import { ngajikuAntdTheme } from '@/lib/antd-theme';
import absensi from '@/routes/absensi';
import admin from '@/routes/admin';

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
            <Title level={5} className="!mb-1">
                {judul}
            </Title>
            <Text type="secondary" className="text-sm">
                {deskripsi}
            </Text>
        </div>
    );
}

function KartuStatistik({
    item,
}: {
    item: StatistikItem;
}) {
    return (
        <Card
            className="h-full"
            styles={{ body: { padding: 18 } }}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <Text type="secondary" className="text-xs sm:text-sm">
                        {item.label}
                    </Text>

                    <Statistic
                        value={item.nilai}
                        valueStyle={{
                            fontSize: 27,
                            fontWeight: 650,
                            color: '#101828',
                            marginTop: 8,
                        }}
                    />

                    <Text type="secondary" className="text-xs">
                        {item.keterangan}
                    </Text>
                </div>

                <Avatar
                    shape="square"
                    size={42}
                    style={{
                        backgroundColor: `${item.warna}15`,
                        color: item.warna,
                        fontSize: 19,
                    }}
                >
                    {item.ikon}
                </Avatar>
            </div>
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
        <Card className="h-full" styles={{ body: { padding: 18 } }}>
            <Space align="center" className="mb-3">
                <Avatar
                    shape="square"
                    size={34}
                    style={{
                        backgroundColor: `${warna}15`,
                        color: warna,
                    }}
                >
                    {ikon}
                </Avatar>
                <Text type="secondary" className="text-sm">
                    {judul}
                </Text>
            </Space>

            <Statistic
                value={data.nominal}
                formatter={() => formatRupiah(data.nominal)}
                valueStyle={{
                    fontSize: 22,
                    fontWeight: 650,
                    color: '#101828',
                }}
            />

            <div className="mt-2">
                <Text type="secondary" className="text-xs">
                    {data.jumlah.toLocaleString('id-ID')} data transaksi/tagihan
                </Text>
            </div>
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
            label: 'Total Santri',
            nilai: akademik.totalSantri,
            keterangan: 'Seluruh akun santri',
            ikon: <TeamOutlined />,
            warna: '#047857',
        },
        {
            label: 'Pendaftaran',
            nilai: akademik.totalPendaftaran,
            keterangan: 'Program yang diikuti',
            ikon: <ReadOutlined />,
            warna: '#2563eb',
        },
        {
            label: 'Pengajar',
            nilai: akademik.totalPengajar,
            keterangan: 'Akun pengajar',
            ikon: <ReadOutlined />,
            warna: '#7c3aed',
        },
        {
            label: 'Kelas Aktif',
            nilai: akademik.totalKelasAktif,
            keterangan: 'Kelas pembelajaran',
            ikon: <AppstoreOutlined />,
            warna: '#d97706',
        },
        {
            label: 'Program',
            nilai: akademik.totalProgram,
            keterangan: 'Program tersedia',
            ikon: <BookOutlined />,
            warna: '#0891b2',
        },
    ];

    return (
        <ConfigProvider theme={ngajikuAntdTheme}>
            <AppLayout title="Dashboard Admin">
                <div className="space-y-8">
                    {/* Header dashboard */}
                    <section className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <Text type="secondary" className="text-sm">
                                Dashboard / Overview
                            </Text>

                            <Title level={3} className="!mb-1 !mt-2">
                                Dashboard Overview
                            </Title>

                            <Text type="secondary">
                                Selamat datang kembali, {auth?.username ?? 'Administrator'}.
                                Berikut ringkasan aktivitas NgajiKu.
                            </Text>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <div className="text-left sm:text-right">
                                <Text type="secondary" className="block text-xs">
                                    Hari ini
                                </Text>
                                <Text strong>{tanggal?.masehi}</Text>
                            </div>

                            <Button
                                type="primary"
                                icon={<CalendarOutlined />}
                                href={absensi.index.url()}
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
                                <Link href={admin.pembayaran.verifikasi.index.url()}>
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

                    {/* Ringkasan absensi */}
                    <section>
                        <BagianJudul
                            judul="Aktivitas Pembelajaran"
                            deskripsi="Pantau kehadiran dan aktivitas pertemuan seluruh kelas."
                        />

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
                                            item.jenis === 'verifikasi_pembayaran'
                                                ? 'green'
                                                : 'blue',
                                        dot:
                                            IKON_AKTIVITAS[item.jenis] ??
                                            <RiseOutlined />,
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
                                                        {formatWaktu(item.waktu)}
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
