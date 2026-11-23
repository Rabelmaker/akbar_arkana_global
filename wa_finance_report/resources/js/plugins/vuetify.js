import { h } from 'vue';
import { createVuetify } from 'vuetify';
import 'vuetify/styles';

const themes = {
    light: {
        dark: false,
        colors: {
            primary: '#666CFF',
            'primary-darken-1': '#5C61E6',
            secondary: '#6D788D',
            success: '#72E128',
            info: '#26C6F9',
            warning: '#FDB528',
            error: '#FF4D49',
            background: '#F7F7F9',
            surface: '#FFFFFF',
            'on-background': '#262B43',
            'on-surface': '#262B43',
        },
    },
    dark: {
        dark: true,
        colors: {
            primary: '#666CFF',
            'primary-darken-1': '#5C61E6',
            secondary: '#6D788D',
            success: '#72E128',
            info: '#26C6F9',
            warning: '#FDB528',
            error: '#FF4D49',
            background: '#282A42',
            surface: '#30334E',
            'on-background': '#EAEAFF',
            'on-surface': '#EAEAFF',
        },
    },
};

const iconify = {
    component: (props) => h(props.tag, {
        ...props,
        class: [props.icon],
        tag: undefined,
        icon: undefined,
    }),
};

export default createVuetify({
    defaults: {
        VBtn: { rounded: 'lg' },
        VCard: { rounded: 'lg' },
        VTextField: { variant: 'outlined', density: 'comfortable' },
        VSelect: { variant: 'outlined', density: 'comfortable' },
    },
    icons: {
        defaultSet: 'iconify',
        aliases: {
            close: 'ri-close-line',
            menu: 'ri-menu-line',
            dropdown: 'ri-arrow-down-s-line',
            success: 'ri-checkbox-circle-line',
            error: 'ri-error-warning-line',
        },
        sets: { iconify },
    },
    theme: {
        defaultTheme: 'light',
        themes,
    },
});
