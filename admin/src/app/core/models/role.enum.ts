// src/app/core/models/role.enum.ts

export enum UserRole {
  ADMIN = 'admin',
  MANAGER = 'gestionnaire',
  SUPERVISOR = 'superviseur'
}

export const ROLE_PERMISSIONS = {
  [UserRole.ADMIN]: ['*'], // Accès total
  [UserRole.MANAGER]: [
    'deliveries.read',
    'deliveries.create',
    'deliveries.update',
    'deliveries.assign',
    'drivers.read',
    'drivers.create',
    'drivers.update',
    'reports.read'
  ],
  [UserRole.SUPERVISOR]: [
    'deliveries.read',
    'deliveries.track',
    'drivers.read',
    'reports.read'
  ]
};