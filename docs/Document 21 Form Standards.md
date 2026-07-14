Document 21: Form Standards
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the standards and best practices for building and managing all forms within the SkyFi Networks application. It defines the chosen technologies, implementation patterns, layout conventions, and user interaction behaviors.

The goal is to ensure that all forms are consistent, performant, accessible, and provide an excellent user experience while being easy for developers to build and maintain.

2.0 Responsibilities
Role	Responsibility
Frontend Lead	Enforce adherence to these form standards in code reviews.
Frontend Developers	Build all forms using the specified libraries and patterns.
UI/UX Designer	Design forms that follow these layout and interaction guidelines.
QA Engineers	Test form functionality, validation, error handling, and accessibility.
3.0 Goals
Consistency: All forms should have a similar look, feel, and behavior, making them predictable for users.
Performance: Minimize re-renders during user input to ensure a smooth experience, especially on large forms.
Developer Experience: Provide a simple, powerful, and reusable pattern for form creation that handles state, validation, and submission gracefully.
User Experience: Provide clear labels, intuitive layouts, and immediate, helpful validation feedback.
Accessibility: Ensure forms are fully usable via keyboard and screen readers.
4.0 Chosen Technologies
Technology	Role	Justification
React Hook Form	Form State & Logic Management	A high-performance, flexible, and hook-based library for managing form state. It minimizes re-renders by using uncontrolled components by default, which is critical for large, complex forms.
Zod	Schema-based Validation	A TypeScript-first schema declaration and validation library. It allows us to define a single schema for a form's data structure and validation rules, which can be reused on both the frontend and backend. The integration with React Hook Form is seamless via @hookform/resolvers/zod.
5.0 Form Implementation Pattern
All forms will be built following a consistent, three-part pattern:

Schema Definition (Zod): Define the form's data shape and validation rules.
Component Implementation (useForm): Use the useForm hook with the Zod resolver.
UI Markup (FormField): Use a standardized FormField common component to render inputs and connect them to the form state.
5.1 Step-by-Step Example: "Create Lead" Form

Step 1: Define the Schema (src/features/sales/components/create-lead-form.tsx)
The schema is defined using Zod at the top of the form component file or in a separate types.ts file.

TypeScript

import { z } from 'zod';

const createLeadSchema = z.object({
  firstName: z.string().min(1, { message: 'First name is required.' }),
  lastName: z.string().min(1, { message: 'Last name is required.' }),
  email: z.string().email({ message: 'Please enter a valid email address.' }),
  phone: z.string().optional(),
  status: z.enum(['new', 'contacted', 'qualified', 'unqualified']),
});

type CreateLeadFormValues = z.infer<typeof createLeadSchema>;
Justification: Defining the schema with Zod provides a single source of truth for both the data shape (TypeScript type is inferred) and the validation rules.

Step 2: Implement the Component (useForm)
The useForm hook is initialized with the schema resolver.

TypeScript

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';

const CreateLeadForm = () => {
  const form = useForm<CreateLeadFormValues>({
    resolver: zodResolver(createLeadSchema),
    defaultValues: {
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
      status: 'new',
    },
  });

  const onSubmit = (values: CreateLeadFormValues) => {
    // This function is only called if validation succeeds.
    console.log(values);
    // e.g., createLeadMutation.mutate(values);
  };

  // ... return JSX
}
Step 3: Render the UI (<Form> and <FormField>)
We use a wrapper Form component from our component library and our common FormField component for each input.

React

import { Form, FormField } from '@/components/ui/form'; // Assuming these are built with RHF Context
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

// ... inside the CreateLeadForm component's return statement
return (
  <Form {...form}>
    <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
      <FormField
        control={form.control}
        name="firstName"
        render={({ field }) => (
          <FormItem>
            <FormLabel>First Name</FormLabel>
            <FormControl>
              <Input placeholder="John" {...field} />
            </FormControl>
            <FormMessage /> {/* This component will display the validation error */}
          </FormItem>
        )}
      />
      
      {/* ... other fields ... */}

      <Button type="submit" isLoading={form.formState.isSubmitting}>
        Create Lead
      </Button>
    </form>
  </Form>
);
Justification: This pattern is highly declarative and reusable. The FormField component (which will be a specified common component) encapsulates the logic for connecting the Input to the form state and displaying validation messages, drastically reducing boilerplate.

6.0 Form Layout and Design
Vertical Rhythm: All forms will use a single-column, top-aligned label layout. This is the most scannable and user-friendly layout.
Grouping: Related fields should be grouped visually using a <fieldset> with a <legend> or a Card component with a header. Example: Grouping "Street Address", "City", "State", and "Zip" under an "Address" heading.
Spacing: Consistent spacing will be used between fields and groups as defined in the UI/UX Design System.
Actions:
The primary submission button (e.g., "Save") will always be on the bottom right.
A secondary "Cancel" button or link should be placed to the left of the primary button.
For long forms, consider making the action buttons "sticky" at the bottom of the viewport.
7.0 User Interaction and Feedback
Validation: Validation will trigger onChange after the first onBlur event for a field (mode: 'onTouched' in useForm). This provides immediate feedback without being annoying during initial input.
Error Messages:
Error messages must be clear, concise, and tell the user how to fix the problem (e.g., "Password must be at least 8 characters long.").
They will be displayed directly below the corresponding input field, styled in the "danger" color.
The input field's border will also turn red.
Submission State:
When a form is being submitted, the primary action button must be disabled and show a loading spinner (isLoading prop).
This prevents duplicate submissions.
Success/Error Feedback:
Upon successful submission, a "toast" notification (e.g., "Customer created successfully") should appear. The form should then be reset or the user should be redirected.
If the API returns an error (e.g., a 422 validation error from the server), the error should be displayed in a general "alert" component at the top of the form. The setError function from useForm can be used to programmatically set errors on specific fields if the API provides field-level error details.
8.0 Accessibility
Labels: Every form control must have an associated <label>. The htmlFor attribute must match the id of the input.
Keyboard Navigation: Users must be able to navigate through all form fields and actions using the Tab key in a logical order.
Focus Management:
When a form in a modal opens, focus should be set on the first input field.
When a server-side validation error occurs, focus should be moved to the first field with an error.
ARIA Attributes: aria-invalid="true" will be set on inputs with errors. aria-describedby will link the input to its error message. The FormField component will handle this automatically.
9.0 Complex Forms
Multi-Step Forms (Wizards):
Each step will be a separate component.
The parent "wizard" component will manage the overall state.
useForm will still be used for each step's validation. Before proceeding to the next step, the trigger() function from useForm will be called to validate the current step's fields.
Dynamic Fields (useFieldArray):
For forms where users can add or remove a set of fields (e.g., adding multiple line items to an invoice), the useFieldArray hook from React Hook Form will be used.
This provides helper functions for append, prepend, remove, and insert operations while managing the form state correctly.
10.0 Risks
Risk	Description	Mitigation Strategy
Inconsistent Implementation	Developers bypass the standard components and patterns, creating one-off form implementations.	Code reviews are critical. The FormField common component should be so easy to use that there is no incentive to build a form manually. Discourage direct use of form.register() in favor of the Controller-based FormField.
Poor Performance on Huge Forms	A form with hundreds of fields becomes slow.	React Hook Form is specifically designed to mitigate this. Ensure that components are not re-rendering unnecessarily by using React.memo where appropriate and avoiding passing complex objects as props that change on every render.
Mismatch between Frontend/Backend Validation	The Zod schema on the frontend allows data that the backend API rejects.	The ultimate goal is a shared schema. While initially separate, a future iteration should involve publishing the Zod schemas from a shared library that both the frontend and backend can consume. In the interim, QA testing must explicitly cover cases where frontend and backend validation rules could diverge.
