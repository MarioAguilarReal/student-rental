import type { SVGAttributes } from 'react';

/**
 * Product mark: a roof over a door — a home you can enter. Drawn in currentColor so it
 * sits on the brand tile in the sidebar or alone on the auth screens.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <path
                d="M3.5 11.25 12 4l8.5 7.25"
                stroke="currentColor"
                strokeWidth="2.2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M6.5 10v9.25h11V10"
                stroke="currentColor"
                strokeWidth="2.2"
                strokeLinejoin="round"
            />
            <path
                d="M10.25 19.25v-4.5a1.75 1.75 0 0 1 3.5 0v4.5"
                fill="currentColor"
            />
        </svg>
    );
}
