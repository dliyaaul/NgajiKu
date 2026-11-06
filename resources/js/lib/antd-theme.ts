
import { theme as antdTheme } from 'antd';
import type { ThemeConfig } from 'antd';

export type NgajiKuThemeMode = 'light' | 'dark';

export function getNgajikuAntdTheme(mode: NgajiKuThemeMode): ThemeConfig {
    const dark = mode === 'dark';

    return {
        algorithm: dark
            ? antdTheme.darkAlgorithm
            : antdTheme.defaultAlgorithm,

        token: {
            colorPrimary: dark ? '#10b981' : '#047857',
            colorInfo: dark ? '#38bdf8' : '#0ea5e9',
            colorSuccess: dark ? '#34d399' : '#039855',
            colorWarning: dark ? '#fbbf24' : '#f59e0b',
            colorError: dark ? '#f87171' : '#ef4444',

            colorText: dark ? '#e5f7ee' : '#101828',
            colorTextSecondary: dark ? '#a7b9b0' : '#667085',
            colorTextTertiary: dark ? '#81958b' : '#98a2b3',

            colorBorder: dark ? '#294238' : '#e4e7ec',
            colorBorderSecondary: dark ? '#20372e' : '#f0f0f0',

            colorBgBase: dark ? '#0b1714' : '#f5f8f6',
            colorBgContainer: dark ? '#14251f' : '#ffffff',
            colorBgElevated: dark ? '#1a3028' : '#ffffff',
            colorBgLayout: dark ? '#0b1714' : '#f5f8f6',

            borderRadius: 10,
            borderRadiusLG: 14,
            controlHeight: 40,
            fontFamily: 'inherit',
            fontSize: 14,

            boxShadowSecondary: dark
                ? '0 4px 20px rgba(0, 0, 0, 0.22)'
                : '0 4px 16px rgba(16, 24, 40, 0.05)',
        },

        components: {
            Card: {
                headerFontSize: 15,
                headerFontSizeSM: 14,
                bodyPadding: 20,
                bodyPaddingSM: 16,
                colorBgContainer: dark ? '#14251f' : '#ffffff',
            },

            Button: {
                borderRadius: 8,
                controlHeight: 40,
                fontWeight: 600,
            },

            Table: {
                headerBg: dark ? '#1a3028' : '#f9fafb',
                headerColor: dark ? '#d5e8df' : '#475467',
                rowHoverBg: dark ? '#1c382d' : '#f0faf5',
                borderColor: dark ? '#294238' : '#e4e7ec',
            },

            Statistic: {
                titleFontSize: 13,
                contentFontSize: 26,
            },

            Tag: {
                borderRadiusSM: 6,
            },
        },
    };
}
