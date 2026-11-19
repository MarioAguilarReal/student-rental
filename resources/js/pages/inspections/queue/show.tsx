import { Form, Head } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import EvidencePhotos from '@/components/inspections/evidence-photos';
import InspectionStatusBadge from '@/components/inspections/inspection-status-badge';
import InputError from '@/components/input-error';
import Page, { PageSection } from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import { assign, index } from '@/routes/inspections/queue';
import type { InspectionDetail, Option } from '@/types';

type Props = {
    inspection: InspectionDetail;
    inspectors: Option<number>[];
    canAssign: boolean;
};

export default function InspectionQueueShow({
    inspection,
    inspectors,
    canAssign,
}: Props) {
    const { t } = useTranslation();
    const today = new Date().toISOString().slice(0, 10);
    const answeredSections = inspection.sections.filter((section) =>
        section.items.some((item) => item.answer),
    );

    return (
        <>
            <Head title={inspection.property.title} />
            <Page
                title={inspection.property.title}
                back={{ href: index(), label: t('Inspections') }}
                meta={
                    <InspectionStatusBadge
                        status={inspection.status}
                        statusLabel={inspection.status_label}
                        result={inspection.result}
                        resultLabel={inspection.result_label}
                    />
                }
                description={`${inspection.type_label} · ${inspection.address}`}
            >
                <div className="space-y-10">
                    <div className="grid gap-6 md:grid-cols-2">
                        <dl className="grid grid-cols-[auto_1fr] content-start gap-x-6 gap-y-2 rounded-xl border p-4 text-sm">
                            <dt className="text-muted-foreground">
                                {t('Owner')}
                            </dt>
                            <dd>
                                {inspection.property.owner.name} ·{' '}
                                {inspection.property.owner.email}
                            </dd>
                            <dt className="text-muted-foreground">
                                {t('Inspector')}
                            </dt>
                            <dd>
                                {inspection.inspector?.name ?? t('Unassigned')}
                            </dd>
                            <dt className="text-muted-foreground">
                                {t('Date')}
                            </dt>
                            <dd className="tabular">
                                {inspection.scheduled_for ?? '—'}
                            </dd>
                            <dt className="text-muted-foreground">
                                {t('Checklist')}
                            </dt>
                            <dd>
                                {t('Version :version', {
                                    version: inspection.template_version,
                                })}
                            </dd>
                            {inspection.ownership_proof && (
                                <>
                                    <dt className="text-muted-foreground">
                                        {t('Proof of ownership')}
                                    </dt>
                                    <dd>
                                        <a
                                            href={
                                                inspection.ownership_proof.url
                                            }
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1 underline underline-offset-4"
                                        >
                                            {inspection.ownership_proof.name}{' '}
                                            <ExternalLink className="size-3" />
                                        </a>
                                    </dd>
                                </>
                            )}
                        </dl>

                        {canAssign && (
                            <Form
                                {...assign.form(inspection.id)}
                                options={{ preserveScroll: true }}
                                className="space-y-4 rounded-xl bg-muted p-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <h2 className="font-semibold">
                                            {inspection.inspector
                                                ? t('Reassign')
                                                : t('Assign inspector')}
                                        </h2>
                                        {inspectors.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                {t(
                                                    'There are no active inspectors. A Super Admin can grant the role in Users.',
                                                )}
                                            </p>
                                        ) : (
                                            <>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="inspector_id">
                                                        {t('Inspector')}
                                                    </Label>
                                                    <NativeSelect
                                                        id="inspector_id"
                                                        name="inspector_id"
                                                        defaultValue={
                                                            inspection.inspector
                                                                ?.id ?? ''
                                                        }
                                                        required
                                                    >
                                                        <option
                                                            value=""
                                                            disabled
                                                        >
                                                            {t('Select')}
                                                        </option>
                                                        {inspectors.map(
                                                            (inspector) => (
                                                                <option
                                                                    key={
                                                                        inspector.value
                                                                    }
                                                                    value={
                                                                        inspector.value
                                                                    }
                                                                >
                                                                    {
                                                                        inspector.label
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </NativeSelect>
                                                    <InputError
                                                        message={
                                                            errors.inspector_id
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="scheduled_for">
                                                        {t('Date')}
                                                    </Label>
                                                    <Input
                                                        id="scheduled_for"
                                                        name="scheduled_for"
                                                        type="date"
                                                        min={today}
                                                        defaultValue={
                                                            inspection.scheduled_for ??
                                                            ''
                                                        }
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.scheduled_for ??
                                                            errors.status
                                                        }
                                                    />
                                                </div>
                                                <Button disabled={processing}>
                                                    {inspection.inspector
                                                        ? t('Reassign')
                                                        : t('Assign')}
                                                </Button>
                                            </>
                                        )}
                                    </>
                                )}
                            </Form>
                        )}
                    </div>

                    {inspection.observations && (
                        <PageSection title={t('Observations')}>
                            <p className="rounded-xl bg-muted p-4 text-sm whitespace-pre-line">
                                {inspection.observations}
                            </p>
                        </PageSection>
                    )}

                    {answeredSections.map((section) => (
                        <PageSection
                            key={section.key}
                            title={section.title ?? t('Shared areas')}
                        >
                            <ul className="divide-y rounded-xl border">
                                {section.items.map((item) => (
                                    <li
                                        key={item.id}
                                        className="space-y-2 px-4 py-3"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <p className="text-sm">
                                                {item.text}
                                            </p>
                                            {item.answer ? (
                                                <Badge
                                                    variant={
                                                        item.answer.result ===
                                                        'compliant'
                                                            ? 'positive'
                                                            : item.answer
                                                                    .result ===
                                                                'non_compliant'
                                                              ? 'negative'
                                                              : 'neutral'
                                                    }
                                                >
                                                    {t(
                                                        answerLabels[
                                                            item.answer.result
                                                        ],
                                                    )}
                                                </Badge>
                                            ) : (
                                                <Badge variant="neutral">
                                                    {t('Unanswered')}
                                                </Badge>
                                            )}
                                        </div>
                                        {item.answer?.comment && (
                                            <p className="text-sm text-muted-foreground">
                                                {item.answer.comment}
                                            </p>
                                        )}
                                        <EvidencePhotos
                                            photos={item.answer?.photos ?? []}
                                        />
                                    </li>
                                ))}
                            </ul>
                        </PageSection>
                    ))}
                </div>
            </Page>
        </>
    );
}

const answerLabels = {
    compliant: 'Complies',
    non_compliant: 'Does not comply',
    not_applicable: 'Not applicable',
} as const;

InspectionQueueShow.layout = {
    breadcrumbs: [{ title: 'Inspections', href: index() }],
};
