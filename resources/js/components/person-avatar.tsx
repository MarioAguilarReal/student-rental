import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

// Pale identity tints, at least 60° apart. Identity only — never data colours.
const TINTS = [
    'bg-[#e3f0f3] text-[#134e5c]',
    'bg-[#efe6f7] text-[#4a2a66]',
    'bg-[#fde9d7] text-[#7a3d0c]',
    'bg-[#e4f4e4] text-[#1f5a2a]',
    'bg-[#fbe4ea] text-[#7a1f3a]',
    'bg-[#e6ecfb] text-[#253f7a]',
];

/**
 * A person shown as themselves: stable initials and a stable pale tint from their id.
 */
export default function PersonAvatar({
    id,
    name,
    size = 'md',
}: {
    id: number;
    name: string;
    size?: 'sm' | 'md';
}) {
    const getInitials = useInitials();

    return (
        <span
            aria-hidden
            className={cn(
                'inline-flex shrink-0 items-center justify-center rounded-full font-medium',
                TINTS[id % TINTS.length],
                size === 'sm' ? 'size-7 text-xs' : 'size-9 text-sm',
            )}
        >
            {getInitials(name)}
        </span>
    );
}
