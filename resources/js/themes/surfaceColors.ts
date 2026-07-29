import colors from 'tailwindcss/colors';

export interface SurfaceOption {
    name: string;
    palette: Record<string, string>;
}

export const surfaceColors: SurfaceOption[] = [
    { name: 'slate', palette: colors.slate },
    { name: 'zinc', palette: colors.zinc },
    { name: 'stone', palette: colors.stone },
    { name: 'gray', palette: colors.gray },
    { name: 'neutral', palette: colors.neutral },
];
