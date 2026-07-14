import { StrictMode } from 'react';
import { BrowserRouter } from 'react-router-dom';
import { createRoot } from 'react-dom/client';

import '@/assets/styles/index.css';

import { AppProvider } from '@/providers/app-provider';
import { AppRoutes } from '@/routes';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <AppProvider>
      <BrowserRouter>
        <AppRoutes />
      </BrowserRouter>
    </AppProvider>
  </StrictMode>,
);
