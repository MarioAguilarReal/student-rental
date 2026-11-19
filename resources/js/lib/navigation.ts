import {
    BadgeCheck,
    Building2,
    Home,
    ListChecks,
    Sofa,
    ClipboardCheck,
    LayoutGrid,
    Users,
} from 'lucide-react';
import { useCan } from '@/lib/permissions';
import { dashboard } from '@/routes';
import { index as amenitiesIndex } from '@/routes/catalog/amenities';
import { index as houseRulesIndex } from '@/routes/catalog/house-rules';
import { index as institutionsIndex } from '@/routes/catalog/institutions';
import { index as myProperties } from '@/routes/properties';
import { index as allProperties } from '@/routes/properties/admin';
import { index as usersIndex } from '@/routes/users';
import { show as verificationShow } from '@/routes/verifications';
import { index as reviewsIndex } from '@/routes/verifications/reviews';
import type { NavItem } from '@/types';

type GuardedNavItem = NavItem & { permissions?: string[] };

const mainItems: GuardedNavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    {
        title: 'My verification',
        href: verificationShow(),
        icon: BadgeCheck,
        permissions: ['verification.documents.upload'],
    },
    {
        title: 'My properties',
        href: myProperties(),
        icon: Home,
        permissions: ['properties.create'],
    },
];

const adminItems: GuardedNavItem[] = [
    {
        title: 'Properties',
        href: allProperties(),
        icon: Home,
        permissions: ['properties.view_any'],
    },
    {
        title: 'Verifications',
        href: reviewsIndex(),
        icon: ClipboardCheck,
        permissions: ['verification.documents.review'],
    },
    {
        title: 'Users',
        href: usersIndex(),
        icon: Users,
        permissions: ['users.suspend', 'users.roles.assign'],
    },
    {
        title: 'Institutions',
        href: institutionsIndex(),
        icon: Building2,
        permissions: ['catalog.manage'],
    },
    {
        title: 'Amenities',
        href: amenitiesIndex(),
        icon: Sofa,
        permissions: ['catalog.manage'],
    },
    {
        title: 'House rules',
        href: houseRulesIndex(),
        icon: ListChecks,
        permissions: ['catalog.manage'],
    },
];

/**
 * Menu items the current user may see. Titles are English keys, translated when rendered.
 */
export function useNavItems(): { main: NavItem[]; admin: NavItem[] } {
    const can = useCan();
    const visible = (items: GuardedNavItem[]) =>
        items.filter((item) => !item.permissions || item.permissions.some(can));

    return { main: visible(mainItems), admin: visible(adminItems) };
}
