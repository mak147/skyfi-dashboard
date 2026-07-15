import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';

import { Button } from '@/components/ui/button';
import { FormField } from '@/components/common/form-field';
import { apiErrorMessage } from '@/lib/apiClient';

import { replyTicket } from '../api/portalApi';
import { replySchema } from '../schemas';

interface TicketReplyFormProps {
  ticketId: number;
}

export const TicketReplyForm = ({ ticketId }: TicketReplyFormProps) => {
  const queryClient = useQueryClient();
  const form = useForm({
    resolver: zodResolver(replySchema),
    defaultValues: { body: '' },
  });

  const mutation = useMutation({
    mutationFn: (body: string) => replyTicket(ticketId, { body }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['portal', 'ticket', ticketId] });
      form.reset();
    },
  });

  const onSubmit = (values: { body: string }) => {
    mutation.mutate(values.body);
  };

  const submitError = mutation.error ? apiErrorMessage(mutation.error, 'Unable to send reply.') : null;

  return (
    <form className="space-y-4" onSubmit={form.handleSubmit(onSubmit)}>
      {submitError ? (
        <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300">
          {submitError}
        </div>
      ) : null}

      <FormField
        control={form.control}
        name="body"
        label="Your reply"
        type="textarea"
        placeholder="Type your message here..."
      />

      <div className="flex items-center justify-end gap-3">
        <Button type="button" variant="secondary" onClick={() => form.reset()} disabled={mutation.isPending}>
          Clear
        </Button>
        <Button type="submit" isLoading={mutation.isPending}>
          Send reply
        </Button>
      </div>
    </form>
  );
};
