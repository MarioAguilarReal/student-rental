/**
 * The current value of a design token (resources/css/app.css), for libraries that need a colour
 * string, such as Leaflet's circles. Follows the active theme.
 */
export function cssVar(name: string, fallback = '#00786f'): string {
    if (typeof window === 'undefined') {
        return fallback;
    }

    const value = getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim();

    return value || fallback;
}
