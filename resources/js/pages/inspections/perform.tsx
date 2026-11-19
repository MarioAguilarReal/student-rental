import { Form, Head, router } from '@inertiajs/react';
import {
    Camera,
    ExternalLink,
    FileText,
    Mail,
    MapPin,
    TriangleAlert,
} from 'lucide-react';
import { useState } from 'react';
import EvidencePhotos from '@/components/inspections/evidence-photos';
import InspectionStatusBadge from '@/components/inspections/inspection-status-badge';
import InputError from '@/components/input-error';
import Page, { PageSection } from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { agenda, complete, start } from '@/routes/inspections';
import { store as storeAnswer } from '@/routes/inspections/answers';
import type {
    AnswerResult,
    ChecklistSectionItem,
    InspectionDetail,
    Option,
} from '@/types';

type Props = {
    inspection: InspectionDetail;
    answerOptions: Option<AnswerResult>[];
    resultOptions: Option[];
};

/**
 * HU-06.02 to HU-06.04: the inspector's checklist, made for a phone in the field.
 * Every answer saves itself; the verdict closes the inspection.
 */
export default function InspectionPerform({
    inspection,
    answerOptions,
    resultOptions,
}: Props) {
    const { t } = useTranslation();
    const inProgress = inspection.status === 'in_progress';
    const { answered, total } = inspection.progress;

    return (
        <>
            <Head title={inspection.property.title} />
            <Page
                width="narrow"
                title={inspection.property.title}
                back={{ href: agenda(), label: t('My inspections') }}
                meta={
                    <InspectionStatusBadge
                        status={inspection.status}
                        statusLabel={inspection.status_label}
                        result={inspection.result}
                        resultLabel={inspection.result_label}
                    />
                }
                description={`${inspection.type_label} · ${inspection.scheduled_for ?? ''}`}
            >
                <div className="space-y-10">
                    <PageSection title={t('Visit')}>
                        <ul className="divide-y rounded-xl border text-sm">
                            <li className="flex items-start gap-3 px-4 py-3">
                                <MapPin
                                    className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    aria-hidden
                                />
                                <span className="flex-1">
                                    {inspection.address}
                                </span>
                                {inspection.map_url && (
                                    <a
                                        href={inspection.map_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex shrink-0 items-center gap-1 underline underline-offset-4"
                                    >
                                        {t('Map')}{' '}
                                        <ExternalLink className="size-3" />
                                    </a>
                                )}
                            </li>
                            <li className="flex items-center gap-3 px-4 py-3">
                                <Mail
                                    className="size-4 shrink-0 text-muted-foreground"
                                    aria-hidden
                                />
                                <span className="flex-1">
                                    {inspection.property.owner.name}
                                </span>
                                <a
                                    href={`mailto:${inspection.property.owner.email}`}
                                    className="underline underline-offset-4"
                                >
                                    {inspection.property.owner.email}
                                </a>
                            </li>
                            {inspection.ownership_proof && (
                                <li className="flex items-center gap-3 px-4 py-3">
                                    <FileText
                                        className="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden
                                    />
                                    <span className="flex-1">
                                        {t('Proof of ownership')}
                                    </span>
                                    <a
                                        href={inspection.ownership_proof.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1 underline underline-offset-4"
                                    >
                                        {t('Open')}{' '}
                                        <ExternalLink className="size-3" />
                                    </a>
                                </li>
                            )}
                        </ul>
                    </PageSection>

                    {inspection.status === 'assigned' && (
                        <div className="rounded-xl bg-muted p-6 text-center">
                            <p className="mb-4 text-sm text-muted-foreground">
                                {t(
                                    'Start when you arrive. Your answers are saved as you go.',
                                )}
                            </p>
                            <Form
                                {...start.form(inspection.id)}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <Button size="lg" disabled={processing}>
                                            {t('Start inspection')}
                                        </Button>
                                        <InputError
                                            message={
                                                errors.domain ?? errors.status
                                            }
                                            className="mt-2"
                                        />
                                    </>
                                )}
                            </Form>
                        </div>
                    )}

                    {(inProgress || inspection.status === 'completed') && (
                        <>
                            {inProgress && (
                                <div className="sticky top-0 z-10 -mx-4 bg-card px-4 py-3 md:-mx-0 md:px-0">
                                    <div className="mb-1.5 flex justify-between text-sm">
                                        <span className="font-medium">
                                            {t('Checklist v:version', {
                                                version:
                                                    inspection.template_version,
                                            })}
                                        </span>
                                        <span className="tabular text-muted-foreground">
                                            {t(':done of :total', {
                                                done: answered,
                                                total,
                                            })}
                                        </span>
                                    </div>
                                    <div
                                        className="h-1.5 overflow-hidden rounded-full bg-muted"
                                        role="progressbar"
                                        aria-valuemin={0}
                                        aria-valuemax={total}
                                        aria-valuenow={answered}
                                    >
                                        <div
                                            className="h-full rounded-full bg-brand transition-[width] duration-300"
                                            style={{
                                                width: `${(answered / Math.max(total, 1)) * 100}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            )}

                            {inspection.sections.map((section) => (
                                <PageSection
                                    key={section.key}
                                    title={section.title ?? t('Shared areas')}
                                >
                                    <ul className="space-y-3">
                                        {section.items.map((item) => (
                                            <ChecklistItemRow
                                                key={`${section.key}-${item.id}`}
                                                inspectionId={inspection.id}
                                                unitId={section.unit_id}
                                                item={item}
                                                options={answerOptions}
                                                editable={inProgress}
                                            />
                                        ))}
                                    </ul>
                                </PageSection>
                            ))}

                            {inProgress && (
                                <VerdictForm
                                    inspection={inspection}
                                    resultOptions={resultOptions}
                                />
                            )}

                            {inspection.status === 'completed' &&
                                inspection.observations && (
                                    <PageSection title={t('Observations')}>
                                        <p className="rounded-xl bg-muted p-4 text-sm whitespace-pre-line">
                                            {inspection.observations}
                                        </p>
                                    </PageSection>
                                )}
                        </>
                    )}
                </div>
            </Page>
        </>
    );
}

function ChecklistItemRow({
    inspectionId,
    unitId,
    item,
    options,
    editable,
}: {
    inspectionId: number;
    unitId: number | null;
    item: ChecklistSectionItem;
    options: Option<AnswerResult>[];
    editable: boolean;
}) {
    const { t } = useTranslation();
    const [comment, setComment] = useState(item.answer?.comment ?? '');
    const [saving, setSaving] = useState(false);
    const result = item.answer?.result ?? null;
    const missingPhoto =
        item.requires_photo &&
        result !== null &&
        result !== 'not_applicable' &&
        (item.answer?.photos.length ?? 0) === 0;

    const save = (changes: { result?: AnswerResult; photos?: File[] }) => {
        const nextResult = changes.result ?? result;

        if (!nextResult) {
            return;
        }

        setSaving(true);
        router.post(
            storeAnswer.url(inspectionId),
            {
                checklist_item_id: item.id,
                unit_id: unitId,
                result: nextResult,
                comment: comment || null,
                photos: changes.photos ?? [],
            },
            {
                preserveScroll: true,
                forceFormData: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    const tone: Record<AnswerResult, string> = {
        compliant: 'border-positive bg-positive-soft text-positive-ink',
        non_compliant: 'border-negative bg-negative-soft text-negative-ink',
        not_applicable: 'border-foreground bg-secondary',
    };

    return (
        <li
            className={cn(
                'space-y-3 rounded-xl border p-4',
                result === null && editable && 'border-dashed',
            )}
        >
            <div className="space-y-1">
                <p className="font-medium">{item.text}</p>
                <div className="flex flex-wrap gap-1">
                    <Badge variant="neutral">{item.category}</Badge>
                    {item.is_critical && (
                        <Badge variant="negative">{t('Critical')}</Badge>
                    )}
                    {item.requires_photo && (
                        <Badge variant="neutral">{t('Photo required')}</Badge>
                    )}
                </div>
            </div>

            <div
                role="radiogroup"
                aria-label={item.text}
                className="grid grid-cols-3 gap-2"
            >
                {options.map((option) => (
                    <button
                        key={option.value}
                        type="button"
                        role="radio"
                        aria-checked={result === option.value}
                        disabled={!editable || saving}
                        onClick={() => save({ result: option.value })}
                        className={cn(
                            'min-h-11 rounded-lg border px-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none active:scale-[0.98] disabled:cursor-default',
                            result === option.value
                                ? tone[option.value]
                                : 'border-input bg-card hover:bg-muted',
                        )}
                    >
                        {option.label}
                    </button>
                ))}
            </div>

            {(editable || item.answer?.comment) && (
                <Textarea
                    aria-label={t('Comment on: :item', { item: item.text })}
                    placeholder={t(
                        'Comment (what you saw, what must be fixed)',
                    )}
                    rows={2}
                    value={comment}
                    readOnly={!editable}
                    onChange={(event) => setComment(event.target.value)}
                    onBlur={() =>
                        editable &&
                        comment !== (item.answer?.comment ?? '') &&
                        save({})
                    }
                />
            )}

            <EvidencePhotos photos={item.answer?.photos ?? []} />

            {editable && result !== null && (
                <label className="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-input px-3 text-sm focus-within:ring-2 focus-within:ring-ring hover:bg-muted">
                    <Camera className="size-4" aria-hidden />
                    {t('Add photos')}
                    <input
                        type="file"
                        accept="image/*"
                        capture="environment"
                        multiple
                        className="sr-only"
                        onChange={(event) => {
                            const files = Array.from(event.target.files ?? []);
                            event.target.value = '';

                            if (files.length) {
                                save({ photos: files });
                            }
                        }}
                    />
                </label>
            )}
            {missingPhoto && editable && (
                <p className="text-sm text-negative">
                    {t('This item needs at least one photo.')}
                </p>
            )}
        </li>
    );
}

function VerdictForm({
    inspection,
    resultOptions,
}: {
    inspection: InspectionDetail;
    resultOptions: Option[];
}) {
    const { t } = useTranslation();
    const forced = inspection.has_critical_failure;
    const [result, setResult] = useState(forced ? 'rejected' : 'approved');
    const chosen = forced ? 'rejected' : result;

    return (
        <PageSection
            title={t('Verdict')}
            description={t(
                'The verdict is final and the owner is notified right away.',
            )}
        >
            <Form
                {...complete.form(inspection.id)}
                transform={(data) => ({ ...data, result: chosen })}
                className="space-y-4"
            >
                {({ processing, errors }) => (
                    <>
                        {forced && (
                            <p className="flex items-start gap-2 rounded-xl bg-negative-soft p-3 text-sm text-negative-ink">
                                <TriangleAlert
                                    className="mt-0.5 size-4 shrink-0"
                                    aria-hidden
                                />
                                {t(
                                    'A critical item does not comply, so the inspection is rejected.',
                                )}
                            </p>
                        )}
                        <div
                            role="radiogroup"
                            aria-label={t('Result')}
                            className="grid gap-2 sm:grid-cols-3"
                        >
                            {resultOptions.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    role="radio"
                                    aria-checked={chosen === option.value}
                                    disabled={
                                        forced && option.value !== 'rejected'
                                    }
                                    onClick={() => setResult(option.value)}
                                    className={cn(
                                        'min-h-11 rounded-lg border px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:opacity-40',
                                        chosen === option.value
                                            ? 'border-foreground bg-foreground text-background'
                                            : 'border-input hover:bg-muted',
                                    )}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="observations">
                                {t('Observations for the owner')}
                            </Label>
                            <Textarea
                                id="observations"
                                name="observations"
                                rows={4}
                                required={chosen !== 'approved'}
                                placeholder={t(
                                    'What was found and what must be fixed before the next visit.',
                                )}
                            />
                            <InputError message={errors.observations} />
                        </div>
                        <InputError
                            message={
                                errors.result ?? errors.domain ?? errors.status
                            }
                        />
                        <Button
                            size="lg"
                            disabled={processing}
                            className="w-full sm:w-auto"
                        >
                            {t('Issue verdict')}
                        </Button>
                    </>
                )}
            </Form>
        </PageSection>
    );
}

InspectionPerform.layout = {
    breadcrumbs: [{ title: 'My inspections', href: agenda() }],
};
