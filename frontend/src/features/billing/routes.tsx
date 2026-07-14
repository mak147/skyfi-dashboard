import { Route, Routes } from 'react-router-dom';

import { BillingHistoryPage } from './pages/BillingHistoryPage';
import { BulkBillingPage } from './pages/BulkBillingPage';
import { GenerateInvoicePage } from './pages/GenerateInvoicePage';
import { InvoiceDetailPage } from './pages/InvoiceDetailPage';
import { InvoicesListPage } from './pages/InvoicesListPage';

export const BillingRoutes = () => (
  <Routes>
    <Route path="/" element={<InvoicesListPage />} />
    <Route path="/history" element={<BillingHistoryPage />} />
    <Route path="/new" element={<GenerateInvoicePage />} />
    <Route path="/bulk" element={<BulkBillingPage />} />
    <Route path="/:id" element={<InvoiceDetailPage />} />
  </Routes>
);
