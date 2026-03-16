export type ConflictStrategy = 'LOCAL_WINS' | 'SERVER_WINS' | 'MANUAL';

export interface ConflictItem {
  entity_type: string;
  entity_id: number;
  local_data: Record<string, any>;
  server_data: Record<string, any>;
  local_updated_at: string;
  server_updated_at: string;
}

export interface ConflictResolution {
  entity_type: string;
  entity_id: number;
  resolved_data: Record<string, any>;
  strategy: ConflictStrategy;
}

let defaultStrategy: ConflictStrategy = 'SERVER_WINS';

export function setDefaultConflictStrategy(strategy: ConflictStrategy): void {
  defaultStrategy = strategy;
}

export function getDefaultConflictStrategy(): ConflictStrategy {
  return defaultStrategy;
}

export function resolveConflict(
  conflict: ConflictItem,
  strategy?: ConflictStrategy,
): ConflictResolution {
  const effectiveStrategy = strategy || defaultStrategy;

  switch (effectiveStrategy) {
    case 'LOCAL_WINS':
      return {
        entity_type: conflict.entity_type,
        entity_id: conflict.entity_id,
        resolved_data: conflict.local_data,
        strategy: 'LOCAL_WINS',
      };
    case 'SERVER_WINS':
      return {
        entity_type: conflict.entity_type,
        entity_id: conflict.entity_id,
        resolved_data: conflict.server_data,
        strategy: 'SERVER_WINS',
      };
    case 'MANUAL':
      // For MANUAL, return server data by default; UI will handle merging
      return {
        entity_type: conflict.entity_type,
        entity_id: conflict.entity_id,
        resolved_data: conflict.server_data,
        strategy: 'MANUAL',
      };
  }
}

export function getConflictDiff(conflict: ConflictItem): Record<string, { local: any; server: any }> {
  const diff: Record<string, { local: any; server: any }> = {};
  const allKeys = new Set([
    ...Object.keys(conflict.local_data),
    ...Object.keys(conflict.server_data),
  ]);

  for (const key of allKeys) {
    if (JSON.stringify(conflict.local_data[key]) !== JSON.stringify(conflict.server_data[key])) {
      diff[key] = {
        local: conflict.local_data[key],
        server: conflict.server_data[key],
      };
    }
  }

  return diff;
}
