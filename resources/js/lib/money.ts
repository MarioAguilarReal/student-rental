/**
 * Money arrives from the backend in minor units (cents) and is shown in the user's locale.
 */
export function formatMoney(
    minor: number,
    currency = 'MXN',
    locale = 'es-MX',
): string {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        maximumFractionDigits: minor % 100 === 0 ? 0 : 2,
    }).format(minor / 100);
}

/** Cents → the plain number shown in a peso input ("4500" or "4500.50"). */
export function toInputAmount(minor: number | null | undefined): string {
    if (minor === null || minor === undefined) {
        return '';
    }

    return minor % 100 === 0 ? String(minor / 100) : (minor / 100).toFixed(2);
}

/** A peso input → cents (rounded), or 0 when empty or invalid. */
export function toMinor(amount: string): number {
    const value = Number.parseFloat(amount.replace(',', '.'));

    return Number.isFinite(value) ? Math.round(value * 100) : 0;
}

/** Commission in basis points (1000 = 10 %) taken from a price, rounded half up like Money::percentage. */
export function netAfterCommission(minor: number, basisPoints: number): number {
    return minor - Math.round((minor * basisPoints) / 10000);
}
