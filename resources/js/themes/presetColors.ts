import colors from 'tailwindcss/colors';

export interface ColorOption {
    name: string;
    palette: Record<string, string> | Record<string, never>;
}

export const presetColors: ColorOption[] = [
    { name: 'noir', palette: {} },
    { name: 'emerald', palette: colors.emerald },
    { name: 'green', palette: colors.green },
    { name: 'lime', palette: colors.lime },
    { name: 'orange', palette: colors.orange },
    { name: 'amber', palette: colors.amber },
    { name: 'yellow', palette: colors.yellow },
    { name: 'teal', palette: colors.teal },
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