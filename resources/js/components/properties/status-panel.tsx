import { Form } from '@inertiajs/react';
import { ExternalLink, FileText } from 'lucide-react';
import FileField from '@/components/file-field';
import InputError from '@/components/input-error';
import OwnerInspectionCard from '@/components/inspections/owner-inspection-card';
import ProgressSteps from '@/components/progress-steps';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { archive, pause, publish } from '@/routes/properties';
import { request as requestInspection } from '@/routes/properties/inspection';
import { store as storeProof } from '@/routes/properties/ownership-proof';
import type {
    OwnerInspection,
    PropertyDetail,
    PublicationRequirement,
} from '@/types';

type Can = {
    update: boolean;
    publish: boolean;
    archive: boolean;
    requestInspection: boolean;
};
type Proof = { name: string; url: string; uploaded_at: string | null } | null;

/**
 * The one main action for where the property is in its life cycle, shown in the page header.
 */
export function NextStepAction({
    property,
    can,
}: {
    property: PropertyDetail;
    can: Can;
}) {
    const { t } = useTranslation();
    const status = property.status;

    if (
        can.requestInspection &&
        (status === 'draft' || status === 'rejected')
    ) {
        return (
            <StatusAction
                form={requestInspection.form(property.id)}
                label={t('Request inspection')}
            />
        );
    }

    if (can.publish && (status === 'approved' || status === 'paused')) {
        return (
            <StatusAction
                form={publish.form(property.id)}
                label={t('Publish')}
            />
        );
    }

    if (can.publish && status === 'published') {
        return (
            <StatusAction
                form={pause.form(property.id)}
                label={t('Pause')}
                variant="outline"
            />
        );
    }

    return null;
}

export default function StatusPanel({
    property,
    publication,
    ownershipProof,
    can,
    inspection,
}: {
    property: PropertyDetail;
    publication: PublicationRequirement[];
    ownershipProof: Proof;
    can: Can;
    inspection: OwnerInspection | null;
}) {
    const { t } = useTranslation();
    const canArchive =
        can.archive &&
        ['draft', 'approved', 'paused', 'rejected'].includes(property.status);

    return (
        <section className="space-y-8">
            <header className="space-y-1">
                <h2 className="text-lg font-semibold">{t('Publication')}</h2>
                <p className="text-sm text-muted-foreground">
                    {t(statusHelp[property.status])}
                </p>
            </header>

            {inspection && <OwnerInspectionCard inspection={inspection} />}

            <ProgressSteps title={t('Before publishing')} steps={publication} />
            {property.inspection_valid_until && (
                <p className="text-sm text-muted-foreground">
                    {t('Inspection valid until :date.', {
                        date: property.inspection_valid_until,
                    })}
                </p>
            )}

            <div className="space-y-3">
                <div className="space-y-1">
                    <h3 className="text-sm font-semibold">
                        {t('Proof of ownership')}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        {t(
                            'Deed, property tax receipt or a letter from the owner authorizing you to rent. Required to request the inspection.',
                        )}
                    </p>
                </div>
                {ownershipProof && (
                    <a
                        href={ownershipProof.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-3 rounded-xl border p-3 text-sm hover:bg-muted"
                    >
                        <FileText
                            className="size-4 text-muted-foreground"
                            aria-hidden
                        />
                        <span className="min-w-0 flex-1 truncate">
                            {ownershipProof.name}
                        </span>
                        <span className="text-muted-foreground">
                            {ownershipProof.uploaded_at}
                        </span>
                        <ExternalLink
                            className="size-3.5 text-muted-foreground"
                            aria-hidden
                        />
                    </a>
                )}
                {can.update && property.editable && (
                    <Form
                        {...storeProof.form(property.id)}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="space-y-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <FileField
                                    name="document"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    required
                                    hint={t('JPG, PNG or PDF, up to 10 MB.')}
                                    label={
                                        ownershipProof
                                            ? t(
                                                  'Choose a new file to replace it',
                                              )
                                            : undefined
                                    }
                                />
                                <InputError message={errors.document} />
                                <Button variant="outline" disabled={processing}>
                                    {ownershipProof
                                        ? t('Replace')
                                        : t('Attach')}
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </div>

            {canArchive && (
                <div className="space-y-2 border-t pt-6">
                    <h3 className="text-sm font-semibold">
                        {t('Archive property')}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        {t(
                            'The property leaves your list and can no longer be edited. Its history is kept.',
                        )}
                    </p>
                    <StatusAction
                        form={archive.form(property.id)}
                        label={t('Archive')}
                        variant="outline"
                        className="text-destructive"
                        confirm={t(
                            'Archive this property? It will no longer be editable.',
                        )}
                    />
                </div>
            )}
        </section>
    );
}

function StatusAction({
    form,
    label,
    variant = 'default',
    confirm,
    className,
}: {
    form: { action: string; method: 'post' | 'get' };
    label: string;
    variant?: 'default' | 'outline' | 'ghost';
    confirm?: string;
    className?: string;
}) {
    return (
        <Form
            {...form}
            options={{ preserveScroll: true }}
            onBefore={() => (confirm ? window.confirm(confirm) : true)}
        >
            {({ processing, errors }) => (
                <div className="space-y-1">
                    <Button
                        variant={variant}
                        disabled={processing}
                        className={className}
                    >
                        {label}
                    </Button>
                    <InputError
                        message={errors.domain ?? errors.status}
                        className="max-w-sm"
                    />
                </div>
            )}
        </Form>
    );
}

const statusHelp: Record<PropertyDetail['status'], string> = {
    draft: 'Complete the information, attach the proof of ownership and request the inspection.',
    pending_inspection:
        'An inspector will visit the property. You can keep editing photos and descriptions.',
    approved: 'The inspection was approved. Publish it once it has prices.',
    rejected:
        'The inspection found problems. Fix them and request a new inspection.',
    published: 'The property appears in the catalog.',
    paused: 'The property is hidden from the catalog.',
    archived: 'The property is archived and can no longer be edited.',
};
