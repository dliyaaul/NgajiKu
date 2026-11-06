import { router, usePage } from "@inertiajs/react";
import {
    CalendarOutlined,
    ReloadOutlined,
    RiseOutlined,
    TeamOutlined,
} from "@ant-design/icons";

import {
    Button,
    Card,
    Col,
    DatePicker,
    Empty,
    Row,
    Segmented,
    Select,
    Space,
    Tooltip,
    Typography,
} from "antd";
import dayjs from "dayjs";
import { useMemo, useState } from "react";

import { PROGRAM } from "@/lib/program";
import type { ProgramKey } from "@/types";

type ModeGrafik = "hadir" | "pertemuan";

const { Text } = Typography;
const { RangePicker } = DatePicker;

export interface GrafikAbsensiItem {
    tanggal: string;
    jumlah_pertemuan: number;
    hadir: number;
    total_absensi: number;
    persen_hadir: number | null;
}

export interface FilterGrafik {
    mulai: string;
    sampai: string;
    program: ProgramKey | null;
}

interface Props {
    data: GrafikAbsensiItem[];
    filter: FilterGrafik;
}

function formatTanggal(tanggal: string, pendek = false) {
    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: pendek ? "short" : "long",
    }).format(new Date(`${tanggal}T00:00:00`));
}

/* =========================================================
   GRAFIK KEHADIRAN
========================================================= */

function GrafikGaris({ data }: { data: GrafikAbsensiItem[] }) {
    const lebar = 720;
    const tinggi = 260;

    const kiri = 42;
    const kanan = 16;
    const atas = 18;
    const bawah = 42;

    const areaLebar = lebar - kiri - kanan;
    const areaTinggi = tinggi - atas - bawah;

    const titik = useMemo(() => {
        return data
            .map((item, index) => {
                if (item.persen_hadir === null) {
                    return null;
                }

                const x =
                    data.length <= 1
                        ? kiri + areaLebar / 2
                        : kiri + (index / (data.length - 1)) * areaLebar;

                const y =
                    atas + areaTinggi - (item.persen_hadir / 100) * areaTinggi;

                return {
                    x,
                    y,
                    item,
                };
            })
            .filter((item): item is NonNullable<typeof item> => item !== null);
    }, [data, areaLebar, areaTinggi]);

    const garis = titik
        .map(
            (point, index) =>
                `${index === 0 ? "M" : "L"} ${point.x} ${point.y}`,
        )
        .join(" ");

    const labelIndex = [0, 1, 2, 3, 4]
        .map((item) => Math.round((item / 4) * Math.max(data.length - 1, 0)))
        .filter((value, index, array) => array.indexOf(value) === index);

    if (titik.length === 0) {
        return (
            <div className="flex min-h-57.5 items-center justify-center">
                <Empty
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                    description="Belum ada data kehadiran"
                />
            </div>
        );
    }

    return (
        <div className="w-full overflow-hidden">
            <svg
                viewBox={`0 0 ${lebar} ${tinggi}`}
                className="block w-full"
                role="img"
                aria-label="Grafik persentase kehadiran harian"
            >
                {/* Garis horizontal */}
                {[0, 25, 50, 75, 100].map((nilai) => {
                    const y = atas + areaTinggi - (nilai / 100) * areaTinggi;

                    return (
                        <g key={nilai}>
                            <line
                                x1={kiri}
                                y1={y}
                                x2={lebar - kanan}
                                y2={y}
                                stroke="#E5E7EB"
                                strokeDasharray="4 5"
                            />

                            <text
                                x={kiri - 10}
                                y={y + 4}
                                textAnchor="end"
                                fontSize="11"
                                fill="#98A2B3"
                            >
                                {nilai}%
                            </text>
                        </g>
                    );
                })}

                {/* Area grafik */}
                {garis && (
                    <>
                        <path
                            d={`${garis} L ${
                                titik[titik.length - 1].x
                            } ${atas + areaTinggi} L ${
                                titik[0].x
                            } ${atas + areaTinggi} Z`}
                            fill="#10B981"
                            opacity="0.08"
                        />

                        <path
                            d={garis}
                            fill="none"
                            stroke="#059669"
                            strokeWidth="3"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </>
                )}

                {/* Titik grafik */}
                {titik.map((point) => (
                    <g key={point.item.tanggal}>
                        <circle
                            cx={point.x}
                            cy={point.y}
                            r="5"
                            fill="#059669"
                            stroke="#FFFFFF"
                            strokeWidth="2"
                        />

                        <title>
                            {formatTanggal(point.item.tanggal)}:{" "}
                            {point.item.persen_hadir}% · {point.item.hadir}{" "}
                            hadir
                        </title>
                    </g>
                ))}

                {/* Label tanggal */}
                {labelIndex.map((index) => {
                    const x =
                        data.length <= 1
                            ? kiri + areaLebar / 2
                            : kiri + (index / (data.length - 1)) * areaLebar;

                    return (
                        <text
                            key={data[index].tanggal}
                            x={x}
                            y={tinggi - 12}
                            textAnchor="middle"
                            fontSize="11"
                            fill="#667085"
                        >
                            {formatTanggal(data[index].tanggal, true)}
                        </text>
                    );
                })}
            </svg>
        </div>
    );
}

/* =========================================================
   GRAFIK PERTEMUAN
========================================================= */

function GrafikPertemuan({ data }: { data: GrafikAbsensiItem[] }) {
    const lebar = 720;
    const tinggi = 260;

    const kiri = 42;
    const kanan = 16;
    const atas = 18;
    const bawah = 42;

    const areaLebar = lebar - kiri - kanan;
    const areaTinggi = tinggi - atas - bawah;

    const nilaiMaksimum = Math.max(
        ...data.map((item) => item.jumlah_pertemuan),
        0,
    );

    const skalaMaksimum = Math.max(nilaiMaksimum, 1);

    const labelIndex = [0, 1, 2, 3, 4]
        .map((item) => Math.round((item / 4) * Math.max(data.length - 1, 0)))
        .filter((value, index, array) => array.indexOf(value) === index);

    if (nilaiMaksimum === 0) {
        return (
            <div className="flex min-h-57.5 items-center justify-center">
                <Empty
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                    description="Belum ada data pertemuan"
                />
            </div>
        );
    }

    const jarak = data.length > 0 ? areaLebar / data.length : 0;

    const lebarBar = Math.max(3, Math.min(22, jarak * 0.55));

    return (
        <div className="w-full overflow-hidden">
            <svg
                viewBox={`0 0 ${lebar} ${tinggi}`}
                className="block w-full"
                role="img"
                aria-label="Grafik jumlah pertemuan harian"
            >
                {/* Garis horizontal */}
                {[0, 1, 2, 3, 4].map((nilai) => {
                    const angka = Math.ceil((skalaMaksimum * nilai) / 4);

                    const y =
                        atas +
                        areaTinggi -
                        (angka / skalaMaksimum) * areaTinggi;

                    return (
                        <g key={nilai}>
                            <line
                                x1={kiri}
                                y1={y}
                                x2={lebar - kanan}
                                y2={y}
                                stroke="#E5E7EB"
                                strokeDasharray="4 5"
                            />

                            <text
                                x={kiri - 10}
                                y={y + 4}
                                textAnchor="end"
                                fontSize="11"
                                fill="#98A2B3"
                            >
                                {angka}
                            </text>
                        </g>
                    );
                })}

                {/* Batang grafik */}
                {data.map((item, index) => {
                    const tinggiBar =
                        (item.jumlah_pertemuan / skalaMaksimum) * areaTinggi;

                    const x = kiri + index * jarak + (jarak - lebarBar) / 2;

                    const y = atas + areaTinggi - tinggiBar;

                    return (
                        <g key={item.tanggal}>
                            <rect
                                x={x}
                                y={y}
                                width={lebarBar}
                                height={Math.max(tinggiBar, 0)}
                                rx="4"
                                fill="#3B82F6"
                            />

                            <title>
                                {formatTanggal(item.tanggal)}:{" "}
                                {item.jumlah_pertemuan} pertemuan
                            </title>
                        </g>
                    );
                })}

                {/* Label tanggal */}
                {labelIndex.map((index) => {
                    const x = kiri + index * jarak + jarak / 2;

                    return (
                        <text
                            key={data[index].tanggal}
                            x={x}
                            y={tinggi - 12}
                            textAnchor="middle"
                            fontSize="11"
                            fill="#667085"
                        >
                            {formatTanggal(data[index].tanggal, true)}
                        </text>
                    );
                })}
            </svg>
        </div>
    );
}

/* =========================================================
   KOMPONEN UTAMA
========================================================= */

export function GrafikAbsensi({ data, filter }: Props) {
    const { url } = usePage();

    const [mode, setMode] = useState<ModeGrafik>("hadir");

    /* Statistik */
    const totalHadir = data.reduce((total, item) => total + item.hadir, 0);

    const totalAbsensi = data.reduce(
        (total, item) => total + item.total_absensi,
        0,
    );

    const totalPertemuan = data.reduce(
        (total, item) => total + item.jumlah_pertemuan,
        0,
    );

    const persenKeseluruhan =
        totalAbsensi > 0 ? Math.round((totalHadir / totalAbsensi) * 100) : null;

    /* Rentang tanggal aktif */
    const jumlahHari =
        dayjs(filter.sampai).diff(dayjs(filter.mulai), "day") + 1;

    const pilihanRentang =
        jumlahHari === 30 || jumlahHari === 60 || jumlahHari === 90
            ? jumlahHari
            : undefined;

    /* Terapkan filter ke backend */
    function terapkanFilter(
        mulai: string,
        sampai: string,
        program: ProgramKey | null,
    ) {
        router.get(
            url.split("?")[0],
            {
                mulai,
                sampai,
                program: program ?? "",
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    /* Ubah rentang tanggal */
    function ubahRentang(hari: 30 | 60 | 90) {
        const akhir = dayjs(filter.sampai);

        const mulai = akhir.subtract(hari - 1, "day").format("YYYY-MM-DD");

        terapkanFilter(mulai, akhir.format("YYYY-MM-DD"), filter.program);
    }

    /* Reset seluruh filter */
    function resetFilter() {
        const sampai = dayjs().format("YYYY-MM-DD");

        const mulai = dayjs().subtract(29, "day").format("YYYY-MM-DD");

        setMode("hadir");

        terapkanFilter(mulai, sampai, null);
    }

    const tanggalDefault = dayjs().format("YYYY-MM-DD");

    const mulaiDefault = dayjs().subtract(29, "day").format("YYYY-MM-DD");

    const filterAktif =
        filter.program !== null ||
        filter.mulai !== mulaiDefault ||
        filter.sampai !== tanggalDefault;

    return (
        <Card
            className="h-full overflow-hidden"
            styles={{
                header: {
                    minHeight: 58,
                    padding: "0 20px",
                    borderBottom: "1px solid #F2F4F7",
                },
                body: {
                    padding: 20,
                },
            }}
            title={
                <Space size={10}>
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50">
                        <RiseOutlined
                            style={{
                                color: "#059669",
                                fontSize: 16,
                            }}
                        />
                    </div>

                    <div>
                        <div className="font-semibold text-gray-800">
                            Analisis Absensi
                        </div>

                        <Text type="secondary" className="text-xs font-normal">
                            Kehadiran dan aktivitas pertemuan
                        </Text>
                    </div>
                </Space>
            }
        >
            <div className="space-y-5">
                {/* Mode grafik */}
                {/* Mode dan filter */}
                <div className="space-y-3">
                    <div className="flex items-center justify-between gap-2">
                        <Segmented
                            size="small"
                            className="segmented-absensi"
                            value={mode}
                            onChange={(value) => setMode(value as ModeGrafik)}
                            options={[
                                {
                                    label: "Kehadiran",
                                    value: "hadir",
                                },
                                {
                                    label: "Pertemuan",
                                    value: "pertemuan",
                                },
                            ]}
                        />

                        {filterAktif && (
                            <Tooltip title="Reset semua filter">
                                <Button
                                    type="text"
                                    size="small"
                                    icon={<ReloadOutlined />}
                                    onClick={resetFilter}
                                    aria-label="Reset semua filter"
                                    className="!text-gray-500 hover:!text-emerald-600"
                                />
                            </Tooltip>
                        )}
                    </div>

                    {/* Filter tanggal, rentang, dan program */}
                    <div className="flex flex-wrap items-center gap-2">
                        <RangePicker
                            size="small"
                            className="!w-[220px]"
                            value={[dayjs(filter.mulai), dayjs(filter.sampai)]}
                            format="DD MMM YYYY"
                            allowClear={false}
                            onChange={(dates) => {
                                if (!dates?.[0] || !dates?.[1]) {
                                    return;
                                }

                                terapkanFilter(
                                    dates[0].format("YYYY-MM-DD"),
                                    dates[1].format("YYYY-MM-DD"),
                                    filter.program,
                                );
                            }}
                        />

                        <Select<30 | 60 | 90>
                            size="small"
                            value={pilihanRentang}
                            placeholder="Rentang"
                            className="!w-[105px]"
                            onChange={ubahRentang}
                            options={[
                                { label: "30 Hari", value: 30 },
                                { label: "60 Hari", value: 60 },
                                { label: "90 Hari", value: 90 },
                            ]}
                        />

                        <Select<ProgramKey | "semua">
                            size="small"
                            value={filter.program ?? "semua"}
                            className="!w-[140px]"
                            onChange={(value) => {
                                terapkanFilter(
                                    filter.mulai,
                                    filter.sampai,
                                    value === "semua" ? null : value,
                                );
                            }}
                            options={[
                                {
                                    label: "Semua Program",
                                    value: "semua",
                                },
                                ...Object.entries(PROGRAM).map(
                                    ([key, item]) => ({
                                        label: item.label,
                                        value: key as ProgramKey,
                                    }),
                                ),
                            ]}
                        />
                    </div>
                </div>

                {/* Statistik ringkas */}
                <Row gutter={[0, 12]} className="items-center">
                    <Col xs={8}>
                        <div className="pr-2 sm:pr-4">
                            <div className="mb-1 flex items-center gap-1.5">
                                <RiseOutlined className="text-emerald-600" />

                                <Text
                                    type="secondary"
                                    className="text-[11px] sm:text-xs"
                                >
                                    Kehadiran
                                </Text>
                            </div>

                            <div className="text-lg font-semibold tracking-tight text-gray-800 sm:text-xl">
                                {persenKeseluruhan === null
                                    ? "—"
                                    : `${persenKeseluruhan}%`}
                            </div>
                        </div>
                    </Col>

                    <Col xs={8}>
                        <div className="border-l border-gray-200 px-3 sm:px-4">
                            <div className="mb-1 flex items-center gap-1.5">
                                <TeamOutlined className="text-blue-600" />

                                <Text
                                    type="secondary"
                                    className="text-[11px] sm:text-xs"
                                >
                                    Santri Hadir
                                </Text>
                            </div>

                            <div className="text-lg font-semibold tracking-tight text-gray-800 sm:text-xl">
                                {totalHadir.toLocaleString("id-ID")}
                            </div>
                        </div>
                    </Col>

                    <Col xs={8}>
                        <div className="border-l border-gray-200 pl-3 sm:pl-4">
                            <div className="mb-1 flex items-center gap-1.5">
                                <CalendarOutlined className="text-indigo-600" />

                                <Text
                                    type="secondary"
                                    className="text-[11px] sm:text-xs"
                                >
                                    Pertemuan
                                </Text>
                            </div>

                            <div className="text-lg font-semibold tracking-tight text-gray-800 sm:text-xl">
                                {totalPertemuan.toLocaleString("id-ID")}
                            </div>
                        </div>
                    </Col>
                </Row>

                {/* Grafik */}
                <div className="min-h-57.5 w-full">
                    {mode === "hadir" ? (
                        <GrafikGaris data={data} />
                    ) : (
                        <GrafikPertemuan data={data} />
                    )}
                </div>

                {/* Footer grafik */}
                <div className="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3">
                    <Text type="secondary" className="text-xs">
                        <CalendarOutlined className="mr-1" />
                        {formatTanggal(filter.mulai)} –{" "}
                        {formatTanggal(filter.sampai)}
                    </Text>

                    <Text type="secondary" className="text-xs">
                        {filter.program
                            ? PROGRAM[filter.program].label
                            : "Semua Program"}
                    </Text>
                </div>
            </div>
        </Card>
    );
}
