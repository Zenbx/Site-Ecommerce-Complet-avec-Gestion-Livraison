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
  
  // MODE TEST - Changez à false pour utiliser la vraie API
  private TEST_MODE = true;

  constructor(private http: HttpClient) {}

  /**
   * Récupère les statistiques globales du dashboard
   */
  getStats(): Observable<DashboardStats> {
    if (this.TEST_MODE) {
      const mockStats: DashboardStats = {
        readyToShip: 45,
        inTransit: 23,
        delivered: 156,
        failed: 3,
        totalToday: 227,
        averageDeliveryTime: 32,
        activeDrivers: 12,
        availableDrivers: 5
      };
      return of(mockStats).pipe(delay(300));
    }

    return this.http.get<DashboardStats>(`${this.apiUrl}/stats`);
  }

  /**
   * Récupère les livraisons récentes
   * @param limit Nombre de livraisons à récupérer (par défaut 10)
   */
  getRecentDeliveries(limit: number = 10): Observable<RecentDelivery[]> {
    if (this.TEST_MODE) {
      const mockDeliveries: RecentDelivery[] = [
        {
          id: 1,
          orderNumber: 'CMD-2024-001',
          customerName: 'Jean Dupont',
          driverName: 'Pierre Martin',
          status: 'in_progress',
          timestamp: new Date().toISOString()
        },
        {
          id: 2,
          orderNumber: 'CMD-2024-002',
          customerName: 'Marie Bernard',
          driverName: 'Luc Dubois',
          status: 'delivered',
          timestamp: new Date(Date.now() - 15 * 60000).toISOString()
        },
        {
          id: 3,
          orderNumber: 'CMD-2024-003',
          customerName: 'Paul Moreau',
          driverName: 'Sophie Laurent',
          status: 'assigned',
          timestamp: new Date(Date.now() - 30 * 60000).toISOString()
        },
        {
          id: 4,
          orderNumber: 'CMD-2024-004',
          customerName: 'Claire Simon',
          status: 'pending',
          timestamp: new Date(Date.now() - 45 * 60000).toISOString()
        },
        {
          id: 5,
          orderNumber: 'CMD-2024-005',
          customerName: 'Michel Rousseau',
          driverName: 'Antoine Petit',
          status: 'delivered',
          timestamp: new Date(Date.now() - 60 * 60000).toISOString()
        },
        {
          id: 6,
          orderNumber: 'CMD-2024-006',
          customerName: 'Isabelle Garnier',
          driverName: 'Thomas Blanc',
          status: 'in_progress',
          timestamp: new Date(Date.now() - 20 * 60000).toISOString()
        },
        {
          id: 7,
          orderNumber: 'CMD-2024-007',
          customerName: 'François Faure',
          status: 'failed',
          timestamp: new Date(Date.now() - 90 * 60000).toISOString()
        },
        {
          id: 8,
          orderNumber: 'CMD-2024-008',
          customerName: 'Nathalie Vincent',
          driverName: 'David Mercier',
          status: 'delivered',
          timestamp: new Date(Date.now() - 120 * 60000).toISOString()
        }
      ].slice(0, limit);

      return of(mockDeliveries).pipe(delay(400));
    }

    return this.http.get<RecentDelivery[]>(`${this.apiUrl}/recent-deliveries`, {
      params: { limit: limit.toString() }
    });
  }

  /**
   * Récupère les performances des livreurs actifs
   */
  getDriverPerformance(): Observable<DriverPerformance[]> {
    if (this.TEST_MODE) {
      const mockPerformance: DriverPerformance[] = [
        {
          driverId: 1,
          driverName: 'Pierre Martin',
          deliveriesCompleted: 45,
          averageTime: 28,
          successRate: 98.5
        },
        {
          driverId: 2,
          driverName: 'Sophie Laurent',
          deliveriesCompleted: 42,
          averageTime: 30,
          successRate: 97.8
        },
        {
          driverId: 3,
          driverName: 'Luc Dubois',
          deliveriesCompleted: 38,
          averageTime: 32,
          successRate: 96.2
        },
        {
          driverId: 4,
          driverName: 'Antoine Petit',
          deliveriesCompleted: 35,
          averageTime: 29,
          successRate: 97.1
        },
        {
          driverId: 5,
          driverName: 'Thomas Blanc',
          deliveriesCompleted: 33,
          averageTime: 35,
          successRate: 95.5
        },
        {
          driverId: 6,
          driverName: 'David Mercier',
          deliveriesCompleted: 31,
          averageTime: 34,
          successRate: 94.8
        }
      ];

      return of(mockPerformance).pipe(delay(500));
    }

    return this.http.get<DriverPerformance[]>(`${this.apiUrl}/driver-performance`);
  }

  /**
   * Récupère les statistiques d'une période donnée
   */
  getStatsByDateRange(startDate: Date, endDate: Date): Observable<DashboardStats> {
    if (this.TEST_MODE) {
      const mockStats: DashboardStats = {
        readyToShip: 12,
        inTransit: 8,
        delivered: 345,
        failed: 7,
        totalToday: 372,
        averageDeliveryTime: 35,
        activeDrivers: 10,
        availableDrivers: 3
      };
      return of(mockStats).pipe(delay(300));
    }

    return this.http.get<DashboardStats>(`${this.apiUrl}/stats/range`, {
      params: {
        startDate: startDate.toISOString(),
        endDate: endDate.toISOString()
      }
    });
  }

  /**
   * Récupère le taux de réussite global
   */
  getSuccessRate(): Observable<number> {
    if (this.TEST_MODE) {
      return of(96.8).pipe(delay(200));
    }

    return this.http.get<number>(`${this.apiUrl}/success-rate`);
  }

  /**
   * Récupère les alertes actives
   */
  getActiveAlerts(): Observable<any[]> {
    if (this.TEST_MODE) {
      const mockAlerts = [
        {
          id: 1,
          type: 'warning',
          message: 'Livraison #CMD-2024-015 en retard de 15 minutes',
          timestamp: new Date().toISOString()
        },
        {
          id: 2,
          type: 'info',
          message: '3 nouveaux livreurs disponibles',
          timestamp: new Date(Date.now() - 10 * 60000).toISOString()
        }
      ];
      return of(mockAlerts).pipe(delay(200));
    }

    return this.http.get<any[]>(`${this.apiUrl}/alerts`);
  }
}