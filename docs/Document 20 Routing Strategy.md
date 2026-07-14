Document 20: Routing Strategy
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the client-side routing strategy for the SkyFi Networks React SPA. It defines the technology to be used, the structure of the routes, and the patterns for handling authentication, authorization, and data loading.

The goal is to create a routing system that is:

Declarative and Maintainable: Easy to read, understand, and modify as the application grows.
URL-Driven: Ensures the URL is the single source of truth for the user's location in the application, enabling deep linking, bookmarking, and browser history navigation.
Secure: Protects routes from unauthorized access.
Performant: Implements code-splitting to load parts of the application on demand.
2.0 Responsibilities
Role	Responsibility
Frontend Lead	Own the routing architecture. Ensure new routes are added correctly and follow the defined patterns.
Frontend Developers	Implement page components and define their routes according to this strategy.
QA Engineers	Test all navigation paths, including protected routes, redirects, and "not found" pages.
3.0 Chosen Technology: React Router v6
Library: react-router-dom
Justification: React Router is the de facto standard for routing in React applications. Version 6 introduced a powerful, hook-based, and component-based API that is highly declarative and fits perfectly with modern React development. Its features, such as nested routes, loaders, and search param handling, are essential for a complex enterprise application.
4.0 Routing Architecture
We will adopt a centralized but feature-distributed routing configuration.

Main Router: A single, top-level router (src/routes/index.tsx) will define the main application layouts and import route configurations from individual feature modules.
Feature-Based Routes: Each feature folder (e.g., src/features/billing/) will contain its own routes.tsx file that defines all the routes related to that feature. This co-locates the routes with the components they render, aligning with our modular architecture.
Code-Splitting: We will use route-based code-splitting with React.lazy() and <Suspense> to ensure users only download the JavaScript for the part of the application they are currently viewing.
4.1 High-Level Structure Diagram

mermaid

graph TD
    subgraph "App Entry Point (main.tsx)"
        A[BrowserRouter]
    end
    
    subgraph "Main Router (src/routes/index.tsx)"
        B(Root Route '/')
        B --> C{Layout Components (e.g., AppLayout)}
        B --> D[Public Routes (e.g., /login)]
        B --> E[Protected Routes (e.g., /dashboard)]
    end

    subgraph "Feature Routes"
        F1[src/features/customers/routes.tsx]
        F2[src/features/billing/routes.tsx]
        F3[src/features/network/routes.tsx]
    end

    A --> B
    E -- "Imports & Nests" --> F1
    E -- "Imports & Nests" --> F2
    E -- "Imports & Nests" --> F3
5.0 Route Definitions and Patterns
The URL structure will follow the RESTful principles outlined in the Navigation Structure (Document 16).

5.1 Example URL Structure:

Path	Component	Description
/login	LoginPage	Public login page.
/forgot-password	ForgotPasswordPage	Public forgot password page.
/dashboard	DashboardPage	Main dashboard after login.
/customers	CustomerListPage	Displays a list of all customers.
/customers/{id}	CustomerDetailPage	Displays details for a single customer.
/customers/{id}/invoices	CustomerInvoiceListPage	Nested view showing invoices for one customer.
/invoices	InvoiceListPage	Global list of all invoices.
/invoices/{id}	InvoiceDetailPage	Details for a single invoice.
/admin/users	UserManagementPage	Admin page for managing users.
*	NotFoundPage	A catch-all route for any path not matched.
5.2 Route Configuration Example

We will use the JSX-based <Routes> and <Route> components for configuration.

Main Router (src/routes/index.tsx):

React

import { Routes, Route } from 'react-router-dom';
import { AppLayout } from '@/components/layout/app-layout';
import { ProtectedRoute } from './protected-route';
import { LoginPage } from '@/features/authentication';
import { CustomerRoutes } from '@/features/customers/routes';
import { BillingRoutes } from '@/features/billing/routes';
import { NotFoundPage } from '@/components/common/not-found-page';

export const AppRoutes = () => {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      
      {/* Routes that require authentication */}
      <Route element={<ProtectedRoute />}>
        <Route element={<AppLayout />}>
          <Route path="/" element={<DashboardPage />} />
          
          {/* Import and nest feature routes */}
          <Route path="/customers/*" element={<CustomerRoutes />} />
          <Route path="/billing/*" element={<BillingRoutes />} />
          
          {/* ... other feature routes */}
        </Route>
      </Route>
      
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
};
Feature Routes (src/features/customers/routes.tsx):

React

import { Routes, Route } from 'react-router-dom';
import { lazy, Suspense } from 'react';

// Use React.lazy for code-splitting
const CustomerListPage = lazy(() => import('./pages/customer-list-page'));
const CustomerDetailPage = lazy(() => import('./pages/customer-detail-page'));

export const CustomerRoutes = () => {
  return (
    <Suspense fallback={<PageLoader />}>
      <Routes>
        <Route path="" element={<CustomerListPage />} />
        <Route path=":id" element={<CustomerDetailPage />} />
        <Route path=":id/invoices" element={<CustomerInvoiceListPage />} />
      </Routes>
    </Suspense>
  );
};
6.0 Protected Routes and Authorization
Authentication (<ProtectedRoute />):

A custom wrapper component, <ProtectedRoute>, will be created.
It will check the authentication status from the authSlice in the Redux store.
If the user is authenticated, it will render its child routes (using <Outlet />).
If the user is not authenticated, it will redirect them to the /login page, storing the intended destination in the route state so they can be redirected back after logging in.
Authorization (Page-Level):

While the navigation UI is already role-aware, we need a "defense-in-depth" check at the page level.
A custom hook, useAuthorization(permission: string), will be created.
This hook will check the user's permissions from the Redux store. If the required permission is not present, it will programmatically navigate the user to a 403 Forbidden page or back to the dashboard.
This hook will be called at the top of every page component that corresponds to a protected resource.
Example (CustomerListPage.tsx):

React

import { useAuthorization } from '@/hooks/use-authorization';

const CustomerListPage = () => {
  // If the user doesn't have this permission, this hook will handle the redirect.
  // The component rendering will be stopped.
  useAuthorization('view:customer');
  
  // ... rest of the component logic
  return <div>Customer List</div>;
};
7.0 Data Loading and URL State
The URL should be the canonical source of truth for the state of the page. This includes filters, sorting, and pagination for data tables.

React Router Hooks: We will use useSearchParams to read and write state to the URL's query string.
TanStack Query Integration: The search parameters from the URL will be passed directly into the queryKey of useQuery. This creates a powerful declarative link: when the URL changes, the query key changes, and TanStack Query automatically re-fetches the data.
Example Flow for a Filterable List:

Component Renders: CustomerListPage renders.
Read URL: It uses useSearchParams to get the current filters, e.g., status=active.
Fetch Data: It calls the useCustomers({ status: 'active' }) hook. The filter object is part of the queryKey (['customers', { status: 'active' }]).
User Interaction: The user changes a filter dropdown to "Suspended."
Update URL: An onChange handler calls setSearchParams({ status: 'suspended' }), updating the URL to /customers?status=suspended.
Re-render & Re-fetch: The component re-renders because the search params have changed. The call to useCustomers now has a new filter object, which creates a new queryKey (['customers', { status: 'suspended' }]). TanStack Query sees the new key and automatically fetches the data for suspended customers.
This pattern ensures that filtered views are bookmarkable and shareable, and the browser's back/forward buttons work as expected.

8.0 Loading and Error States
Code-Splitting Fallback: The top-level <Suspense> boundary in each feature's route file will show a generic page loader (e.g., a centered spinner) while the JavaScript chunk for the page is being downloaded.
Data Loading: Within a page, the isLoading state from useQuery will be used to show more specific skeleton loaders that mimic the page's layout.
Not Found (404): The <Route path="*" ... /> will catch any undefined URLs and render a user-friendly "Page Not Found" component.
Forbidden (403): The useAuthorization hook will redirect users to a dedicated "Access Denied" page.
9.0 Risks
Risk	Description	Mitigation Strategy
Large Bundle Sizes	Forgetting to code-split new routes leads to an ever-increasing initial JavaScript payload.	Make React.lazy() the default pattern for all page-level components. The PR template should include a checklist item: "Is this route code-split?".
Complex Nested Routes	Overly deep nesting of routes (/a/b/c/d/e) can become hard to manage and read.	As a rule of thumb, limit nesting to 2-3 levels where possible. Use URL parameters and search params instead of deep nesting for filtering or identifying resources.
"Flash" of Unprotected Content	A protected page briefly renders before the authentication check completes and redirects.	The <ProtectedRoute> component should render a full-page loader while it's performing the auth check, preventing any child content from rendering until the check is complete.
State Mismatch	The UI state (e.g., in Redux) and the URL state become out of sync.	Enforce the URL as the single source of truth. The UI should always read its primary state (filters, page numbers, etc.) from the URL via useParams or useSearchParams. The UI's job is to reflect the URL, not the other way around.