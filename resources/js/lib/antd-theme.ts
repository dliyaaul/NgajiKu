
import type { ThemeConfig } from 'antd';

export const ngajikuAntdTheme: ThemeConfig = {
    token: {
        colorPrimary: '#047857',
        colorInfo: '#047857',
        colorSuccess: '#039855',
        colorWarning: '#dc6803',
        colorError: '#d92d20',

        colorText: '#101828',
        colorTextSecondary: '#667085',
        colorBorder: '#e4e7ec',
        colorBgContainer: '#ffffff',
        colorBgLayout: '#f7f9f8',

        borderRadius: 10,
        borderRadiusLG: 14,
        controlHeight: 40,
        fontFamily: 'inherit',
        fontSize: 14,
        boxShadowSecondary: '0 4px 16px rgba(16, 24, 40, 0.05)',
    },

    components: {
        Card: {
            headerFontSize: 15,
            headerFontSizeSM: 14,
            bodyPadding: 20,
            bodyPaddingSM: 16,
        },
        Button: {
            borderRadius: 8,
            controlHeight: 40,
            fontWeight: 600,
        },
        Table: {
            headerBg: '#f9fafb',
            headerColor: '#475467',
            rowHoverBg: '#f3fbf7',
            borderColor: '#e4e7ec',
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
