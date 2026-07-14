import { Route, Routes } from 'react-router-dom';

import { CreateCustomerPage } from './pages/CreateCustomerPage';
import { CustomerDetailPage } from './pages/CustomerDetailPage';
import { CustomersListPage } from './pages/CustomersListPage';
import { EditCustomerPage } from './pages/EditCustomerPage';

export const CustomerRoutes = () => (
  <Routes>
    <Route path="/" element={<CustomersListPage />} />
    <Route path="/new" element={<CreateCustomerPage />} />
    <Route path="/:id" element={<CustomerDetailPage />} />
    <Route path="/:id/edit" element={<EditCustomerPage />} />
  </Routes>
);
