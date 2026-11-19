import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import VerificationStatusBadge from '@/components/verification-status-badge';
import { useTranslation } from '@/lib/i18n';
import { index } from '@/routes/users';
import { update as updateRoles } from '@/routes/users/roles';
import {
    destroy as reactivate,
    store as suspend,
} from '@/routes/users/suspension';
import type { Option, VerificationStatus } from '@/types';

type Props = {
    user: {
        id: number;
        name: string;
        email: string;
        email_verified: boolean;
        birth_date: string | null;
        created_at: string | null;
        roles: string[];
        role_values: string[];
        suspended_at: string | null;
        suspension_reason: string | null;
        verification_profiles: {
            id: number;
            kind: string;
            status: VerificationStatus;
            status_label: string;
            institution: string | null;
            verified_until: string | null;
        }[];
    };
    internalRoleOptions: Option[];
    can: { assignRoles: boolean; suspend: boolean };
};

export default function UserShow({ user, internalRoleOptions, can }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={user.name} />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title={user.name} description={user.email} />
                    <div className="flex flex-wrap gap-1">
                        {user.suspended_at && (
                            <Badge variant="destructive">
                                {t('Suspended')}
                            </Badge>
                        )}
                        {user.roles.map((role) => (
                            <Badge key={role} variant="outline">
                                {role}
                            </Badge>
                        ))}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Account')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm">
                        <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2">
                            <dt className="text-muted-foreground">
                                {t('Email verified')}
                            </dt>
                            <dd>{user.email_verified ? t('Yes') : t('No')}</dd>
                            <dt className="text-muted-foreground">
                                {t('Birth date')}
                            </dt>
                            <dd>{user.birth_date ?? '—'}</dd>
                            <dt className="text-muted-foreground">
                                {t('Member since')}
                            </dt>
                            <dd>{user.created_at}</dd>
                        </dl>

                        {user.verification_profiles.length > 0 && (
                            <ul className="divide-y rounded-md border">
                                {user.verification_profiles.map((profile) => (
                                    <li
                                        key={profile.id}
                                        className="flex flex-wrap items-center justify-between gap-2 px-3 py-2"
                                    >
                                        <span>
                                            {profile.kind}
                                            {profile.institution &&
                                                ` · ${profile.institution}`}
                                            {profile.verified_until &&
                                                ` · ${t('until :date', { date: profile.verified_until })}`}
                                        </span>
                                        <VerificationStatusBadge
                                            status={profile.status}
                                            label={profile.status_label}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                {can.assignRoles && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Internal roles')}</CardTitle>
                            <CardDescription>
                                {t(
                                    'Client and owner profiles are chosen by the user and are not changed here.',
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...updateRoles.form(user.id)}
                                options={{ preserveScroll: true }}
                                transform={(data) => ({
                                    roles: data.roles ?? [],
                                })}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="space-y-3">
                                            {internalRoleOptions.map(
                                                (option) => (
                                                    <div
                                                        key={option.value}
                                                        className="flex items-center gap-3"
                                                    >
                                                        <Checkbox
                                                            id={`role-${option.value}`}
                                                            name="roles[]"
                                                            value={option.value}
                                                            defaultChecked={user.role_values.includes(
                                                                option.value,
                                                            )}
                                                        />
                                                        <Label
                                                            htmlFor={`role-${option.value}`}
                                                        >
                                                            {option.label}
                                                        </Label>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                        <InputError message={errors.roles} />
                                        <Button disabled={processing}>
                                            {processing && <Spinner />}
                                            {t('Save roles')}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {can.suspend && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Suspension')}</CardTitle>
                            <CardDescription>
                                {user.suspended_at
                                    ? t('Suspended on :date. Reason: :reason', {
                                          date: user.suspended_at,
                                          reason: user.suspension_reason ?? '',
                                      })
                                    : t(
                                          'A suspended user is signed out and cannot sign in. Their active rentals are not changed.',
                                      )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {user.suspended_at ? (
                                <Form
                                    {...reactivate.form(user.id)}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing, errors }) => (
                                        <div className="space-y-2">
                                            <InputError
                                                message={errors.domain}
                                            />
                                            <Button
                                                variant="outline"
                                                disabled={processing}
                                            >
                                                {t('Reactivate account')}
                                            </Button>
                                        </div>
                                    )}
                                </Form>
                            ) : (
                                <Form
                                    {...suspend.form(user.id)}
                                    options={{ preserveScroll: true }}
                                    className="space-y-2"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <Label htmlFor="reason">
                                                {t('Reason')}
                                            </Label>
                                            <Textarea
                                                id="reason"
                                                name="reason"
                                                required
                                            />
                                            <InputError
                                                message={errors.reason}
                                            />
                                            <Button
                                                variant="destructive"
                                                disabled={processing}
                                            >
                                                {t('Suspend account')}
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

UserShow.layout = {
    breadcrumbs: [{ title: 'Users', href: index() }],
};
