import Dexie, { type Table } from 'dexie';

export interface OfflineAccount {
  id: number;
  company_id: number;
  account_type_id: number;
  parent_id: number | null;
  name: string;
  code: string;
  description: string | null;
  placeholder: boolean;
  hidden: boolean;
  balance_num?: number;
  balance_denom?: number;
  classification?: string;
  updated_at?: string;
}

export interface OfflineTransaction {
  id: number;
  company_id: number;
  currency_id: number;
  description: string;
  notes: string | null;
  reference: string | null;
  post_date: string;
  enter_date: string;
  is_posted: boolean;
  updated_at?: string;
}

export interface OfflineSplit {
  id: number;
  transaction_id: number;
  account_id: number;
  amount_num: number;
  amount_denom: number;
  action: 'DEBIT' | 'CREDIT';
  memo: string | null;
  reconcile_state: string;
}

export interface OfflineContact {
  id: number;
  company_id: number;
  name: string;
  type: string;
  email: string | null;
  phone: string | null;
  updated_at?: string;
}

export interface SyncQueueEntry {
  id?: number;
  entity_type: string;
  entity_id: number | null;
  action: 'create' | 'update' | 'delete';
  payload: string;
  created_at: string;
  retries: number;
  status: 'pending' | 'syncing' | 'failed';
}

export interface DeviceInfo {
  key: string;
  value: string;
}

class ShillingsDatabase extends Dexie {
  accounts!: Table<OfflineAccount, number>;
  transactions!: Table<OfflineTransaction, number>;
  splits!: Table<OfflineSplit, number>;
  contacts!: Table<OfflineContact, number>;
  syncQueue!: Table<SyncQueueEntry, number>;
  deviceInfo!: Table<DeviceInfo, string>;

  constructor() {
    super('shillings');

    this.version(1).stores({
      accounts: 'id, company_id, parent_id, account_type_id, name',
      transactions: 'id, company_id, post_date, enter_date',
      splits: 'id, transaction_id, account_id',
      contacts: 'id, company_id, type, name',
      syncQueue: '++id, entity_type, status, created_at',
      deviceInfo: 'key',
    });

    this.version(2).stores({
      transactions: 'id, company_id, post_date, enter_date, is_posted, [company_id+post_date]',
      splits: 'id, transaction_id, account_id, [account_id+transaction_id]',
    });
  }
}

export const db = new ShillingsDatabase();
