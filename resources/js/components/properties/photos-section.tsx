import { Form, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';
import FileField from '@/components/file-field';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import { destroy, reorder, store, update } from '@/routes/properties/photos';
import type { PropertyPhoto, Unit } from '@/types';

const MAX_PHOTOS = 30;

type Props = {
    propertyId: number;
    photos: PropertyPhoto[];
    units: Unit[];
    disabled: boolean;
};

export default function PhotosSection({
    propertyId,
    photos,
    units,
    disabled,
}: Props) {
    const { t } = useTranslation();
    const options = { preserveScroll: true };

    const move = (index: number, offset: number) => {
        const order = photos.map((photo) => photo.id);
        [order[index], order[index + offset]] = [
            order[index + offset],
            order[index],
        ];
        router.put(reorder.url(propertyId), { order }, options);
    };

    return (
        <section className="space-y-6">
            <header className="space-y-1">
                <h2 className="text-lg font-semibold">{t('Photos')}</h2>
                <p className="text-sm text-muted-foreground">
                    {t(
                        'At least 3 to publish, up to :max. The first one is the cover. JPG, PNG or WEBP up to 10 MB.',
                        { max: MAX_PHOTOS },
                    )}
                </p>
            </header>
            <div className="space-y-4">
                {photos.length > 0 && (
                    <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {photos.map((photo, index) => (
                            <PhotoCard
                                key={photo.id}
                                propertyId={propertyId}
                                photo={photo}
                                units={units}
                                isCover={index === 0}
                                disabled={disabled}
                                onMove={(offset) => move(index, offset)}
                                canMoveLeft={index > 0}
                                canMoveRight={index < photos.length - 1}
                            />
                        ))}
                    </ul>
                )}

                {!disabled && photos.length < MAX_PHOTOS && (
                    <Form
                        {...store.form(propertyId)}
                        options={options}
                        resetOnSuccess
                        className="grid gap-3 sm:grid-cols-[1fr_12rem_auto] sm:items-end"
                    >
                        {({ processing, errors, progress }) => (
                            <>
                                <div className="grid gap-2">
                                    <FileField
                                        id="photos"
                                        name="photos[]"
                                        multiple
                                        required
                                        accept=".jpg,.jpeg,.png,.webp"
                                        hint={t(
                                            'JPG, PNG or WEBP, up to 10 MB each',
                                        )}
                                    />
                                    <InputError
                                        message={
                                            errors.photos ?? errors['photos.0']
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="photo_unit">
                                        {t('They show')}
                                    </Label>
                                    <NativeSelect
                                        id="photo_unit"
                                        name="unit_id"
                                        defaultValue=""
                                    >
                                        <option value="">
                                            {t('The whole property')}
                                        </option>
                                        {units.map((unit) => (
                                            <option
                                                key={unit.id}
                                                value={unit.id}
                                            >
                                                {unit.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </div>
                                <Button disabled={processing}>
                                    {processing && progress
                                        ? `${Math.round(progress.percentage ?? 0)}%`
                                        : t('Upload')}
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </section>
    );
}

function PhotoCard({
    propertyId,
    photo,
    units,
    isCover,
    disabled,
    onMove,
    canMoveLeft,
    canMoveRight,
}: {
    propertyId: number;
    photo: PropertyPhoto;
    units: Unit[];
    isCover: boolean;
    disabled: boolean;
    onMove: (offset: number) => void;
    canMoveLeft: boolean;
    canMoveRight: boolean;
}) {
    const { t } = useTranslation();
    const [alt, setAlt] = useState(photo.alt ?? '');
    const [unitId, setUnitId] = useState(
        photo.unit_id ? String(photo.unit_id) : '',
    );
    const args = { property: propertyId, photo: photo.id };
    const save = (cover = false) =>
        router.put(
            update.url(args),
            { alt: alt || null, unit_id: unitId || null, cover },
            { preserveScroll: true },
        );

    return (
        <li className="space-y-2">
            <div className="relative">
                <img
                    src={photo.thumb}
                    alt={photo.alt ?? ''}
                    className="aspect-[4/3] w-full rounded-xl object-cover"
                    loading="lazy"
                />
                {isCover && (
                    <Badge className="absolute top-2 left-2">
                        {t('Cover')}
                    </Badge>
                )}
            </div>
            {!disabled && (
                <>
                    <Input
                        aria-label={t('Photo description')}
                        placeholder={t('Describe the photo (accessibility)')}
                        value={alt}
                        onChange={(event) => setAlt(event.target.value)}
                        onBlur={() => alt !== (photo.alt ?? '') && save()}
                    />
                    <NativeSelect
                        aria-label={t('Space shown')}
                        value={unitId}
                        onChange={(event) => {
                            setUnitId(event.target.value);
                            router.put(
                                update.url(args),
                                {
                                    alt: alt || null,
                                    unit_id: event.target.value || null,
                                },
                                { preserveScroll: true },
                            );
                        }}
                    >
                        <option value="">{t('The whole property')}</option>
                        {units.map((unit) => (
                            <option key={unit.id} value={unit.id}>
                                {unit.name}
                            </option>
                        ))}
                    </NativeSelect>
                    <div className="flex flex-wrap gap-1">
                        <Button
                            size="icon"
                            variant="ghost"
                            disabled={!canMoveLeft}
                            onClick={() => onMove(-1)}
                            aria-label={t('Move left')}
                        >
                            <ArrowLeft />
                        </Button>
                        <Button
                            size="icon"
                            variant="ghost"
                            disabled={!canMoveRight}
                            onClick={() => onMove(1)}
                            aria-label={t('Move right')}
                        >
                            <ArrowRight />
                        </Button>
                        {!isCover && (
                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={() => save(true)}
                            >
                                <Star /> {t('Make cover')}
                            </Button>
                        )}
                        <Button
                            size="icon"
                            variant="ghost"
                            className="ml-auto text-destructive"
                            aria-label={t('Delete photo')}
                            onClick={() =>
                                window.confirm(t('Delete this photo?')) &&
                                router.delete(destroy.url(args), {
                                    preserveScroll: true,
                                })
                            }
                        >
                            <Trash2 />
                        </Button>
                    </div>
                </>
            )}
        </li>
    );
}
