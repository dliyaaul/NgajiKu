import { router } from '@inertiajs/react';
import {
    CalendarOutlined,
    FilterOutlined,
    ReloadOutlined,
} from '@ant-design/icons';
import {
    Button,
    Card,
    Col,
    DatePicker,
    Empty,
    Row,
    Select,
    Space,
    Statistic,
    Table,
    Tag,
    Typography,
} from 'antd';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';

import { PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { PROGRAM } from '@/lib/program';
import absensi from '@/routes/absensi';
import type { BreadcrumbItem, ProgramKey } from '@/types';

const { RangePicker } = DatePicker;
const { Text } = Typography;

type Status = 'Hadir' | 'Izin' | 'Sakit' | 'Alpha';

interface Pertemuan {
    id: string;
    program: ProgramKey;
    tanggal: string;
    kelas_id: string;
    kelas: string;
    materi: string | null;
    rekap: Record<Status, number>;
}

interface Santri {
    id: string;
    santri_id: string;
    nama: string;
    program: ProgramKey;
    kelas: string;
    kelas_id: string;
    status: Record<string, Status>;
    hadir: number;
    izin: number;
    sakit: number;
    alpha: number;
    total_pertemuan: number;
    persen_hadir: number | null;
}

interface Filter {
    mulai: string;
    sampai: string;
    program: ProgramKey | null;
    kelas: string | null;
}

interface Props {
    pertemuan: Pertemuan[];
    santri: Santri[];
    ringkasan: {
        jumlah_santri: number;
        jumlah_pertemuan: number;
        hadir: number;
        izin: number;
        sakit: number;
        alpha: number;
        persen_hadir: number | null;
    };
    programs: Array<{
        value: ProgramKey;
        label: string;
        jumlah_kelas: number;
    }>;
    kelasOptions: Array<{
        id: string;
        nama: string;
        program: ProgramKey;
    }>;
    filter: Filter;
}

const WARNA_STATUS: Record<Status, string> = {
    Hadir: 'success',
    Izin: 'processing',
    Sakit: 'warning',
    Alpha: 'error',
};

function formatTanggal(tanggal: string) {
    return dayjs(tanggal).format('DD MMM YYYY');
}

function terapkanFilter(filter: Filter) {
    router.get(
        '/absensi/rekap',
        {
            mulai: filter.mulai,
            sampai: filter.sampai,
            program: filter.program ?? '',
            kelas: filter.kelas ?? '',
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

export default function RekapAbsensi({
    pertemuan,
    santri,
    ringkasan,
    programs,
    kelasOptions,
    filter,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { label: 'Absensi', href: absensi.index.url() },
        { label: 'Rekap Absensi' },
    ];

    const columns: ColumnsType<Santri> = [
        {
            title: 'No.',
            key: 'nomor',
            width: 60,
            fixed: 'left',
            render: (_value, _record, index) => index + 1,
        },
        {
            title: 'Nama Santri',
            dataIndex: 'nama',
            key: 'nama',
            width: 190,
            fixed: 'left',
            render: (nama, record) => (
                <div>
                    <Text strong>{nama}</Text>
                    <Text type="secondary" className="block text-xs">
                        {PROGRAM[record.program].label} · {record.kelas}
                    </Text>
                </div>
            ),
        },
        ...pertemuan.map((item) => ({
            title: (
                <div className="text-center">
                    <Text strong className="block text-xs">
                        {formatTanggal(item.tanggal)}
                    </Text>
                    <Text type="secondary" className="text-xs">
                        {PROGRAM[item.program].label}
                    </Text>
                </div>
            ),
            key: `${item.program}:${item.id}`,
            width: 115,
            align: 'center' as const,
            render: (_value: unknown, record: Santri) => {
                const status = record.status[`${item.program}:${item.id}`];

                return (
                    <Tag color={WARNA_STATUS[status] ?? 'default'}>
                        {status ?? '—'}
                    </Tag>
                );
            },
        })),
        {
            title: 'Hadir',
            dataIndex: 'hadir',
            width: 85,
            align: 'center',
        },
        {
            title: 'Izin',
            dataIndex: 'izin',
            width: 75,
            align: 'center',
        },
        {
            title: 'Sakit',
            dataIndex: 'sakit',
            width: 75,
            align: 'center',
        },
        {
            title: 'Alpha',
            dataIndex: 'alpha',
            width: 75,
            align: 'center',
        },
        {
            title: 'Persentase',
            dataIndex: 'persen_hadir',
            width: 110,
            align: 'center',
            fixed: 'right',
            render: (nilai: number | null) => (
                <Text
                    strong
                    type={
                        nilai === null
                            ? 'secondary'
                            : nilai >= 80
                              ? 'success'
                              : nilai >= 60
                                ? 'warning'
                                : 'danger'
                    }
                >
                    {nilai === null ? '—' : `${nilai}%`}
                </Text>
            ),
        },
    ];

    const kelasTerfilter = kelasOptions.filter(
        (item) => !filter.program || item.program === filter.program,
    );

    return (
        <AppLayout title="Rekap Absensi" breadcrumbs={breadcrumbs}>
            <PageHeader
                title="Rekap Absensi"
                subtitle="Lihat dan pantau rekap kehadiran santri berdasarkan program dan periode."
                breadcrumbs={breadcrumbs}
            />

            <div className="space-y-6">
                {/* Filter */}
                <Card
                    title={
                        <Space>
                            <FilterOutlined />
                            Filter Rekap
                        </Space>
                    }
                >
                    <Row gutter={[16, 16]} align="bottom">
                        <Col xs={24} md={8}>
                            <Text strong className="mb-2 block">
                                Periode
                            </Text>

                            <RangePicker
                                className="w-full"
                                allowClear={false}
                                format="DD MMM YYYY"
                                value={[
                                    dayjs(filter.mulai),
                                    dayjs(filter.sampai),
                                ]}
                                onChange={(dates) => {
                                    if (!dates?.[0] || !dates?.[1]) return;

                                    terapkanFilter({
                                        ...filter,
                                        mulai: dates[0].format('YYYY-MM-DD'),
                                        sampai: dates[1].format('YYYY-MM-DD'),
                                    });
                                }}
                            />
                        </Col>

                        <Col xs={24} md={6}>
                            <Text strong className="mb-2 block">
                                Program
                            </Text>

                            <Select<ProgramKey | 'semua'>
                                className="w-full"
                                value={filter.program ?? 'semua'}
                                onChange={(value) =>
                                    terapkanFilter({
                                        ...filter,
                                        program: value === 'semua' ? null : value,
                                        kelas: null,
                                    })
                                }
                                options={[
                                    {
                                        label: 'Semua Program',
                                        value: 'semua',
                                    },
                                    ...programs.map((item) => ({
                                        label: item.label,
                                        value: item.value,
                                    })),
                                ]}
                            />
                        </Col>

                        <Col xs={24} md={6}>
                            <Text strong className="mb-2 block">
                                Kelas
                            </Text>

                            <Select
                                className="w-full"
                                allowClear
                                showSearch
                                optionFilterProp="label"
                                placeholder="Semua Kelas"
                                value={filter.kelas ?? undefined}
                                onChange={(value) =>
                                    terapkanFilter({
                                        ...filter,
                                        kelas: value ?? null,
                                    })
                                }
                                options={kelasTerfilter.map((item) => ({
                                    label: item.nama,
                                    value: item.id,
                                }))}
                            />
                        </Col>

                        <Col xs={24} md={4}>
                            <Button
                                block
                                icon={<ReloadOutlined />}
                                onClick={() =>
                                    terapkanFilter({
                                        mulai: dayjs()
                                            .subtract(29, 'day')
                                            .format('YYYY-MM-DD'),
                                        sampai: dayjs().format('YYYY-MM-DD'),
                                        program: null,
                                        kelas: null,
                                    })
                                }
                            >
                                Reset
                            </Button>
                        </Col>
                    </Row>
                </Card>

                {/* Statistik */}
                <Row gutter={[16, 16]}>
                    <Col xs={12} xl={4}>
                        <Card>
                            <Statistic
                                title="Total Santri"
                                value={ringkasan.jumlah_santri}
                            />
                        </Card>
                    </Col>

                    <Col xs={12} xl={4}>
                        <Card>
                            <Statistic
                                title="Pertemuan"
                                value={ringkasan.jumlah_pertemuan}
                            />
                        </Card>
                    </Col>

                    <Col xs={12} xl={4}>
                        <Card>
                            <Statistic
                                title="Hadir"
                                value={ringkasan.hadir}
                                valueStyle={{ color: '#039855' }}
                            />
                        </Card>
                    </Col>

                    <Col xs={12} xl={4}>
                        <Card>
                            <Statistic
                                title="Izin"
                                value={ringkasan.izin}
                                valueStyle={{ color: '#1677ff' }}
                            />
                        </Card>
                    </Col>

                    <Col xs={12} xl={4}>
                        <Card>
                            <Statistic
                                title="Sakit"
                                value={ringkasan.sakit}
                                valueStyle={{ color: '#d97706' }}
                            />
                        </Card>
                    </Col>

                    <Col xs={12} xl={4}>
                        <Card>
                            <Statistic
                                title="Alpha"
                                value={ringkasan.alpha}
                                valueStyle={{ color: '#dc2626' }}
                            />
                        </Card>
                    </Col>
                </Row>

                {/* Tabel */}
                <Card
                    title="Detail Rekap Kehadiran Santri"
                    extra={
                        <Tag color="success">
                            {ringkasan.persen_hadir === null
                                ? 'Belum ada data'
                                : `${ringkasan.persen_hadir}% kehadiran`}
                        </Tag>
                    }
                >
                    <Table<Santri>
                        rowKey={(record) => `${record.program}:${record.id}`}
                        columns={columns}
                        dataSource={santri}
                        size="middle"
                        bordered
                        pagination={{
                            pageSize: 10,
                            showSizeChanger: true,
                            showTotal: (total) => `${total} santri`,
                        }}
                        scroll={{ x: 'max-content' }}
                        locale={{
                            emptyText: (
                                <Empty description="Belum ada data rekap pada periode ini" />
                            ),
                        }}
                    />
                </Card>
            </div>
        </AppLayout>
    );
}
