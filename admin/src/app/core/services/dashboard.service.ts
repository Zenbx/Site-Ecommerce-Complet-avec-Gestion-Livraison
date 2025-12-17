// src/app/features/dashboard/dashboard.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of, delay, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { DashboardStats, RecentDelivery, DriverPerformance } from '../models/dashboard-stats.model';

@Injectable({
  providedIn: 'root'
})
export class DashboardService {
  private apiUrl = `${environment.apiUrl}/dashboard`;
  
  // 🧪 MODE MOCK - Changez à false pour utiliser la vraie API
  private MOCK_MODE = true;

  constructor(private http: HttpClient) {}

  /**
   * Récupère les statistiques globales du dashboard
   */
  getStats(): Observable<DashboardStats> {
    if (this.MOCK_MODE) {
      return this.getMockStats();
    }

    return this.http.get<DashboardStats>(`${this.apiUrl}/stats`);
  }

  /**
   * Récupère les livraisons récentes
   */
  getRecentDeliveries(limit: number = 10): Observable<RecentDelivery[]> {
    if (this.MOCK_MODE) {
      return this.getMockRecentDeliveries(limit);
    }

    return this.http.get<RecentDelivery[]>(`${this.apiUrl}/recent-deliveries`, {
      params: { limit: limit.toString() }
    });
  }

  /**
   * Récupère les performances des livreurs actifs
   */
  getDriverPerformance(): Observable<DriverPerformance[]> {
    if (this.MOCK_MODE) {
      return this.getMockDriverPerformance();
    }

    return this.http.get<DriverPerformance[]>(`${this.apiUrl}/driver-performance`);
  }

  /**
   * Récupère les statistiques d'une période donnée
   */
  getStatsByDateRange(startDate: Date, endDate: Date): Observable<DashboardStats> {
    if (this.MOCK_MODE) {
      return this.getMockStatsByRange(startDate, endDate);
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
    if (this.MOCK_MODE) {
      return of(96.8).pipe(delay(200));
    }

    return this.http.get<number>(`${this.apiUrl}/success-rate`);
  }

  /**
   * Récupère les alertes actives
   */
  getActiveAlerts(): Observable<any[]> {
    if (this.MOCK_MODE) {
      return this.getMockAlerts();
    }

    return this.http.get<any[]>(`${this.apiUrl}/alerts`);
  }

  /**
   * Récupère les données pour les graphiques de tendance
   */
  getTrendData(period: 'day' | 'week' | 'month'): Observable<any[]> {
    if (this.MOCK_MODE) {
      return this.getMockTrendData(period);
    }

    return this.http.get<any[]>(`${this.apiUrl}/trends`, {
      params: { period }
    });
  }

  // ========================================
  // MÉTHODES MOCK
  // ========================================

  private getMockStats(): Observable<DashboardStats> {
    // Génère des stats aléatoires pour simuler des changements
    const baseStats: DashboardStats = {
      readyToShip: Math.floor(Math.random() * 20) + 40,
      inTransit: Math.floor(Math.random() * 10) + 20,
      delivered: Math.floor(Math.random() * 30) + 150,
      failed: Math.floor(Math.random() * 5) + 2,
      totalToday: 0,
      averageDeliveryTime: Math.floor(Math.random() * 10) + 28,
      activeDrivers: Math.floor(Math.random() * 5) + 10,
      availableDrivers: Math.floor(Math.random() * 3) + 4
    };

    baseStats.totalToday = baseStats.readyToShip + baseStats.inTransit + 
                            baseStats.delivered + baseStats.failed;

    return of(baseStats).pipe(delay(300));
  }

  private getMockRecentDeliveries(limit: number): Observable<RecentDelivery[]> {
    const statuses = ['pending', 'assigned', 'in_progress', 'delivered', 'failed'];
    const customers = [
      'Jean Dupont', 'Marie Bernard', 'Paul Moreau', 'Claire Simon',
      'Michel Rousseau', 'Isabelle Garnier', 'François Faure', 'Nathalie Vincent',
      'Laurent Thomas', 'Sylvie Lefebvre', 'Alain Mercier', 'Caroline Blanc'
    ];
    const drivers = [
      'Pierre Martin', 'Sophie Laurent', 'Luc Dubois', 'Antoine Petit',
      'Thomas Blanc', 'David Mercier', 'Emma Rousseau', 'Julie Moreau'
    ];

    const mockDeliveries: RecentDelivery[] = Array.from({ length: limit }, (_, i) => {
      const status = statuses[Math.floor(Math.random() * statuses.length)];
      const hasDriver = status !== 'pending';
      
      return {
        id: i + 1,
        orderNumber: `CMD-2024-${String(Math.floor(Math.random() * 1000)).padStart(3, '0')}`,
        customerName: customers[Math.floor(Math.random() * customers.length)],
        driverName: hasDriver ? drivers[Math.floor(Math.random() * drivers.length)] : undefined,
        status,
        timestamp: new Date(Date.now() - Math.random() * 3600000).toISOString()
      };
    });

    return of(mockDeliveries).pipe(delay(400));
  }

  private getMockDriverPerformance(): Observable<DriverPerformance[]> {
    const driverNames = [
      'Pierre Martin', 'Sophie Laurent', 'Luc Dubois', 'Antoine Petit',
      'Thomas Blanc', 'David Mercier', 'Emma Rousseau', 'Julie Moreau',
      'Marc Fontaine', 'Alice Durand'
    ];

    const mockPerformance: DriverPerformance[] = driverNames.map((name, index) => ({
      driverId: index + 1,
      driverName: name,
      deliveriesCompleted: Math.floor(Math.random() * 20) + 30,
      averageTime: Math.floor(Math.random() * 15) + 25,
      successRate: Math.floor(Math.random() * 500 + 9500) / 100
    }));

    // Trier par nombre de livraisons
    mockPerformance.sort((a, b) => b.deliveriesCompleted - a.deliveriesCompleted);

    return of(mockPerformance).pipe(delay(500));
  }

  private getMockStatsByRange(startDate: Date, endDate: Date): Observable<DashboardStats> {
    const daysDiff = Math.floor((endDate.getTime() - startDate.getTime()) / (1000 * 3600 * 24));
    
    const stats: DashboardStats = {
      readyToShip: Math.floor(Math.random() * 20) + 10,
      inTransit: Math.floor(Math.random() * 15) + 5,
      delivered: Math.floor(daysDiff * (Math.random() * 50 + 100)),
      failed: Math.floor(daysDiff * (Math.random() * 3 + 1)),
      totalToday: 0,
      averageDeliveryTime: Math.floor(Math.random() * 10) + 30,
      activeDrivers: Math.floor(Math.random() * 5) + 8,
      availableDrivers: Math.floor(Math.random() * 3) + 2
    };

    stats.totalToday = stats.readyToShip + stats.inTransit + stats.delivered + stats.failed;

    return of(stats).pipe(delay(300));
  }

  private getMockAlerts(): Observable<any[]> {
    const alertTypes = ['warning', 'info', 'error', 'success'];
    const messages = [
      'Livraison #CMD-2024-015 en retard de 15 minutes',
      '3 nouveaux livreurs disponibles',
      'Pic de commandes détecté',
      'Livreur Pierre Martin a atteint 100 livraisons',
      'Zone de livraison saturée : Centre-ville',
      'Maintenance système prévue dans 2 heures'
    ];

    const numAlerts = Math.floor(Math.random() * 4) + 1;
    const mockAlerts = Array.from({ length: numAlerts }, (_, i) => ({
      id: i + 1,
      type: alertTypes[Math.floor(Math.random() * alertTypes.length)],
      message: messages[Math.floor(Math.random() * messages.length)],
      timestamp: new Date(Date.now() - Math.random() * 1800000).toISOString()
    }));

    return of(mockAlerts).pipe(delay(200));
  }

  private getMockTrendData(period: 'day' | 'week' | 'month'): Observable<any[]> {
    const dataPoints = period === 'day' ? 24 : period === 'week' ? 7 : 30;
    
    const trendData = Array.from({ length: dataPoints }, (_, i) => ({
      label: period === 'day' ? `${i}:00` : `Jour ${i + 1}`,
      delivered: Math.floor(Math.random() * 30) + 40,
      failed: Math.floor(Math.random() * 5) + 1,
      inProgress: Math.floor(Math.random() * 10) + 5
    }));

    return of(trendData).pipe(delay(400));
  }
}