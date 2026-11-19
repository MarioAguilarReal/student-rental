import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormActions from '@/components/form-actions';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import { update } from '@/routes/properties/features';
import type { PropertyDetail, PropertyEditorOptions } from '@/types';

type RuleChoice = 'unset' | 'allowed' | 'forbidden';

export default function FeaturesForm({
    property,
    options,
    disabled,
}: {
    property: PropertyDetail;
    options: PropertyEditorOptions;
    disabled: boolean;
}) {
    const { t } = useTranslation();
    const initialRules = Object.fromEntries(
        options.houseRules.map((rule) => {
            const current = property.house_rules.find(
                (item) => item.id === rule.id,
            );

            return [
                rule.id,
                {
                    choice: (current
                        ? current.is_allowed
                            ? 'allowed'
                            : 'forbidden'
                        : 'unset') as RuleChoice,
                    note: current?.note ?? '',
                },
            ];
        }),
    );

    const form = useForm({
        amenity_ids: property.amenity_ids,
        rules: initialRules,
        gender_policy: property.gender_policy,
        allowed_communities: property.allowed_communities,
    });

    const toggle = <T,>(list: T[], value: T) =>
        list.includes(value)
            ? list.filter((item) => item !== value)
            : [...list, value];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            amenity_ids: data.amenity_ids,
            gender_policy: data.gender_policy,
            allowed_communities: data.allowed_communities,
            house_rules: Object.entries(data.rules)
                .filter(([, rule]) => rule.choice !== 'unset')
                .map(([id, rule]) => ({
                    id: Number(id),
                    is_allowed: rule.choice === 'allowed',
                    note: rule.note || null,
                })),
        }));
        form.put(update.url(property.id), { preserveScroll: true });
    };

    const categories = [
        ...new Set(
            options.propertyAmenities.map((amenity) => amenity.category),
        ),
    ];

    return (
        <section className="space-y-6">
            <header className="space-y-1">
                <h2 className="text-lg font-semibold">
                    {t('Amenities, rules and coexistence')}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {t(
                        'Amenities of the shared areas. Each space can add its own.',
                    )}
                </p>
            </header>
            <div>
                <form onSubmit={submit}>
                    <fieldset disabled={disabled} className="space-y-6">
                        <InputError
                            message={
                                form.errors.amenity_ids ??
                                (form.errors as Record<string, string>)
                                    .house_rules
                            }
                        />
                        {categories.map((category) => (
                            <div key={category} className="space-y-2">
                                <h3 className="text-sm font-medium">
                                    {category}
                                </h3>
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {options.propertyAmenities
                                        .filter(
                                            (amenity) =>
                                                amenity.category === category,
                                        )
                                        .map((amenity) => (
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
                                                                form.data
                                                                    .amenity_ids,
                                                                amenity.value,
                                                            ),
                                                        )
                                                    }
                                                />
                                                {amenity.label}
                                            </label>
                                        ))}
                                </div>
                            </div>
                        ))}

                        <div className="space-y-2">
                            <h3 className="text-sm font-medium">
                                {t('House rules')}
                            </h3>
                            <p className="text-xs text-muted-foreground">
                                {t(
                                    'Tenants accept them when they request a rental.',
                                )}
                            </p>
                            {options.houseRules.map((rule) => (
                                <div
                                    key={rule.id}
                                    className="grid items-center gap-2 sm:grid-cols-[1fr_10rem_1fr]"
                                >
                                    <Label htmlFor={`rule-${rule.id}`}>
                                        {rule.name}
                                    </Label>
                                    <NativeSelect
                                        id={`rule-${rule.id}`}
                                        value={
                                            form.data.rules[rule.id]?.choice ??
                                            'unset'
                                        }
                                        onChange={(event) =>
                                            form.setData('rules', {
                                                ...form.data.rules,
                                                [rule.id]: {
                                                    ...form.data.rules[rule.id],
                                                    choice: event.target
                                                        .value as RuleChoice,
                                                },
                                            })
                                        }
                                    >
                                        <option value="unset">
                                            {t('Not specified')}
                                        </option>
                                        <option value="allowed">
                                            {t('Allowed')}
                                        </option>
                                        <option value="forbidden">
                                            {t('Not allowed')}
                                        </option>
                                    </NativeSelect>
                                    <Input
                                        aria-label={t('Note for :rule', {
                                            rule: rule.name,
                                        })}
                                        placeholder={t('Note (optional)')}
                                        value={
                                            form.data.rules[rule.id]?.note ?? ''
                                        }
                                        onChange={(event) =>
                                            form.setData('rules', {
                                                ...form.data.rules,
                                                [rule.id]: {
                                                    ...form.data.rules[rule.id],
                                                    note: event.target.value,
                                                },
                                            })
                                        }
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="gender_policy">
                                    {t('Who can live here')}
                                </Label>
                                <NativeSelect
                                    id="gender_policy"
                                    value={form.data.gender_policy}
                                    onChange={(event) =>
                                        form.setData(
                                            'gender_policy',
                                            event.target.value,
                                        )
                                    }
                                >
                                    {options.genderPolicies.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError
                                    message={form.errors.gender_policy}
                                />
                            </div>
                            <fieldset className="grid gap-2">
                                <legend className="mb-2 text-sm font-medium">
                                    {t('Communities admitted')}
                                </legend>
                                {options.communities.map((option) => (
                                    <label
                                        key={option.value}
                                        className="flex items-center gap-2 text-sm"
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
                                <p className="text-xs text-muted-foreground">
                                    {t('None selected = all communities.')}
                                </p>
                                <InputError
                                    message={form.errors.allowed_communities}
                                />
                            </fieldset>
                        </div>

                        <FormActions>
                            <Button disabled={form.processing}>
                                {t('Save')}
                            </Button>
                        </FormActions>
                    </fieldset>
                </form>
            </div>
        </section>
    );
}
