import type { Control, FieldPath, FieldValues } from 'react-hook-form';
import { Controller } from 'react-hook-form';

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface FormFieldProps<TFieldValues extends FieldValues> {
  name: FieldPath<TFieldValues>;
  label: string;
  control: Control<TFieldValues>;
  type?: 'text' | 'email' | 'password' | 'tel' | 'textarea';
  placeholder?: string;
  autoComplete?: string;
  rows?: number;
}

export const FormField = <TFieldValues extends FieldValues>({
  name,
  label,
  control,
  type = 'text',
  placeholder,
  autoComplete,
  rows = 4,
}: FormFieldProps<TFieldValues>) => {
  const inputId = `field-${String(name).replaceAll('.', '-')}`;
  const errorId = `${inputId}-error`;
  const isTextarea = type === 'textarea';

  return (
    <Controller
      control={control}
      name={name}
      render={({ field, fieldState }) => (
        <div>
          <Label htmlFor={inputId}>{label}</Label>
          {isTextarea ? (
            <Textarea
              {...field}
              id={inputId}
              rows={rows}
              placeholder={placeholder}
              autoComplete={autoComplete}
              aria-invalid={Boolean(fieldState.error)}
              aria-describedby={fieldState.error ? errorId : undefined}
            />
          ) : (
            <Input
              {...field}
              id={inputId}
              type={type}
              placeholder={placeholder}
              autoComplete={autoComplete}
              isError={Boolean(fieldState.error)}
              aria-describedby={fieldState.error ? errorId : undefined}
            />
          )}
          {fieldState.error ? (
            <p id={errorId} className="mt-2 text-xs text-red-600" role="alert">
              {fieldState.error.message}
            </p>
          ) : null}
        </div>
      )}
    />
  );
};
