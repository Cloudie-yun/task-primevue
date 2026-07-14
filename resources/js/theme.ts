import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

const Noir = definePreset(Aura, {
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

        // Governs InputText, Password, Textarea, Select, DatePicker, etc. all at once —
        // no need to configure each input component's tokens individually.
        formField: {
            paddingX: '0.75rem',
            paddingY: '0.625rem',
            borderRadius: '{border.radius.md}',
            focusRing: {
                width: '0',
                style: 'none',
                color: 'transparent',
                offset: '0',
                shadow: 'none',
            },
            transitionDuration: '{transition.duration}',
        },

        colorScheme: {
            light: {
                primary: {
                    color: '{indigo.500}',
                    inverseColor: '#ffffff',
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
                formField: {
                    background: '{surface.0}',
                    disabledBackground: '{surface.100}',
                    filledBackground: '{surface.50}',
                    filledHoverBackground: '{surface.50}',
                    filledFocusBackground: '{surface.50}',
                    borderColor: '{surface.300}',
                    hoverBorderColor: '{surface.400}',
                    focusBorderColor: '{primary.color}',
                    invalidBorderColor: '{red.400}',
                    color: '{surface.700}',
                    disabledColor: '{surface.400}',
                    placeholderColor: '{surface.400}',
                    invalidPlaceholderColor: '{red.600}',
                    floatLabelColor: '{surface.400}',
                    floatLabelFocusColor: '{primary.color}',
                    floatLabelActiveColor: '{surface.400}',
                    floatLabelInvalidColor: '{red.400}',
                    iconColor: '{surface.400}',
                    shadow: '0 1px 2px 0 rgba(18, 18, 23, 0.05)',
                },
            },
            dark: {
                primary: {
                    color: '{zinc.50}',
                    inverseColor: '{zinc.950}',
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
                formField: {
                    background: '{surface.950}',
                    disabledBackground: '{surface.700}',
                    filledBackground: '{surface.800}',
                    filledHoverBackground: '{surface.800}',
                    filledFocusBackground: '{surface.800}',
                    borderColor: '{surface.600}',
                    hoverBorderColor: '{surface.500}',
                    focusBorderColor: '{primary.color}',
                    invalidBorderColor: '{red.900}',
                    color: '{surface.0}',
                    disabledColor: '{surface.400}',
                    placeholderColor: '{surface.400}',
                    invalidPlaceholderColor: '{red.900}',
                    floatLabelColor: '{surface.400}',
                    floatLabelFocusColor: '{primary.color}',
                    floatLabelActiveColor: '{surface.400}',
                    floatLabelInvalidColor: '{red.900}',
                    iconColor: '{surface.400}',
                    shadow: '0 1px 2px 0 rgba(0, 0, 0, 0.3)',
                },
            },
        },
    },

    // Optional — only needed if you want Button to diverge from semantic.primary.
    // By default Button already reads button.background -> primary.color automatically.
    components: {
        button: {
            colorScheme: {
                light: {
                    root: {
                        primary: {
                            background: '{primary.color}',
                            hoverBackground: '{primary.hoverColor}',
                            activeBackground: '{primary.activeColor}',
                            borderColor: '{primary.color}',
                            hoverBorderColor: '{primary.hoverColor}',
                            activeBorderColor: '{primary.activeColor}',
                            color: '{primary.inverseColor}',
                            hoverColor: '{primary.inverseColor}',
                            activeColor: '{primary.inverseColor}',
                        },
                    },
                },
                dark: {
                    root: {
                        primary: {
                            background: '{primary.color}',
                            hoverBackground: '{primary.hoverColor}',
                            activeBackground: '{primary.activeColor}',
                            borderColor: '{primary.color}',
                            hoverBorderColor: '{primary.hoverColor}',
                            activeBorderColor: '{primary.activeColor}',
                            color: '{primary.inverseColor}',
                            hoverColor: '{primary.inverseColor}',
                            activeColor: '{primary.inverseColor}',
                        },
                    },
                },
            },
        },
    },
});

export default Noir;