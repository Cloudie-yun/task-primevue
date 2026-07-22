function toSlugUpper(value: string): string {
    return value
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '') // strip accents, mirrors Str::slug's ASCII behavior
        .replace(/[^a-zA-Z0-9]+/g, '')
        .toUpperCase();
}

export function buildSku(productName: string, values: { value: string; variant_type_id: number }[]): string {
    const base = productName
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase())
        .join('');

    const valuePart = [...values]
        .sort((a, b) => a.variant_type_id - b.variant_type_id)
        .map((v) => toSlugUpper(v.value))
        .join('-');

    return `${base}-${valuePart}`;
}