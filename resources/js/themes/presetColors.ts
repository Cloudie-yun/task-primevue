import colors from 'tailwindcss/colors';

export interface ColorOption {
    name: string;
    palette: Record<string, string> | Record<string, never>;
}

// Custom brand palette — not from Tailwind's stock colors.
// A cobalt/azure hue tuned for a distinct, less "generic SaaS blue" feel.
const brand: Record<string, string> = {
    50: '#f2f1fd',
    100: '#e6e4fa',
    200: '#cfccf5',
    300: '#aea8ec',
    400: '#8a80e0',
    500: '#6c5fd3',
    600: '#5847bd',
    700: '#48389a',
    800: '#3c2f7c',
    900: '#332964',
    950: '#1f1840',
};

export const presetColors: ColorOption[] = [
    { name: 'noir', palette: {} },
    { name: 'brand', palette: brand },
    { name: 'emerald', palette: colors.emerald },
    { name: 'green', palette: colors.green },
    { name: 'lime', palette: colors.lime },
    { name: 'orange', palette: colors.orange },
    { name: 'amber', palette: colors.amber },
    { name: 'yellow', palette: colors.yellow },
    { name: 'teal', palette: colors.teal },
    { name: 'cyan', palette: colors.cyan },
    { name: 'sky', palette: colors.sky },
    { name: 'blue', palette: colors.blue },
    { name: 'indigo', palette: colors.indigo },
    { name: 'violet', palette: colors.violet },
    { name: 'purple', palette: colors.purple },
    { name: 'fuchsia', palette: colors.fuchsia },
    { name: 'pink', palette: colors.pink },
    { name: 'rose', palette: colors.rose },
];