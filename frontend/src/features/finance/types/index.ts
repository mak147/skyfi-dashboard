export interface ChartOfAccount {
  id: number;
  account_number: string;
  name: string;
  type: 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';
  normal_balance: 'debit' | 'credit';
  parent_id: number | null;
}

export interface FinancialAccount {
  id: number;
  account_type: 'cash' | 'bank' | 'merchant';
  name: string;
  chart_of_account_id: number;
  chart_of_account_name?: string;
  balance: string;
  currency: string;
  status: 'active' | 'inactive';
}

export interface JournalEntryLine {
  id: number;
  account_id: number;
  account_number?: string;
  account_name?: string;
  debit_amount: string | null;
  credit_amount: string | null;
}

export interface JournalEntry {
  id: number;
  transaction_id: string;
  description: string;
  transaction_date: string;
  source_id: number | null;
  source_type: string | null;
  created_by: number;
  created_by_name?: string;
  lines: JournalEntryLine[];
}

export interface Expense {
  id: number;
  category: string;
  amount: string;
  transaction_date: string;
  description: string;
  financial_account_id: number;
  financial_account_name?: string;
  chart_of_account_id: number;
  chart_of_account_name?: string;
}

export interface Revenue {
  id: number;
  category: string;
  amount: string;
  transaction_date: string;
  description: string;
  financial_account_id: number;
  financial_account_name?: string;
  chart_of_account_id: number;
  chart_of_account_name?: string;
}

export interface FinanceDashboardStats {
  cash_balance: number;
  bank_balance: number;
  revenue_this_month: number;
  expenses_this_month: number;
}
