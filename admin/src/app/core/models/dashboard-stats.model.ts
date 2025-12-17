// src/app/features/dashboard/models/dashboard-stats.model.ts

export interface DashboardStats {
  readyToShip: number;
  inTransit: number;
  delivered: number;
  failed: number;
  totalToday: number;
  averageDeliveryTime: number; // en minutes
  activeDrivers: number;
  availableDrivers: number;
}

export interface RecentDelivery {
  id: number;
  orderNumber: string;
  customerName: string;
  driverName?: string;
  status: string;
  timestamp: string;
}

export interface DriverPerformance {
  driverId: number;
  driverName: string;
  deliveriesCompleted: number;
  averageTime: number;
  successRate: number;
}