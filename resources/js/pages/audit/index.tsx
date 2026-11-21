import { Form, Head, Link } from '@inertiajs/react';
import { ScrollText } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import InputError from '@/components/input-error';
import Page from '@/components/page';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import { index } from '@/routes/audit';
import type { Option, Paginated } from '@/types';

type AuditRecord = {
    id: number;
    log: string;
    log_label: string;
    event: string | null;
    description: string;
    subject: string | null;
    actor: string | null;
    properties: Record<string, unknown>;
    created_at: string | null;
};

type Filters = { log: string; actor: string; from: string; to: string };

function formatDateTime(iso: string | null): string {
    return iso
        ? new Intl.DateTimeFormat('es-MX', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(iso))
        : '';
}

/**
 * HU-11.01 · RN-90: who changed what and when. Read only.
 */
export default function AuditLogIndex({
    records,
    filters,
    logs,
}: {
    records: Paginated<AuditRecord>;
    filters: Filters;
    logs: Option[];
}) {
    const { t } = useTranslation();
    const filtered = Object.values(filters).some((value) => value !== '');

    return (
        <>
            <Head title={t('Audit log')} />
            <Page
                title={t('Audit log')}
                description={t(
                    'Who changed what and when. Records cannot be edited or deleted.',
                )}
                width="wide"
            >
                <div className="space-y-6">
                    <Form
                        {...index.form()}
                        options={{ preserveState: true, replace: true }}
                        transform={(data) =>
                            Object.fromEntries(
                                Object.entries(data).filter(
                                    ([, value]) => value !== '',
                                ),
                            )
                        }
                        className="grid items-end gap-3 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-[12rem_1fr_10rem_10rem_auto]"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="audit-log">
                                        {t('Area')}
                                    </Label>
                                    <NativeSelect
                                        id="audit-log"
                                        name="log"
                                        defaultValue={filters.log}
                                    >
                                        <option value="">{t('All')}</option>
                                        {logs.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="audit-actor">
                                        {t('Who')}
                                    </Label>
                                    <Input
                                        id="audit-actor"
                                        name="actor"
                                        defaultValue={filters.actor}
                                        placeholder={t('Name or email')}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="audit-from">
                                        {t('From')}
                                    </Label>
                                    <Input
                                        id="audit-from"
                                        name="from"
                                        type="date"
                                        defaultValue={filters.from}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="audit-to">{t('To')}</Label>
                                    <Input
                                        id="audit-to"
                                        name="to"
                                        type="date"
                                        defaultValue={filters.to}
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <Button disabled={processing}>
                                        {t('Filter')}
                                    </Button>
                                    {filtered && (
                                        <Button variant="ghost" asChild>
                                            <Link href={index()}>
                                                {t('Clear')}
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                                <InputError
                                    className="sm:col-span-full"
                                    message={
                                        errors.to ?? errors.from ?? errors.log
                                    }
                                />
                            </>
                        )}
                    </Form>

                    {records.data.length === 0 ? (
                        <EmptyState
                            icon={ScrollText}
                            title={
                                filtered
                                    ? t('Nothing matches these filters')
                                    : t('No records yet')
                            }
                        />
                    ) : (
                        <ul className="divide-y rounded-xl border">
                            {records.data.map((record) => (
                                <li
                                    key={record.id}
                                    className="px-4 py-3 text-sm"
                                >
                                    <details className="group">
                                        <summary className="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-1 rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none">
                                            <span className="tabular w-36 shrink-0 text-muted-foreground">
                                                {formatDateTime(
                                                    record.created_at,
                                                )}
                                            </span>
                                            <Badge variant="neutral">
                                                {record.log_label}
                                            </Badge>
                                            <span className="min-w-0 flex-1 font-medium">
                                                {record.description}
                                                {record.subject && (
                                                    <span className="font-normal text-muted-foreground">
                                                        {' · '}
                                                        {record.subject}
                                                    </span>
                                                )}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {record.actor ?? t('System')}
                                            </span>
                                        </summary>
                                        {Object.keys(record.properties).length >
                                            0 && (
                                            <pre className="mt-3 overflow-x-auto rounded-lg bg-muted p-3 text-xs">
                                                {JSON.stringify(
                                                    record.properties,
                                                    null,
                                                    2,
                                                )}
                                            </pre>
                                        )}
                                    </details>
                                </li>
                            ))}
                        </ul>
                    )}
                    <Pagination page={records} />
                </div>
            </Page>
        </>
    );
}

AuditLogIndex.layout = {
    breadcrumbs: [{ title: 'Audit log', href: index() }],
};
