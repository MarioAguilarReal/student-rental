import { Form, Head, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import Page, { PageSection } from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import {
    destroy as destroyItem,
    store as storeItem,
    update as updateItem,
} from '@/routes/checklists/items';
import { draft as createDraft, index, publish } from '@/routes/checklists';
import type { Option } from '@/types';

type Item = {
    id: number;
    category: string;
    category_label: string;
    text: string;
    scope: string;
    scope_label: string;
    is_critical: boolean;
    requires_photo: boolean;
};
type Template = {
    id: number;
    name: string;
    version: number;
    status: string;
    status_label: string;
    published_at: string | null;
    items: Item[];
};

type Props = {
    current: Template | null;
    draft: Template | null;
    history: { id: number; version: number; published_at: string | null }[];
    categories: Option[];
    scopes: Option[];
};

/**
 * HU-03.03 · RN-82: the version in force is read-only; changes go into a draft that is published as a new version.
 */
export default function Checklists({
    current,
    draft,
    history,
    categories,
    scopes,
}: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Inspection checklist')} />
            <Page
                title={t('Inspection checklist')}
                description={t(
                    'What inspectors check. Inspections keep the version they started with.',
                )}
                actions={
                    !draft && (
                        <Button
                            onClick={() =>
                                router.post(
                                    createDraft.url(),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            {t('Create new version')}
                        </Button>
                    )
                }
            >
                <div className="space-y-12">
                    {draft && (
                        <PageSection
                            title={t('Draft · version :version', {
                                version: draft.version,
                            })}
                            description={t(
                                'Edit the items and publish when ready. Critical items reject the inspection when they fail.',
                            )}
                            actions={
                                <Button
                                    onClick={() =>
                                        window.confirm(
                                            t(
                                                'Publish version :version? New inspections will use it.',
                                                { version: draft.version },
                                            ),
                                        ) &&
                                        router.post(
                                            publish.url(draft.id),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {t('Publish version :version', {
                                        version: draft.version,
                                    })}
                                </Button>
                            }
                        >
                            <ul className="space-y-2">
                                {draft.items.map((item) => (
                                    <li
                                        key={item.id}
                                        className="rounded-xl border p-3"
                                    >
                                        <ItemForm
                                            templateId={draft.id}
                                            item={item}
                                            categories={categories}
                                            scopes={scopes}
                                        />
                                    </li>
                                ))}
                                <li className="rounded-xl bg-muted p-3">
                                    <ItemForm
                                        templateId={draft.id}
                                        categories={categories}
                                        scopes={scopes}
                                    />
                                </li>
                            </ul>
                        </PageSection>
                    )}

                    {current && (
                        <PageSection
                            title={t('In force · version :version', {
                                version: current.version,
                            })}
                            description={t('Published on :date.', {
                                date: current.published_at ?? '',
                            })}
                        >
                            <ul className="divide-y rounded-xl border">
                                {current.items.map((item) => (
                                    <li
                                        key={item.id}
                                        className="flex flex-wrap items-center gap-2 px-4 py-3"
                                    >
                                        <p className="min-w-0 flex-1 text-sm">
                                            {item.text}
                                        </p>
                                        <Badge variant="neutral">
                                            {item.category_label}
                                        </Badge>
                                        <Badge variant="neutral">
                                            {item.scope_label}
                                        </Badge>
                                        {item.is_critical && (
                                            <Badge variant="negative">
                                                {t('Critical')}
                                            </Badge>
                                        )}
                                        {item.requires_photo && (
                                            <Badge variant="neutral">
                                                {t('Photo required')}
                                            </Badge>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </PageSection>
                    )}

                    {history.length > 0 && (
                        <p className="text-sm text-muted-foreground">
                            {t('Previous versions: :list', {
                                list: history
                                    .map(
                                        (version) =>
                                            `v${version.version} (${version.published_at?.slice(0, 10) ?? ''})`,
                                    )
                                    .join(', '),
                            })}
                        </p>
                    )}
                </div>
            </Page>
        </>
    );
}

function ItemForm({
    templateId,
    item,
    categories,
    scopes,
}: {
    templateId: number;
    item?: Item;
    categories: Option[];
    scopes: Option[];
}) {
    const { t } = useTranslation();
    const args = item ? { template: templateId, item: item.id } : null;

    return (
        <Form
            {...(args ? updateItem.form(args) : storeItem.form(templateId))}
            options={{ preserveScroll: true }}
            resetOnSuccess={!item}
            transform={(data) => ({
                ...data,
                is_critical: data.is_critical === 'on',
                requires_photo: data.requires_photo === 'on',
            })}
            className="grid items-start gap-2 lg:grid-cols-[1fr_9rem_9rem_auto_auto_auto_auto]"
        >
            {({ processing, errors }) => (
                <>
                    <div>
                        <Input
                            name="text"
                            required
                            defaultValue={item?.text}
                            aria-label={t('Item')}
                            placeholder={t('What the inspector checks')}
                        />
                        <InputError message={errors.text ?? errors.domain} />
                    </div>
                    <NativeSelect
                        name="category"
                        defaultValue={item?.category}
                        aria-label={t('Category')}
                    >
                        {categories.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <NativeSelect
                        name="scope"
                        defaultValue={item?.scope ?? 'property'}
                        aria-label={t('Where it applies')}
                    >
                        {scopes.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <label className="flex h-10 items-center gap-2 text-sm">
                        <Checkbox
                            name="is_critical"
                            defaultChecked={item?.is_critical}
                        />
                        {t('Critical')}
                    </label>
                    <label className="flex h-10 items-center gap-2 text-sm">
                        <Checkbox
                            name="requires_photo"
                            defaultChecked={item?.requires_photo}
                        />
                        {t('Photo')}
                    </label>
                    <Button
                        variant={item ? 'outline' : 'default'}
                        disabled={processing}
                    >
                        {item ? t('Save') : t('Add')}
                    </Button>
                    {args ? (
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            aria-label={t('Remove item')}
                            onClick={() =>
                                router.delete(destroyItem.url(args), {
                                    preserveScroll: true,
                                })
                            }
                        >
                            <Trash2 />
                        </Button>
                    ) : (
                        <span />
                    )}
                </>
            )}
        </Form>
    );
}

Checklists.layout = {
    breadcrumbs: [{ title: 'Inspection checklist', href: index() }],
};
