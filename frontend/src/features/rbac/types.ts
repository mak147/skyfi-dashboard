export interface Permission {
  id: number;
  name: string;
  description: string;
}

export interface Role {
  id: number;
  name: string;
  description: string;
  permissions: Permission[];
}

export interface RoleFormData {
  name: string;
  description: string;
  permissions: number[];
}
