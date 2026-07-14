import { apiClient } from '@/lib/apiClient';
import type { 
  ChartOfAccount, 
  FinancialAccount, 
  JournalEntry, 
  Expense, 
  Revenue, 
  FinanceDashboardStats 
} from '../types';

export const financeApi = {
  getDashboardStats: () => apiClient.get<FinanceDashboardStats>('/finance/dashboard'),
  
  getChartOfAccounts: () => apiClient.get<ChartOfAccount[]>('/finance/chart-of-accounts'),
  createChartOfAccount: (data: Partial<ChartOfAccount>) => apiClient.post<ChartOfAccount>('/finance/chart-of-accounts', data),
  
  getFinancialAccounts: () => apiClient.get<FinancialAccount[]>('/finance/accounts'),
  createFinancialAccount: (data: Partial<FinancialAccount>) => apiClient.post<FinancialAccount>('/finance/accounts', data),
  
  getLedger: () => apiClient.get<unknown[]>('/finance/ledger'),
  
  getJournalEntries: () => apiClient.get<JournalEntry[]>('/finance/journal-entries'),
  createJournalEntry: (data: Partial<JournalEntry>) => apiClient.post<JournalEntry>('/finance/journal-entries', data),
  
  getExpenses: () => apiClient.get<Expense[]>('/finance/expenses'),
  createExpense: (data: Partial<Expense>) => apiClient.post<Expense>('/finance/expenses', data),
  
  getRevenues: () => apiClient.get<Revenue[]>('/finance/revenue'),
  createRevenue: (data: Partial<Revenue>) => apiClient.post<Revenue>('/finance/revenue', data),
};
