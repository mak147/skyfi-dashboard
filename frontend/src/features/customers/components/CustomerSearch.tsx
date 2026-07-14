import { useEffect, useState } from 'react';

import { Input } from '@/components/ui/input';

interface CustomerSearchProps {
  value: string;
  onChange: (value: string) => void;
}

export const CustomerSearch = ({ value, onChange }: CustomerSearchProps) => {
  const [localValue, setLocalValue] = useState(value);

  useEffect(() => {
    const timer = setTimeout(() => {
      onChange(localValue);
    }, 300);
    return () => clearTimeout(timer);
  }, [localValue, onChange]);

  useEffect(() => {
    setLocalValue(value);
  }, [value]);

  return (
    <div className="relative">
      <Input
        type="text"
        placeholder="Search by name, phone, email, or code..."
        value={localValue}
        onChange={(e) => setLocalValue(e.target.value)}
        leftIcon={<span aria-hidden="true">🔍</span>}
      />
    </div>
  );
};
