import type { Control, FieldPath, FieldValues } from 'react-hook-form';
import { Controller } from 'react-hook-form';

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface FormFieldProps<TFieldValues extends FieldValues> {
  name: FieldPath<TFieldValues>;
  label: string;
  control: Control<TFieldValues>;
  type?: 'text' | 'email' | 'password';
  placeholder?: string;
  autoComplete?: string;
}

export const FormField = <TFieldValues extends FieldValues>({
  name,
  label,
  control,
  type = 'text',
  placeholder,
  autoComplete,
}: FormFieldProps<TFieldValues>) => {
  const inputId = `field-${String(name).replaceAll('.', '-')}`;
  const errorId = `${inputId}-error`;

  return (
    <Controller
      control={control}
      name={name}
      render={({ field, fieldState }) => (
        <div>
          <Label htmlFor={inputId}>{label}</Label>
          <Input
            {...field}
            id={inputId}
            type={type}
            placeholder={placeholder}
            autoComplete={autoComplete}
            isError={Boolean(fieldState.error)}
            aria-describedby={fieldState.error ? errorId : undefined}
          />
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
