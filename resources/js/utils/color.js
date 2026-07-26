// Decide se um texto deve ser claro ou escuro sobre uma cor de fundo
// escolhida livremente pelo usuário (ex.: cor da matéria/etiqueta) — sem
// isto, uma cor clara (amarelo pastel, por exemplo) produzia texto branco
// sobre fundo claro, ilegível.
export function getReadableTextColor(hexColor, { light = '#ffffff', dark = '#1f2937' } = {}) {
    if (!hexColor) return dark;

    const hex = hexColor.replace('#', '');
    if (![3, 6].includes(hex.length)) return dark;

    const full = hex.length === 3
        ? hex.split('').map((c) => c + c).join('')
        : hex;

    const r = parseInt(full.slice(0, 2), 16);
    const g = parseInt(full.slice(2, 4), 16);
    const b = parseInt(full.slice(4, 6), 16);

    if ([r, g, b].some((n) => Number.isNaN(n))) return dark;

    // Luminância relativa (WCAG), simplificada sem a correção gamma completa
    // — suficiente para decidir entre duas opções de texto, não para medir
    // a razão de contraste exata.
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    return luminance > 0.6 ? dark : light;
}
