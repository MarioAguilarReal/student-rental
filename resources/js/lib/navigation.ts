import {
    BadgeCheck,
    Banknote,
    Building2,
    CreditCard,
    Landmark,
    Percent,
    CalendarDays,
    ClipboardList,
    ListTodo,
    Home,
    ListChecks,
    Sofa,
    ClipboardCheck,
    KeyRound,
    Luggage,
    LayoutGrid,
    Users,
} from 'lucide-react';
import { useCan } from '@/lib/permissions';
import { dashboard } from '@/routes';
import { index as amenitiesIndex } from '@/routes/catalog/amenities';
import { index as houseRulesIndex } from '@/routes/catalog/house-rules';
import { index as institutionsIndex } from '@/routes/catalog/institutions';
import { index as checklistsIndex } from '@/routes/checklists';
import { index as commissionRules } from '@/routes/commission-rules';
import { edit as paymentSimulation } from '@/routes/payment-simulation';
import { balance, finance } from '@/routes/payments';
import { agenda as inspectionAgenda } from '@/routes/inspections';
import { index as inspectionQueue } from '@/routes/inspections/queue';
import { index as myProperties } from '@/routes/properties';
import { index as myRentals, manage as manageRentals } from '@/routes/rentals';
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
        title: 'My rentals',
        href: myRentals(),
        icon: Luggage,
        permissions: ['rentals.request'],
    },
    {
        title: 'My properties',
        href: myProperties(),
        icon: Home,
        permissions: ['properties.create'],
    },
    {
        title: 'Rentals',
        href: manageRentals(),
        icon: KeyRound,
        permissions: ['properties.create', 'properties.view_any'],
    },
    {
        title: 'Income',
        href: balance(),
        icon: Banknote,
        permissions: ['properties.create'],
    },
    {
        title: 'My inspections',
        href: inspectionAgenda(),
        icon: CalendarDays,
        permissions: ['inspections.perform'],
    },
];

const adminItems: GuardedNavItem[] = [
    {
        title: 'Finance',
        href: finance(),
        icon: Landmark,
        permissions: ['properties.view_any'],
    },
    {
        title: 'Properties',
        href: allProperties(),
        icon: Home,
        permissions: ['properties.view_any'],
    },
    {
        title: 'Inspections',
        href: inspectionQueue(),
        icon: ClipboardList,
        permissions: ['inspections.assign'],
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
    {
        title: 'Inspection checklist',
        href: checklistsIndex(),
        icon: ListTodo,
        permissions: ['catalog.manage'],
    },
    {
        title: 'Commissions',
        href: commissionRules(),
        icon: Percent,
        permissions: ['payments.commission_rules.manage'],
    },
    {
        title: 'Test payments',
        href: paymentSimulation(),
        icon: CreditCard,
        permissions: ['payments.simulation.configure'],
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
