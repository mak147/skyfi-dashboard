import { Route, Routes } from 'react-router-dom';

import { RolesPage } from '../pages/RolesPage';

export const RbacRoutes = () => (
  <Routes>
    <Route path="/" element={<RolesPage />} />
  </Routes>
);
