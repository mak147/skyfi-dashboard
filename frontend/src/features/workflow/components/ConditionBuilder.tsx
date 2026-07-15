import type { ConditionRule, OperatorCatalogItem } from '../types';

const emptyRule = (): ConditionRule => ({ field: '', operator: 'equals', value: '' });
const emptyGroup = (): ConditionRule => ({ logic: 'AND', rules: [emptyRule()] });

export const ConditionBuilder = ({
  value,
  operators,
  onChange,
}: {
  value: ConditionRule;
  operators: OperatorCatalogItem[];
  onChange: (next: ConditionRule) => void;
}) => {
  const node = value.logic ? value : { logic: 'AND' as const, rules: [value.field ? value : emptyRule()] };
  const rules = node.rules ?? [];

  const updateRule = (index: number, next: ConditionRule) => {
    const copy = [...rules];
    copy[index] = next;
    onChange({ logic: node.logic ?? 'AND', rules: copy });
  };

  const removeRule = (index: number) => {
    const copy = rules.filter((_, i) => i !== index);
    onChange({ logic: node.logic ?? 'AND', rules: copy.length ? copy : [emptyRule()] });
  };

  return (
    <div className="space-y-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h3 className="text-sm font-semibold text-slate-800 dark:text-slate-100">Conditions</h3>
          <p className="text-xs text-slate-500">Nested AND/OR groups evaluated against the event payload.</p>
        </div>
        <select
          className="rounded-lg border border-slate-300 bg-white px-2 py-1 text-sm dark:border-slate-600 dark:bg-slate-900"
          value={node.logic ?? 'AND'}
          onChange={(e) => onChange({ logic: e.target.value as 'AND' | 'OR', rules })}
        >
          <option value="AND">AND</option>
          <option value="OR">OR</option>
        </select>
      </div>

      <div className="space-y-3">
        {rules.map((rule, index) =>
          rule.logic ? (
            <div key={index} className="rounded-lg border border-dashed border-indigo-300 p-3 dark:border-indigo-700">
              <ConditionBuilder
                value={rule}
                operators={operators}
                onChange={(next) => updateRule(index, next)}
              />
              <button type="button" className="mt-2 text-xs text-rose-600" onClick={() => removeRule(index)}>
                Remove group
              </button>
            </div>
          ) : (
            <div key={index} className="grid gap-2 md:grid-cols-4">
              <input
                className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900"
                placeholder="field.path"
                value={rule.field ?? ''}
                onChange={(e) => updateRule(index, { ...rule, field: e.target.value })}
              />
              <select
                className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900"
                value={rule.operator ?? 'equals'}
                onChange={(e) => updateRule(index, { ...rule, operator: e.target.value })}
              >
                {operators.map((op) => (
                  <option key={op.id} value={op.id}>
                    {op.label}
                  </option>
                ))}
              </select>
              <input
                className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900"
                placeholder="value"
                value={typeof rule.value === 'string' || typeof rule.value === 'number' ? String(rule.value) : ''}
                onChange={(e) => updateRule(index, { ...rule, value: e.target.value })}
                disabled={rule.operator === 'is_empty' || rule.operator === 'is_not_empty'}
              />
              <button type="button" className="rounded-lg border px-3 py-2 text-xs text-rose-600" onClick={() => removeRule(index)}>
                Remove
              </button>
            </div>
          ),
        )}
      </div>

      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold dark:border-slate-600"
          onClick={() => onChange({ logic: node.logic ?? 'AND', rules: [...rules, emptyRule()] })}
        >
          + Condition
        </button>
        <button
          type="button"
          className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold dark:border-slate-600"
          onClick={() => onChange({ logic: node.logic ?? 'AND', rules: [...rules, emptyGroup()] })}
        >
          + Nested Group
        </button>
      </div>
    </div>
  );
};
