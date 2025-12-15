// src/app/features/dashboard/dashboard.service.ts

import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of, delay } from 'rxjs';
import { environment } from '../../../environments/environment';
import { DashboardStats, RecentDelivery, DriverPerformance } from './models/dashboard-stats.model';

@Injectable({
  providedIn: 'root'
})
export class DashboardService {
  private apiUrl = `${environment.apiUrl}/dashboard`;
  
  // 🧪 MODE TEST - Changez à false pour utiliser la vraie API
  private MOCK_MODE = true;

  constructor(private http: HttpClient) {}

  getStats(): Observable<DashboardStats> {
    if (this.MOCK_MODE) {
      // Données mockées pour le développement
      const mockStats: DashboardStats = {
        readyToShip: 12,
        inTransit: 8,
        delivered: 45,
        failed: 2,
        totalToday: 67,
        averageDeliveryTime: 35,
        activeDrivers: 6,
        availableDrivers: 3
      };
      return of(mockStats).pipe(delay(500)); // Simule un délai réseau
    }
    
    return this.http.get<DashboardStats>(`${this.apiUrl}/stats`);
  }

  getRecentDeliveries(limit: number = 10): Observable<RecentDelivery[]> {
    if (this.MOCK_MODE) {
      const mockDeliveries: RecentDelivery[] = [
        {
          id: 1,
          orderNumber: 'CMD-2024-001',
          customerName: 'Jean Dupont',
          driverName: 'Marc Martin',
          status: 'in_progress',
          timestamp: new Date().toISOString()
        },
        {
          id: 2,
          orderNumber: 'CMD-2024-002',
          customerName: 'Marie Dubois',
          driverName: 'Pierre Lefebvre',
          status: 'delivered',
          timestamp: new Date(Date.now() - 3600000).toISOString()
        },
        {
          id: 3,
          orderNumber: 'CMD-2024-003',
          customerName: 'Paul Bernard',
          status: 'pending',
          timestamp: new Date(Date.now() - 7200000).toISOString()
        },
        {
          id: 4,
          orderNumber: 'CMD-2024-004',
          customerName: 'Sophie Laurent',
          driverName: 'Luc Moreau',
          status: 'in_progress',
          timestamp: new Date(Date.now() - 1800000).toISOString()
        },
        {
          id: 5,
          orderNumber: 'CMD-2024-005',
          customerName: 'Thomas Petit',
          driverName: 'Antoine Simon',
          status: 'delivered',
          timestamp: new Date(Date.now() - 5400000).toISOString()
        }
      ];
      return of(mockDeliveries).pipe(delay(300));
    }
    
    return this.http.get<RecentDelivery[]>(`${this.apiUrl}/recent-deliveries?limit=${limit}`);
  }

  getDriverPerformance(): Observable<DriverPerformance[]> {
    if (this.MOCK_MODE) {
      const mockPerformance: DriverPerformance[] = [
        {
          driverId: 1,
          driverName: 'Marc Martin',
          deliveriesCompleted: 23,
          averageTime: 28,
          successRate: 95.6
        },
        {
          driverId: 2,
          driverName: 'Pierre Lefebvre',
          deliveriesCompleted: 19,
          averageTime: 32,
          successRate: 94.7
        },
        {
          driverId: 3,
          driverName: 'Luc Moreau',
          deliveriesCompleted: 17,
          averageTime: 30,
          successRate: 94.1
        },
        {
          driverId: 4,
          driverName: 'Antoine Simon',
          deliveriesCompleted: 15,
          averageTime: 35,
          successRate: 93.3
        },
        {
          driverId: 5,
          driverName: 'Nicolas Roux',
          deliveriesCompleted: 12,
          averageTime: 38,
          successRate: 91.7
        }
      ];
      return of(mockPerformance).pipe(delay(400));
    }
    
    return this.http.get<DriverPerformance[]>(`${this.apiUrl}/driver-performance`);
  }

  getDeliveryTrends(period: 'day' | 'week' | 'month' = 'week'): Observable<any> {
    if (this.MOCK_MODE) {
      const mockTrends = {
        period,
        data: [
          { date: '2024-01-01', delivered: 45, failed: 2 },
          { date: '2024-01-02', delivered: 52, failed: 1 },
          { date: '2024-01-03', delivered: 48, failed: 3 }
        ]
      };
      return of(mockTrends).pipe(delay(500));
    }
    
    return this.http.get(`${this.apiUrl}/trends?period=${period}`);
  }
}