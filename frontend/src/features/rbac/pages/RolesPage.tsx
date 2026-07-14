import React, { useEffect, useState } from 'react';
import { getRoles, deleteRole } from '../api/rbacApi';
import type { Role } from '../types';
import { RoleForm } from '../components/RoleForm';
import { UserRoleAssignment } from '../components/UserRoleAssignment';

export const RolesPage: React.FC = () => {
  const [roles, setRoles] = useState<Role[]>([]);
  const [loading, setLoading] = useState(true);
  const [editingRole, setEditingRole] = useState<Role | undefined>(undefined);
  const [isFormOpen, setIsFormOpen] = useState(false);

  const fetchRoles = async () => {
    try {
      const data = await getRoles();
      setRoles(data);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRoles();
  }, []);

  const handleDelete = async (id: number) => {
    if (confirm('Are you sure you want to delete this role?')) {
      await deleteRole(id);
      fetchRoles();
    }
  };

  const openEdit = (role: Role) => {
    setEditingRole(role);
    setIsFormOpen(true);
  };

  const openCreate = () => {
    setEditingRole(undefined);
    setIsFormOpen(true);
  };

  const closeForm = () => {
    setIsFormOpen(false);
    setEditingRole(undefined);
  };

  const handleSuccess = () => {
    closeForm();
    fetchRoles();
  };

  if (loading) return <div className="p-4">Loading roles...</div>;

  return (
    <div className="p-8 max-w-5xl mx-auto">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold text-slate-800">Roles & Permissions</h1>
        {!isFormOpen && (
          <button 
            onClick={openCreate}
            className="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700"
          >
            Create Role
          </button>
        )}
      </div>

      {isFormOpen ? (
        <RoleForm role={editingRole} onSuccess={handleSuccess} onCancel={closeForm} />
      ) : (
        <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg mb-8">
          <table className="min-w-full divide-y divide-gray-300">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900">Name</th>
                <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900">Description</th>
                <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900">Permissions</th>
                <th className="px-6 py-3 text-right text-sm font-semibold text-gray-900">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 bg-white">
              {roles.map(role => (
                <tr key={role.id}>
                  <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{role.name}</td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{role.description}</td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{role.permissions.length} actions</td>
                  <td className="relative whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-4">
                    <button onClick={() => openEdit(role)} className="text-indigo-600 hover:text-indigo-900">Edit</button>
                    <button onClick={() => handleDelete(role.id)} className="text-red-600 hover:text-red-900">Delete</button>
                  </td>
                </tr>
              ))}
              {roles.length === 0 && (
                <tr>
                  <td colSpan={4} className="px-6 py-4 text-center text-sm text-gray-500">No roles found.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      <UserRoleAssignment />
    </div>
  );
};
