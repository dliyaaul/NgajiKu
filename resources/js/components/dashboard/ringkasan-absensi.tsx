import { Link } from "@inertiajs/react";
import {
    Alert,
    Avatar,
    Button,
    Card,
    Col,
    Empty,
    List,
    Row,
    Space,
    Statistic,
    Tag,
    Typography,
} from "antd";
import {
    ArrowRightOutlined,
    BookOutlined,
    CalendarOutlined,
    ClockCircleOutlined,
    FileTextOutlined,
    LockOutlined,
    RiseOutlined,
    TeamOutlined,
} from "@ant-design/icons";

import { PROGRAM } from "@/lib/program";
import { formatTanggal, sisaWaktu } from "@/lib/utils";
import absensi from "@/routes/absensi";
import type { PertemuanRingkas, ProgramKey, Rekap } from "@/types";

import { GrafikAbsensi } from "@/components/dashboard/grafik-absensi";
import type {
    GrafikAbsensiItem,
    FilterGrafik,
} from "@/components/dashboard/grafik-absensi";

const { Text } = Typography;

export interface RingkasanAbsensiProps {
    programs: Array<{
        program: ProgramKey;
        jumlah_kelas: number;
    }>;
    gagal: string[];
    rentangHari: number;
    jumlahKelas: number;
    jumlahPertemuan: number;
    persenHadir: number | null;
    terbuka: PertemuanRingkas[];
    belumDiisi: number;
    terbaru: PertemuanRingkas[];
    grafikAbsensi: GrafikAbsensiItem[];
    filterGrafik: FilterGrafik;
}

const WARNA_STATUS: Record<string, string> = {
    hadir: "success",
    izin: "processing",
    sakit: "warning",
    alpha: "error",
};

function RekapTags({ rekap, kosong }: { rekap: Rekap; kosong: string }) {
    const entries = Object.entries(rekap);

    if (entries.length === 0) {
        return <Tag>{kosong}</Tag>;
    }

    return (
        <Space size={[4, 6]} wrap>
            {entries.map(([status, jumlah]) => (
                <Tag
                    key={status}
                    color={WARNA_STATUS[status.toLowerCase()] ?? "default"}
                >
                    {status}: {jumlah}
                </Tag>
            ))}
        </Space>
    );
}

function KartuStatistik({
    judul,
    nilai,
    keterangan,
    ikon,
    warna,
    suffix,
}: {
    judul: string;
    nilai: number | string;
    keterangan: string;
    ikon: React.ReactNode;
    warna: string;
    suffix?: string;
}) {
    return (
        <Card
            className="h-full"
            styles={{
                body: {
                    padding: 18,
                },
            }}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <Statistic
                        title={judul}
                        value={nilai}
                        suffix={suffix}
                        valueStyle={{
                            color: "#101828",
                            fontSize: 26,
                            fontWeight: 650,
                        }}
                    />

                    <Text type="secondary" className="mt-1 block text-xs">
                        {keterangan}
                    </Text>
                </div>

                <Avatar
                    shape="square"
                    size={42}
                    style={{
                        backgroundColor: `${warna}15`,
                        color: warna,
                        fontSize: 19,
                    }}
                >
                    {ikon}
                </Avatar>
            </div>
        </Card>
    );
}

function IkonProgram() {
    return (
        <Avatar
            shape="square"
            size={40}
            style={{
                backgroundColor: "#04785715",
                color: "#047857",
                fontSize: 18,
            }}
        >
            <BookOutlined />
        </Avatar>
    );
}

function PanelRekapAbsensi({ filter }: { filter: FilterGrafik }) {
    const periodeMulai = formatTanggal(filter.mulai);
    const periodeSampai = formatTanggal(filter.sampai);

    const namaProgram = filter.program
        ? PROGRAM[filter.program].label
        : "Semua Program";

    const parameterRekap = new URLSearchParams({
        mulai: filter.mulai,
        sampai: filter.sampai,
        program: filter.program ?? "",
    });

    return (
        <Card
            className="h-full"
            title={
                <Space size={8}>
                    <FileTextOutlined
                        style={{
                            color: "#059669",
                        }}
                    />
                    <span>Rekap Absensi</span>
                </Space>
            }
            extra={<Tag color="success">Laporan</Tag>}
        >
            <div className="flex h-full flex-col">
                <div className="mb-5">
                    <Text type="secondary" className="block text-sm">
                        Lihat laporan kehadiran santri berdasarkan program,
                        kelas, dan periode pembelajaran.
                    </Text>
                </div>

                <div className="mb-5 space-y-3 rounded-xl bg-gray-50 p-4">
                    <div>
                        <Text type="secondary" className="mb-1 block text-xs">
                            Periode laporan
                        </Text>

                        <Text strong className="text-sm">
                            {periodeMulai} – {periodeSampai}
                        </Text>
                    </div>

                    <div>
                        <Text type="secondary" className="mb-1 block text-xs">
                            Program
                        </Text>

                        <Text strong className="text-sm">
                            {namaProgram}
                        </Text>
                    </div>
                </div>

                <div className="mt-auto">
                    <Link href={`/absensi/rekap?${parameterRekap.toString()}`}>
                        <Button
                            type="primary"
                            icon={<FileTextOutlined />}
                            block
                            size="large"
                        >
                            Lihat Rekap Absensi
                        </Button>
                    </Link>

                    <Text
                        type="secondary"
                        className="mt-3 block text-center text-xs"
                    >
                        Lihat detail dan analisis kehadiran santri
                    </Text>
                </div>
            </div>
        </Card>
    );
}

/**
 * Ringkasan absensi untuk dashboard admin & pengajar.
 * `cakupan`: teks "kelas Anda" / "seluruh lembaga".
 */
export function RingkasanAbsensi({
    cakupan,
    ...data
}: RingkasanAbsensiProps & {
    cakupan: string;
}) {
    const {
        gagal,
        rentangHari,
        persenHadir,
        terbuka,
        belumDiisi,
        terbaru,
        grafikAbsensi,
        filterGrafik,
    } = data;

    return (
        <div className="space-y-6">
            {/* Peringatan data */}
            {gagal.length > 0 && (
                <Alert
                    type="warning"
                    showIcon
                    message="Sebagian data absensi belum tersedia"
                    description={`Data ${gagal.join(", ")} belum dapat dimuat, sehingga angka di bawah belum lengkap. Muat ulang halaman beberapa saat lagi.`}
                />
            )}

            {/* Statistik absensi */}
            <Row gutter={[16, 16]}>
                <Col xs={12} xl={6}>
                    <KartuStatistik
                        judul="Kelas Aktif"
                        nilai={data.jumlahKelas}
                        keterangan={`Pada ${cakupan}`}
                        ikon={<TeamOutlined />}
                        warna="#047857"
                    />
                </Col>

                <Col xs={12} xl={6}>
                    <KartuStatistik
                        judul="Pertemuan"
                        nilai={data.jumlahPertemuan}
                        keterangan={`${rentangHari} hari terakhir`}
                        ikon={<CalendarOutlined />}
                        warna="#2563eb"
                    />
                </Col>

                <Col xs={12} xl={6}>
                    <KartuStatistik
                        judul="Kehadiran"
                        nilai={persenHadir === null ? "—" : persenHadir}
                        suffix={persenHadir === null ? undefined : "%"}
                        keterangan={
                            persenHadir === null
                                ? "Belum ada absensi tercatat"
                                : `Santri hadir, ${rentangHari} hari terakhir`
                        }
                        ikon={<RiseOutlined />}
                        warna="#039855"
                    />
                </Col>

                <Col xs={12} xl={6}>
                    <KartuStatistik
                        judul="Belum Diisi"
                        nilai={belumDiisi}
                        keterangan={`${terbuka.length} absensi masih terbuka`}
                        ikon={<FileTextOutlined />}
                        warna={belumDiisi > 0 ? "#d97706" : "#667085"}
                    />
                </Col>
            </Row>

            {/* Grafik dan panel rekap absensi */}
            <Row gutter={[16, 16]} align="stretch">
                <Col xs={24} xl={17}>
                    <GrafikAbsensi data={grafikAbsensi} filter={filterGrafik} />
                </Col>

                <Col xs={24} xl={7}>
                    <PanelRekapAbsensi filter={filterGrafik} />
                </Col>
            </Row>

            {/* Absensi terbuka dan program pembelajaran */}
            <Row gutter={[16, 16]} align="stretch">
                <Col xs={24} lg={16}>
                    <Card
                        title="Absensi Masih Terbuka"
                        extra={
                            <Tag
                                color={
                                    terbuka.length > 0 ? "warning" : "success"
                                }
                            >
                                {terbuka.length} pertemuan
                            </Tag>
                        }
                        className="h-full"
                    >
                        <Text type="secondary" className="mb-4 block text-sm">
                            Absensi dapat diisi atau diubah hingga 24 jam sejak
                            pertemuan dibuat.
                        </Text>

                        {terbuka.length === 0 ? (
                            <Empty
                                image={Empty.PRESENTED_IMAGE_SIMPLE}
                                description={
                                    <div>
                                        <Text strong className="block">
                                            Tidak ada absensi yang menunggu
                                        </Text>

                                        <Text
                                            type="secondary"
                                            className="text-sm"
                                        >
                                            Buat pertemuan baru dari menu
                                            Absensi untuk mulai mencatat
                                            kehadiran.
                                        </Text>
                                    </div>
                                }
                            />
                        ) : (
                            <List
                                dataSource={terbuka}
                                split
                                renderItem={(item) => (
                                    <List.Item
                                        key={item.id}
                                        className="py-4!"
                                        actions={[
                                            <Link
                                                key="aksi"
                                                href={absensi.show.url({
                                                    program: item.program,
                                                    pertemuan: item.id,
                                                })}
                                            >
                                                <Button
                                                    type="primary"
                                                    size="small"
                                                    icon={
                                                        <ArrowRightOutlined />
                                                    }
                                                >
                                                    Isi Absensi
                                                </Button>
                                            </Link>,
                                        ]}
                                    >
                                        <List.Item.Meta
                                            avatar={<IkonProgram />}
                                            title={
                                                <Text strong>{item.kelas}</Text>
                                            }
                                            description={
                                                <div className="space-y-2">
                                                    <Text
                                                        type="secondary"
                                                        className="block text-xs"
                                                    >
                                                        {
                                                            PROGRAM[
                                                                item.program
                                                            ].label
                                                        }
                                                        {" · "}
                                                        {formatTanggal(
                                                            item.tanggal,
                                                        )}
                                                    </Text>

                                                    <RekapTags
                                                        rekap={item.rekap}
                                                        kosong="Belum diisi"
                                                    />

                                                    {item.batas_edit && (
                                                        <Text
                                                            type="secondary"
                                                            className="block text-xs"
                                                        >
                                                            <ClockCircleOutlined />
                                                            {" Terkunci "}
                                                            {sisaWaktu(
                                                                item.batas_edit,
                                                            )}
                                                            {" lagi"}
                                                        </Text>
                                                    )}
                                                </div>
                                            }
                                        />
                                    </List.Item>
                                )}
                            />
                        )}
                    </Card>
                </Col>

                <Col xs={24} lg={8}>
                    <Card title="Program Pembelajaran" className="h-full">
                        <List
                            dataSource={data.programs}
                            locale={{
                                emptyText: "Belum ada program tersedia",
                            }}
                            renderItem={(row) => (
                                <List.Item
                                    key={row.program}
                                    className="px-0!"
                                    actions={[
                                        <Link
                                            key="program"
                                            href={absensi.kelas.url(
                                                row.program,
                                            )}
                                        >
                                            <Button
                                                type="text"
                                                size="small"
                                                icon={<ArrowRightOutlined />}
                                            />
                                        </Link>,
                                    ]}
                                >
                                    <List.Item.Meta
                                        avatar={<IkonProgram />}
                                        title={
                                            <Link
                                                href={absensi.kelas.url(
                                                    row.program,
                                                )}
                                                className="font-semibold"
                                            >
                                                {PROGRAM[row.program].label}
                                            </Link>
                                        }
                                        description={
                                            <Text
                                                type="secondary"
                                                className="text-xs"
                                            >
                                                {row.jumlah_kelas} kelas aktif
                                            </Text>
                                        }
                                    />
                                </List.Item>
                            )}
                        />
                    </Card>
                </Col>
            </Row>

            {/* Riwayat pertemuan */}
            {terbaru.length > 0 && (
                <Card
                    title="Pertemuan Terkunci Terbaru"
                    extra={
                        <Space size={6}>
                            <LockOutlined />
                            <Text type="secondary" className="text-xs">
                                Riwayat
                            </Text>
                        </Space>
                    }
                >
                    <Text type="secondary" className="mb-4 block text-sm">
                        Pertemuan dalam {rentangHari} hari terakhir yang sudah
                        melewati batas waktu pengeditan.
                    </Text>

                    <List
                        dataSource={terbaru}
                        renderItem={(item) => (
                            <List.Item
                                key={item.id}
                                className="py-3!"
                                actions={[
                                    <Link
                                        key="detail"
                                        href={absensi.show.url({
                                            program: item.program,
                                            pertemuan: item.id,
                                        })}
                                    >
                                        <Button type="text" size="small">
                                            Detail <ArrowRightOutlined />
                                        </Button>
                                    </Link>,
                                ]}
                            >
                                <List.Item.Meta
                                    avatar={
                                        <Avatar
                                            shape="square"
                                            style={{
                                                backgroundColor: "#f2f4f7",
                                                color: "#667085",
                                            }}
                                            icon={<LockOutlined />}
                                        />
                                    }
                                    title={
                                        <Space wrap size={[6, 6]}>
                                            <Tag color="green">
                                                {PROGRAM[item.program].label}
                                            </Tag>

                                            <Text strong>{item.kelas}</Text>
                                        </Space>
                                    }
                                    description={
                                        <div className="space-y-2">
                                            <Text
                                                type="secondary"
                                                className="block text-xs"
                                            >
                                                {formatTanggal(item.tanggal)}
                                            </Text>

                                            <RekapTags
                                                rekap={item.rekap}
                                                kosong="Tidak diisi"
                                            />
                                        </div>
                                    }
                                />
                            </List.Item>
                        )}
                    />
                </Card>
            )}
        </div>
    );
}
