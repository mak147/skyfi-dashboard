import { Route, Routes } from 'react-router-dom';

import { FinanceDashboardPage } from './pages/FinanceDashboardPage';
import { ChartOfAccountsPage } from './pages/ChartOfAccountsPage';

export const FinanceRoutes = () => (
  <Routes>
    <Route path="/" element={<FinanceDashboardPage />} />
    <Route path="/accounts" element={<ChartOfAccountsPage />} />
  </Routes>
);
