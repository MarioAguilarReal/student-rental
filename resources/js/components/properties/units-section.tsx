import { Form, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import ReinspectionNotice from '@/components/properties/reinspection-notice';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { destroy, store, template, update } from '@/routes/properties/units';
import type { PropertyDetail, PropertyEditorOptions, Unit } from '@/types';

type Props = {
    property: PropertyDetail;
    units: Unit[];
    options: PropertyEditorOptions;
    disabled: boolean;
};

type Editing = { unit: Unit | null; parent: Unit | null } | null;

export default function UnitsSection({
    property,
    units,
    options,
    disabled,
}: Props) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState<Editing>(null);
    const childTypes = (unit: Unit) =>
        options.unitTypes.find((type) => type.value === unit.type)?.children ??
        [];

    const remove = (unit: Unit) => {
        if (
            window.confirm(
                t('Remove :name and its subdivisions?', { name: unit.name }),
            )
        ) {
            router.delete(
                destroy.url({ property: property.id, unit: unit.id }),
                { preserveScroll: true },
            );
        }
    };

    const renderTree = (parentId: number | null, depth: number) =>
        units
            .filter((unit) => unit.parent_id === parentId)
            .map((unit) => (
                <li key={unit.id}>
                    <div
                        className="flex flex-wrap items-center justify-between gap-2 py-2"
                        style={{ paddingLeft: `${depth * 1.5}rem` }}
                    >
                        <div className="min-w-0">
                            <p className="font-medium">{unit.name}</p>
                            <p className="text-xs text-muted-foreground">
                                {unit.type_label} ·{' '}
                                {t(':count people', { count: unit.capacity })} ·{' '}
                                {unit.effective_gender_policy}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-1">
                            <Badge
                                variant={
                                    unit.is_rentable ? 'positive' : 'neutral'
                                }
                            >
                                {unit.is_rentable
                                    ? t('Rentable')
                                    : t('Not rentable')}
                            </Badge>
                            {!disabled && (
                                <>
                                    {childTypes(unit).length > 0 && (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                setEditing({
                                                    unit: null,
                                                    parent: unit,
                                                })
                                            }
                                        >
                                            <Plus /> {t('Subdivide')}
                                        </Button>
                                    )}
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        aria-label={t('Edit :name', {
                                            name: unit.name,
                                        })}
                                        onClick={() =>
                                            setEditing({
                                                unit,
                                                parent:
                                                    units.find(
                                                        (item) =>
                                                            item.id ===
                                                            unit.parent_id,
                                                    ) ?? null,
                                            })
                                        }
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        aria-label={t('Remove :name', {
                                            name: unit.name,
                                        })}
                                        onClick={() => remove(unit)}
                                    >
                                        <Trash2 />
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                    <ul>{renderTree(unit.id, depth + 1)}</ul>
                </li>
            ));

    return (
        <section className="space-y-6">
            <header className="space-y-1">
                <h2 className="text-lg font-semibold">{t('Spaces')}</h2>
                <p className="text-sm text-muted-foreground">
                    {t(
                        'Decide how it is rented: the whole place, by rooms or by beds in a shared room. Renting a space blocks its parent and its subdivisions for the same dates.',
                    )}
                </p>
            </header>
            <div className="space-y-4">
                <ReinspectionNotice
                    show={property.passed_inspection && !disabled}
                />

                {units.length === 0 ? (
                    !disabled && (
                        <TemplateForm
                            propertyId={property.id}
                            options={options}
                        />
                    )
                ) : (
                    <ul className="divide-y rounded-xl border px-3">
                        {renderTree(null, 0)}
                    </ul>
                )}

                {!disabled && units.length > 0 && (
                    <Button
                        variant="outline"
                        onClick={() => setEditing({ unit: null, parent: null })}
                    >
                        <Plus /> {t('Add space')}
                    </Button>
                )}

                {editing && (
                    <UnitDialog
                        key={
                            editing.unit?.id ??
                            `new-${editing.parent?.id ?? 'root'}`
                        }
                        propertyId={property.id}
                        editing={editing}
                        options={options}
                        onClose={() => setEditing(null)}
                    />
                )}
            </div>
        </section>
    );
}

function TemplateForm({
    propertyId,
    options,
}: {
    propertyId: number;
    options: PropertyEditorOptions;
}) {
    const { t } = useTranslation();
    const [choice, setChoice] = useState('entire_place');

    return (
        <Form
            {...template.form(propertyId)}
            options={{ preserveScroll: true }}
            className="space-y-4 rounded-md border border-dashed p-4"
        >
            {({ processing, errors }) => (
                <>
                    <p className="text-sm font-medium">
                        {t('How do you want to rent it?')}
                    </p>
                    <div className="grid gap-2 sm:grid-cols-3">
                        {options.templates.map((option) => (
                            <label
                                key={option.value}
                                className="cursor-pointer rounded-md border p-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent"
                            >
                                <input
                                    type="radio"
                                    name="template"
                                    value={option.value}
                                    checked={choice === option.value}
                                    onChange={() => setChoice(option.value)}
                                    className="sr-only"
                                />
                                {option.label}
                            </label>
                        ))}
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {choice !== 'entire_place' ? (
                            <div className="grid gap-2">
                                <Label htmlFor="rooms">
                                    {t('Number of rooms')}
                                </Label>
                                <Input
                                    id="rooms"
                                    name="rooms"
                                    type="number"
                                    min={1}
                                    max={20}
                                    defaultValue={3}
                                />
                                <InputError message={errors.rooms} />
                            </div>
                        ) : (
                            <input type="hidden" name="rooms" value="0" />
                        )}
                        <div className="grid gap-2">
                            <Label htmlFor="template_capacity">
                                {t('Total capacity (people)')}
                            </Label>
                            <Input
                                id="template_capacity"
                                name="capacity"
                                type="number"
                                min={1}
                                max={50}
                                defaultValue={3}
                            />
                            <InputError
                                message={errors.capacity ?? errors.template}
                            />
                        </div>
                    </div>
                    <Button disabled={processing}>{t('Create spaces')}</Button>
                </>
            )}
        </Form>
    );
}

function UnitDialog({
    propertyId,
    editing,
    options,
    onClose,
}: {
    propertyId: number;
    editing: NonNullable<Editing>;
    options: PropertyEditorOptions;
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const { unit, parent } = editing;
    const allowedTypes = options.unitTypes.filter((type) =>
        parent
            ? options.unitTypes
                  .find((item) => item.value === parent.type)
                  ?.children.includes(type.value)
            : type.can_be_root,
    );

    const form = useForm({
        parent_id: parent?.id ?? null,
        type: unit?.type ?? allowedTypes[0]?.value ?? '',
        name: unit?.name ?? '',
        description: unit?.description ?? '',
        capacity: unit?.capacity ?? 1,
        is_rentable: unit?.is_rentable ?? true,
        gender_policy: unit?.gender_policy ?? '',
        inherit_communities:
            unit?.allowed_communities === null || unit === null,
        allowed_communities: unit?.allowed_communities ?? [],
        amenity_ids: unit?.amenity_ids ?? [],
    });

    const toggle = <T,>(list: T[], value: T) =>
        list.includes(value)
            ? list.filter((item) => item !== value)
            : [...list, value];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const done = { preserveScroll: true, onSuccess: onClose };

        if (unit) {
            form.put(update.url({ property: propertyId, unit: unit.id }), done);
        } else {
            form.post(store.url(propertyId), done);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90svh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {unit
                            ? t('Edit :name', { name: unit.name })
                            : t('New space')}
                    </DialogTitle>
                    {parent && (
                        <DialogDescription>
                            {t('Inside :name', { name: parent.name })}
                        </DialogDescription>
                    )}
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <InputError message={form.errors.parent_id} />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="unit_type">{t('Type')}</Label>
                            <NativeSelect
                                id="unit_type"
                                value={form.data.type}
                                onChange={(event) =>
                                    form.setData('type', event.target.value)
                                }
                            >
                                {(unit
                                    ? options.unitTypes.filter(
                                          (type) =>
                                              type.value === unit.type ||
                                              allowedTypes.includes(type),
                                      )
                                    : allowedTypes
                                ).map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {type.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={form.errors.type} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="unit_capacity">
                                {t('Capacity (people)')}
                            </Label>
                            <Input
                                id="unit_capacity"
                                type="number"
                                min={1}
                                max={50}
                                value={form.data.capacity}
                                onChange={(event) =>
                                    form.setData(
                                        'capacity',
                                        Number(event.target.value),
                                    )
                                }
                            />
                            <InputError message={form.errors.capacity} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="unit_name">{t('Name')}</Label>
                        <Input
                            id="unit_name"
                            required
                            placeholder={t('e.g. Room 2, Bed A')}
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="unit_description">
                            {t('Description')}
                        </Label>
                        <Textarea
                            id="unit_description"
                            rows={3}
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.is_rentable}
                            onCheckedChange={(checked) =>
                                form.setData('is_rentable', checked === true)
                            }
                        />
                        {t('Can be rented on its own')}
                    </label>
                    <div className="grid gap-2">
                        <Label htmlFor="unit_gender">
                            {t('Who can live here')}
                        </Label>
                        <NativeSelect
                            id="unit_gender"
                            value={form.data.gender_policy}
                            onChange={(event) =>
                                form.setData(
                                    'gender_policy',
                                    event.target.value,
                                )
                            }
                        >
                            <option value="">
                                {t('Same as the space it belongs to')}
                            </option>
                            {options.genderPolicies.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={form.errors.gender_policy} />
                    </div>
                    <fieldset className="space-y-2">
                        <legend className="text-sm font-medium">
                            {t('Communities admitted')}
                        </legend>
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={form.data.inherit_communities}
                                onCheckedChange={(checked) =>
                                    form.setData(
                                        'inherit_communities',
                                        checked === true,
                                    )
                                }
                            />
                            {t('Same as the space it belongs to')}
                        </label>
                        {!form.data.inherit_communities &&
                            options.communities.map((option) => (
                                <label
                                    key={option.value}
                                    className="flex items-center gap-2 pl-6 text-sm"
                                >
                                    <Checkbox
                                        checked={form.data.allowed_communities.includes(
                                            option.value,
                                        )}
                                        onCheckedChange={() =>
                                            form.setData(
                                                'allowed_communities',
                                                toggle(
                                                    form.data
                                                        .allowed_communities,
                                                    option.value,
                                                ),
                                            )
                                        }
                                    />
                                    {option.label}
                                </label>
                            ))}
                        <InputError message={form.errors.allowed_communities} />
                    </fieldset>
                    <fieldset className="space-y-2">
                        <legend className="text-sm font-medium">
                            {t('Own amenities')}
                        </legend>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {options.unitAmenities.map((amenity) => (
                                <label
                                    key={amenity.value}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        checked={form.data.amenity_ids.includes(
                                            amenity.value,
                                        )}
                                        onCheckedChange={() =>
                                            form.setData(
                                                'amenity_ids',
                                                toggle(
                                                    form.data.amenity_ids,
                                                    amenity.value,
                                                ),
                                            )
                                        }
                                    />
                                    {amenity.label}
                                </label>
                            ))}
                        </div>
                        <InputError message={form.errors.amenity_ids} />
                    </fieldset>
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="ghost" onClick={onClose}>
                            {t('Cancel')}
                        </Button>
                        <Button disabled={form.processing}>{t('Save')}</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
