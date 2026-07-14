import React from 'react';
import { Route, Routes } from 'react-router-dom';
import { RolesPage } from '../pages/RolesPage';

export const RbacRoutes: React.FC = () => {
  return (
    <Routes>
      <Route path="/" element={<RolesPage />} />
    </Routes>
  );
};
