import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

const Noir = definePreset(Aura, {
    typography: {
        lineHeight: '1.5',
        fontFamily: 'inherit',
        fontWeight: 'normal',
        fontSize: '0.875rem',
    },
    formField: {
        fontWeight: '{typography.font.weight}',
        fontSize: '{typography.font.size}',
    },
    semantic: {
        primary: {
            50: '{indigo.50}',
            100: '{indigo.100}',
            200: '{indigo.200}',
            300: '{indigo.300}',
            400: '{indigo.400}',
            500: '{indigo.500}',
            600: '{indigo.600}',
            700: '{indigo.700}',
            800: '{indigo.800}',
            900: '{indigo.900}',
            950: '{indigo.950}',
        },
        colorScheme: {
            light: {
                primary: {
                    color: '{indigo.500}',
                    contrastColor: '#ffffff',
                    hoverColor: '{indigo.600}',
                    activeColor: '{indigo.700}',
                },
                surface: {
                    0: '#ffffff',
                    50: '{zinc.50}',
                    100: '{zinc.100}',
                    200: '{zinc.200}',
                    300: '{zinc.300}',
                    400: '{zinc.400}',
                    500: '{zinc.500}',
                    600: '{zinc.600}',
                    700: '{zinc.700}',
                    800: '{zinc.800}',
                    900: '{zinc.900}',
                    950: '{zinc.950}',
                },
            },
            dark: {
                primary: {
                    color: '{zinc.50}',
                    contrastColor: '{zinc.950}',
                    hoverColor: '{zinc.100}',
                    activeColor: '{zinc.200}',
                },
                surface: {
                    0: '#ffffff',
                    50: '{slate.50}',
                    100: '{slate.100}',
                    200: '{slate.200}',
                    300: '{slate.300}',
                    400: '{slate.400}',
                    500: '{slate.500}',
                    600: '{slate.600}',
                    700: '{slate.700}',
                    800: '{slate.800}',
                    900: '{slate.900}',
                    950: '{slate.950}',
                },
            },
        },
    },

    // Component-level tokens: point at plain CSS vars so a simple
    // setProperty() call (no updatePreset() needed) can adjust these live.
    components: {
        button: {
            root: {
                fontSize: 'var(--app-font-size, {form.field.font.size})',
                borderRadius: 'var(--app-border-radius, {border.radius.md})',
                label: {
                    fontWeight: 'var(--app-font-weight, {form.field.font.weight})',
                },
            },
        },
        card: {
            root: {
                borderRadius: 'var(--app-border-radius, {border.radius.md})',
                background: 'var(--card)'
            },
            title: {
                fontSize: 'var(--app-font-size, {form.field.font.size})',
                fontWeight: 'var(--app-font-weight, {form.field.font.weight})',
            },
            subtitle: {
                fontSize: 'var(--app-font-size, {form.field.font.size})',
                fontWeight: 'var(--app-font-weight, {form.field.font.weight})',
            },
        },
        inputtext: {
            root: {
                borderRadius: 'var(--app-border-radius, {border.radius.md})',
                fontSize: 'var(--app-font-size, {form.field.font.size})',
            },
        },
    },
});

export default Noir;