import { FileUp } from 'lucide-react';
import { useId, useState } from 'react';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * File input as a drop area: says what it accepts, shows what was chosen. Works inside
 * Inertia <Form> because it is a real <input type="file" name=…>.
 */
export default function FileField({
    name,
    accept,
    hint,
    multiple = false,
    required = false,
    label,
    id,
}: {
    name: string;
    accept: string;
    hint: string;
    multiple?: boolean;
    required?: boolean;
    label?: string;
    id?: string;
}) {
    const { t } = useTranslation();
    const generated = useId();
    const inputId = id ?? generated;
    const [files, setFiles] = useState<string[]>([]);
    const [dragging, setDragging] = useState(false);

    return (
        <label
            htmlFor={inputId}
            onDragOver={() => setDragging(true)}
            onDragLeave={() => setDragging(false)}
            onDrop={() => setDragging(false)}
            className={cn(
                'relative flex cursor-pointer flex-col items-center gap-1 rounded-xl border border-dashed border-input px-4 py-6 text-center transition-colors focus-within:ring-2 focus-within:ring-ring hover:bg-muted',
                dragging && 'border-brand bg-brand-soft',
            )}
        >
            <FileUp className="size-5 text-muted-foreground" aria-hidden />
            <span className="text-sm font-medium">
                {files.length > 0
                    ? files.join(', ')
                    : (label ??
                      (multiple
                          ? t('Choose files or drop them here')
                          : t('Choose a file or drop it here')))}
            </span>
            <span className="text-xs text-muted-foreground">{hint}</span>
            <input
                id={inputId}
                name={name}
                type="file"
                accept={accept}
                multiple={multiple}
                required={required}
                className="absolute inset-0 cursor-pointer opacity-0"
                onChange={(event) =>
                    setFiles(
                        Array.from(event.target.files ?? []).map(
                            (file) => file.name,
                        ),
                    )
                }
            />
        </label>
    );
}
