import React, { useState, useEffect } from 'react';
import { apiErrorMessage } from '@/lib/apiClient';
import { getRoles, getUserRoles, syncUserRoles } from '../api/rbacApi';
import type { Role } from '../types';

export const UserRoleAssignment: React.FC = () => {
  const [userId, setUserId] = useState<string>('');
  const [userRoles, setUserRoles] = useState<number[]>([]);
  const [allRoles, setAllRoles] = useState<Role[]>([]);
  const [message, setMessage] = useState('');

  useEffect(() => {
    getRoles().then(setAllRoles);
  }, []);

  const handleFetch = async () => {
    if (!userId) return;
    try {
      const roles = await getUserRoles(Number(userId));
      setUserRoles(roles.map(r => r.id));
      setMessage('');
    } catch (error) {
      setMessage(apiErrorMessage(error, 'Error fetching user'));
    }
  };

  const handleSync = async () => {
    try {
      await syncUserRoles(Number(userId), userRoles);
      setMessage('Roles updated successfully');
    } catch (error) {
      setMessage(apiErrorMessage(error, 'Error updating roles'));
    }
  };

  const toggleRole = (id: number) => {
    setUserRoles(prev => prev.includes(id) ? prev.filter(r => r !== id) : [...prev, id]);
  };

  return (
    <div className="p-4 border rounded shadow bg-white mt-8">
      <h2 className="text-xl font-semibold mb-4">Assign Roles to User</h2>
      
      <div className="flex space-x-2 mb-4">
        <input 
          placeholder="User ID" 
          value={userId}
          onChange={e => setUserId(e.target.value)}
          className="border p-2 rounded"
        />
        <button onClick={handleFetch} className="bg-gray-200 px-4 py-2 rounded">
          Load User
        </button>
      </div>

      {userId && (
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-2 border p-3 rounded">
            {allRoles.map(role => (
              <label key={role.id} className="flex items-center space-x-2">
                <input 
                  type="checkbox"
                  checked={userRoles.includes(role.id)}
                  onChange={() => toggleRole(role.id)}
                />
                <span className="text-sm font-medium">{role.name}</span>
              </label>
            ))}
          </div>

          <button onClick={handleSync} className="bg-blue-600 text-white px-4 py-2 rounded">
            Update Roles
          </button>
          {message && <p className="text-sm text-green-600 mt-2">{message}</p>}
        </div>
      )}
    </div>
  );
};
