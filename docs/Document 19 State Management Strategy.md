Document 19: State Management Strategy
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the state management strategy for the SkyFi Networks frontend (React SPA). It defines the different categories of state, the tools we will use to manage them, and the architectural patterns for their use.

The purpose is to provide a clear and coherent strategy that enables developers to manage application state in a predictable, scalable, and maintainable way. This avoids common pitfalls like prop-drilling, inconsistent data, and performance bottlenecks.

2.0 Responsibilities
Role	Responsibility
Frontend Lead	Own and enforce the state management strategy. Guide developers on which tool to use for which type of state.
Frontend Developers	Implement stateful logic according to the patterns and tools defined in this document.
QA Engineers	Utilize state management developer tools (e.g., Redux DevTools) to debug and verify application state during testing.
3.0 Goals
Predictability: State changes should follow a clear, traceable path, making the application easier to debug.
Performance: Avoid unnecessary re-renders and efficiently manage data fetching and caching.
Scalability: The strategy must handle a growing and complex application state without degrading performance or developer experience.
Separation of Concerns: Clearly separate different types of state (e.g., server cache vs. global UI state) and manage them with the most appropriate tool.
Excellent Developer Experience: Provide tools that simplify common tasks like data fetching, caching, and managing global state.
4.0 State Categorization
Not all state is the same. Recognizing the different types of state is the first step to managing it effectively. We will categorize state into four main types:

State Type	Description	Examples	Lifespan
Server Cache State	Data that originates from the server. It is asynchronous and can become "stale."	Customer list, invoice details, user profile.	Session
Global UI State	Client-side state that needs to be shared across many components.	Current user/auth status, selected theme (dark/light), sidebar open/closed.	Session
Local Component State	State that is only needed by a single component or a small, co-located group of components.	is-open for a dropdown, current value of a form input.	Component Lifecycle
Form State	State related to user input in forms, including values, validation errors, and submission status.	A new customer creation form.	Component Lifecycle
5.0 Chosen Tools and Their Roles
Based on the state categorization, we will use a dedicated tool for each job. This "right tool for the right job" approach is a core tenet of our strategy.

State Type	Primary Tool	Rationale
Server Cache State	TanStack Query (formerly React Query)	TanStack Query is not a global state manager; it's a server-state manager. It excels at fetching, caching, synchronizing, and updating server data, providing hooks that handle loading states, error states, and re-fetching out-of-the-box. This will manage ~80% of our application's state.
Global UI State	Redux Toolkit	For the small amount of truly global, synchronous UI state, Redux Toolkit provides a robust, predictable, and scalable solution with excellent developer tools. It is the industry standard for complex global state.
Local Component State	React Hooks (useState, useReducer)	For state that doesn't need to be shared, React's built-in hooks are the simplest and most performant solution. There is no need to introduce the complexity of a global store for local concerns.
Form State	React Hook Form + Zod	React Hook Form is designed for performance and developer experience, minimizing re-renders. Paired with Zod for schema-based validation, it provides a powerful and standardized way to handle all forms.
6.0 State Management Architecture Diagram
This diagram illustrates how the different state management tools interact within the application.

mermaid

graph TD
    subgraph "Application State"
        subgraph "Server Cache State (TanStack Query)"
            TSQ[useQuery('customers'), useMutation(...)]
        end
        
        subgraph "Global UI State (Redux Toolkit)"
            RTK[Auth Slice (user, token), UI Slice (theme)]
        end

        subgraph "Local & Form State"
            RHF[React Hook Form (useForm)]
            RState[React Hooks (useState)]
        end
    end

    subgraph "React Components"
        C1[CustomerList Component]
        C2[LoginForm Component]
        C3[ThemeToggle Component]
        C4[Dropdown Component]
    end

    subgraph "External"
        API[REST API]
        Browser[Browser (localStorage)]
    end

    TSQ -- Fetches/Updates --> API
    C1 -- uses --> TSQ

    API -- on login --> RTK[Updates Auth Slice]
    C2 -- dispatches to --> RTK
    C3 -- dispatches to --> RTK
    C3 -- reads from --> RTK

    C4 -- uses --> RState

    C2 -- uses --> RHF
    
    RTK -- can persist to --> Browser
7.0 Detailed Usage Patterns
7.1 TanStack Query for Server Cache State
Querying Data (useQuery): All GET requests will be wrapped in useQuery hooks.
Query Keys: A structured, serializable array that uniquely identifies the data. E.g., ['customers', { page: 2, status: 'active' }].
Benefits: Automatically provides data, isLoading, isError, error, etc. Caches data to avoid re-fetching on re-mount. Handles background re-fetching to keep data fresh.
Example:
React

// src/features/customers/api/useCustomers.ts
export const useCustomers = (filters) => {
  return useQuery({
    queryKey: ['customers', filters],
    queryFn: () => api.getCustomers(filters),
  });
};
Mutating Data (useMutation): All POST, PUT, PATCH, DELETE requests will be wrapped in useMutation.
Benefits: Provides mutate, isLoading, isError helpers.
Invalidation: The most powerful feature. After a successful mutation, we will invalidate related queries, telling TanStack Query to re-fetch that data automatically. This is how we keep the UI in sync with the backend.
Example:
React

// src/features/customers/api/useCreateCustomer.ts
const queryClient = useQueryClient();

export const useCreateCustomer = () => {
  return useMutation({
    mutationFn: api.createCustomer,
    onSuccess: () => {
      // When a customer is created, invalidate the entire 'customers' query space.
      // This will cause any component using useCustomers() to re-fetch.
      queryClient.invalidateQueries({ queryKey: ['customers'] });
    },
  });
};
Global Configuration: A single QueryClientProvider will wrap the entire application, configured with default stale times and retry logic.
7.2 Redux Toolkit for Global UI State
Slices: The Redux store will be organized into "slices" using createSlice. Each slice manages a specific piece of the global state.
Initial Slices:
authSlice: Manages authentication state (user object, accessToken, isAuthenticated status). This is the primary use case for Redux in our app.
uiSlice: Manages global UI state like the current theme ('light' | 'dark') and sidebar state ('open' | 'collapsed').
Store Location: src/store/
No Server State: Redux must not be used to store data fetched from the server. This is TanStack Query's job. Duplicating server state in Redux leads to complex synchronization logic and is an anti-pattern in this architecture.
7.3 React Hooks for Local and Form State
useState: The default choice for simple, local state (e.g., boolean flags for visibility, string for a search input).
useReducer: To be used for complex local state with multiple, inter-dependent actions (e.g., managing the state of a multi-step wizard component).
React Hook Form (useForm): To be used for all forms.
It handles form values, validation state, submission state, and errors internally.
It integrates seamlessly with the FormField common component.
State is co-located with the form, not placed in a global store.
8.0 State Management Flow Example: Creating a Customer
Form: The user fills out the "New Customer" form. React Hook Form manages the input values and validation state locally within the form component.
Submission: On submit, the useCreateCustomer mutation hook (from TanStack Query) is called with the form data: createCustomerMutation.mutate(formData).
Loading State: The createCustomerMutation.isLoading flag becomes true. The UI shows a spinner on the "Submit" button.
API Call: The mutationFn makes the POST /api/v1/customers request.
Success:
The API returns 201 Created.
The onSuccess callback in useMutation is triggered.
queryClient.invalidateQueries({ queryKey: ['customers'] }) is called.
UI Update:
Any component currently on screen that uses useCustomers() (e.g., a customer list in the background) will now automatically re-fetch its data.
The UI updates to show the new customer in the list, without any manual state manipulation.
The form can be reset, and the user can be redirected to the new customer's detail page.
9.0 Developer Experience & Debugging
React Query Devtools: Will be included in development builds. This provides a crucial visualization of all cached queries, their states, and their data.
Redux DevTools: The browser extension will be used to inspect the global UI state, view dispatched actions, and time-travel debug state changes.
Strict Typing: TypeScript will be used throughout, providing autocompletion and type safety for all state objects, query keys, and Redux actions.
10.0 Risks
Risk	Description	Mitigation Strategy
Improper State Placement	A developer puts server state into the Redux store or local state into TanStack Query.	This is the primary risk. It will be mitigated through developer training and strict code reviews. The rule is simple: "If it comes from the API, use TanStack Query. If it's global UI state, use Redux. If it's local, use useState."
Complex Query Keys	Inconsistent or poorly structured query keys in TanStack Query can lead to cache misses and bugs.	Establish and document a clear, consistent pattern for query keys in the Developer Guidelines. E.g., [resource, id] for single items, [resource, { filters }] for lists.
Over-reliance on Redux	Developers coming from a "Redux-first" background may try to manage all state in Redux.	Emphasize that Redux is for a very small, specific subset of state in this architecture. The default should always be to reach for useState or useQuery first. The authSlice is the only mission-critical piece of Redux state.
Prop-Drilling	A developer avoids using a global store and instead passes props down through many layers of components.	If a piece of state is needed more than 2-3 levels deep or in widely separated parts of the component tree, it's a candidate for either React Context (for simple values) or the Redux store (for complex state).