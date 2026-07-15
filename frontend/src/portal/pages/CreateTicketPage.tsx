import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import { Controller, useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/common/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { apiErrorMessage } from '@/lib/apiClient';

import { createTicket } from '../api/portalApi';
import { ticketSchema } from '../schemas';
import type { PortalTicketForm } from '../types';

const priorities: Array<{ value: PortalTicketForm['priority']; label: string }> = [
  { value: 'low', label: 'Low' },
  { value: 'normal', label: 'Normal' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
];

export const CreateTicketPage = () => {
  const navigate = useNavigate();
  const form = useForm<PortalTicketForm>({
    resolver: zodResolver(ticketSchema),
    defaultValues: {
      category_id: 0,
      priority: 'normal',
      subject: '',
      description: '',
      connection_id: null,
    },
  });

  const mutation = useMutation({
    mutationFn: createTicket,
    onSuccess: (data) => {
      const ticketId = (data.id as number) ?? 0;
      navigate(`/portal/support/${ticketId}`);
    },
  });

  const onSubmit = (values: PortalTicketForm) => {
    mutation.mutate(values);
  };

  const submitError = mutation.error ? apiErrorMessage(mutation.error, 'Unable to create ticket.') : null;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Create support ticket</h1>
          <p className="mt-1 text-sm text-slate-500">Tell us what is wrong and we will get back to you.</p>
        </div>
        <Button variant="secondary" size="sm" asChild>
          <Link to="/portal/support">Back to support</Link>
        </Button>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card dark:border-slate-700 dark:bg-slate-900">
        <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
          {submitError ? <Alert title="Unable to create ticket">{submitError}</Alert> : null}

          <div className="grid gap-6 sm:grid-cols-2">
            <div>
              <Label htmlFor="category_id" required>
                Category
              </Label>
              <Controller
                control={form.control}
                name="category_id"
                render={({ field, fieldState }) => (
                  <>
                    <Input
                      id="category_id"
                      type="number"
                      min={1}
                      placeholder="Category number"
                      value={field.value || ''}
                      onChange={(event) => field.onChange(Number(event.target.value))}
                      isError={Boolean(fieldState.error)}
                    />
                    {fieldState.error ? (
                      <p className="mt-2 text-xs text-red-600" role="alert">
                        {fieldState.error.message}
                      </p>
                    ) : null}
                  </>
                )}
              />
            </div>

            <div>
              <Label htmlFor="priority" required>
                Priority
              </Label>
              <Controller
                control={form.control}
                name="priority"
                render={({ field }) => (
                  <Select value={field.value} onValueChange={field.onChange}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select priority" />
                    </SelectTrigger>
                    <SelectContent>
                      {priorities.map((priority) => (
                        <SelectItem key={priority.value} value={priority.value}>
                          {priority.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                )}
              />
            </div>
          </div>

          <FormField control={form.control} name="subject" label="Subject" type="text" />

          <div>
            <Label htmlFor="description" required>
              Description
            </Label>
            <Controller
              control={form.control}
              name="description"
              render={({ field, fieldState }) => (
                <>
                  <Textarea
                    id="description"
                    rows={5}
                    placeholder="Describe your issue in detail..."
                    value={field.value}
                    onChange={field.onChange}
                    onBlur={field.onBlur}
                    aria-invalid={Boolean(fieldState.error)}
                  />
                  {fieldState.error ? (
                    <p className="mt-2 text-xs text-red-600" role="alert">
                      {fieldState.error.message}
                    </p>
                  ) : null}
                </>
              )}
            />
          </div>

          <div className="flex items-center justify-end gap-3">
            <Button type="button" variant="secondary" asChild>
              <Link to="/portal/support">Cancel</Link>
            </Button>
            <Button type="submit" isLoading={mutation.isPending}>
              Create ticket
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};
