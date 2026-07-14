import React, { useState, useEffect } from 'react';
import { getPermissions, createRole, updateRole } from '../api/rbacApi';
import type { Role, Permission } from '../types';

interface Props {
  role?: Role;
  onSuccess: () => void;
  onCancel: () => void;
}

export const RoleForm: React.FC<Props> = ({ role, onSuccess, onCancel }) => {
  const [name, setName] = useState(role?.name || '');
  const [description, setDescription] = useState(role?.description || '');
  const [selectedPermissions, setSelectedPermissions] = useState<number[]>(
    role?.permissions.map(p => p.id) || []
  );
  const [allPermissions, setAllPermissions] = useState<Permission[]>([]);

  useEffect(() => {
    getPermissions().then(setAllPermissions);
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const data = { name, description, permissions: selectedPermissions };
    if (role) {
      await updateRole(role.id, data);
    } else {
      await createRole(data);
    }
    onSuccess();
  };

  const togglePermission = (id: number) => {
    setSelectedPermissions(prev =>
      prev.includes(id) ? prev.filter(p => p !== id) : [...prev, id]
    );
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4 p-4 border rounded shadow bg-white">
      <h2 className="text-xl font-semibold">{role ? 'Edit Role' : 'Create Role'}</h2>
      
      <div>
        <label className="block text-sm font-medium">Role Name</label>
        <input 
          required
          value={name}
          onChange={e => setName(e.target.value)}
          className="w-full border p-2 rounded mt-1"
        />
      </div>

      <div>
        <label className="block text-sm font-medium">Description</label>
        <input 
          value={description}
          onChange={e => setDescription(e.target.value)}
          className="w-full border p-2 rounded mt-1"
        />
      </div>

      <div>
        <label className="block text-sm font-medium mb-2">Permissions Matrix</label>
        <div className="grid grid-cols-2 gap-2 border p-3 rounded">
          {allPermissions.map(p => (
            <label key={p.id} className="flex items-center space-x-2">
              <input 
                type="checkbox"
                checked={selectedPermissions.includes(p.id)}
                onChange={() => togglePermission(p.id)}
              />
              <span className="text-sm">{p.name}</span>
            </label>
          ))}
        </div>
      </div>

      <div className="flex space-x-2 pt-2">
        <button type="submit" className="bg-blue-600 text-white px-4 py-2 rounded">
          Save
        </button>
        <button type="button" onClick={onCancel} className="bg-gray-300 px-4 py-2 rounded">
          Cancel
        </button>
      </div>
    </form>
  );
};
