import EvidencePhotos from '@/components/inspections/evidence-photos';
import InspectionStatusBadge from '@/components/inspections/inspection-status-badge';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/lib/i18n';
import type { OwnerInspection } from '@/types';

/**
 * HU-06.05: what the owner needs to know about their latest inspection.
 */
export default function OwnerInspectionCard({
    inspection,
}: {
    inspection: OwnerInspection;
}) {
    const { t } = useTranslation();

    const headline = {
        pending_assignment: t(
            'We are scheduling the visit. You will receive the date by email.',
        ),
        assigned: t(':inspector will visit on :date.', {
            inspector: inspection.inspector?.name ?? '',
            date: inspection.scheduled_for ?? '',
        }),
        in_progress: t('The inspection is in progress.'),
        completed:
            inspection.result === 'rejected'
                ? t(
                      'The property did not pass. Fix what is listed below and request a new inspection.',
                  )
                : t('Valid until :date.', {
                      date: inspection.valid_until ?? '',
                  }),
        cancelled: t('This inspection was cancelled.'),
    }[inspection.status];

    return (
        <div className="space-y-4 rounded-xl border p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h3 className="text-sm font-semibold">
                    {inspection.type_label}
                </h3>
                <InspectionStatusBadge
                    status={inspection.status}
                    statusLabel={inspection.status_label}
                    result={inspection.result}
                    resultLabel={inspection.result_label}
                />
            </div>
            <p className="text-sm">{headline}</p>

            {inspection.observations && (
                <div className="space-y-1">
                    <p className="text-sm font-medium">{t('Observations')}</p>
                    <p className="rounded-lg bg-muted p-3 text-sm whitespace-pre-line">
                        {inspection.observations}
                    </p>
                </div>
            )}

            {inspection.failed_items.length > 0 && (
                <div className="space-y-2">
                    <p className="text-sm font-medium">{t('Did not comply')}</p>
                    <ul className="divide-y rounded-lg border">
                        {inspection.failed_items.map((item) => (
                            <li key={item.id} className="space-y-2 px-3 py-3">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <p className="text-sm">
                                        {item.text}
                                        {item.unit && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                · {item.unit}
                                            </span>
                                        )}
                                    </p>
                                    {item.is_critical && (
                                        <Badge variant="negative">
                                            {t('Critical')}
                                        </Badge>
                                    )}
                                </div>
                                {item.comment && (
                                    <p className="text-sm text-muted-foreground">
                                        {item.comment}
                                    </p>
                                )}
                                <EvidencePhotos photos={item.photos} />
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
