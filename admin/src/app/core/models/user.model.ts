export enum UserRole {
  ADMIN = 'admin',
  GESTIONNAIRE = 'gestionnaire',
  SUPERVISEUR = 'superviseur'
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  user: User;
}