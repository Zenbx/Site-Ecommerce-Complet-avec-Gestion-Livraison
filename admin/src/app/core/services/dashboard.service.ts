import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, throwError, BehaviorSubject, timer } from 'rxjs';
import { catchError, map, retry, shareReplay, switchMap } from 'rxjs/operators';
import { environment } from '../../../environments/environment';
import {
  DashboardStats,
  DashboardOverviewResponse,
  SalesChartData,
  SalesChartResponse,
  TopProduct,
  TopProductsResponse,
  DriverPerformance,
  DeliveryPerformanceResponse,
  RecentActivity,
  RecentActivityResponse,
  DashboardPeriod,
  ChartPeriod,
  ChartGroupBy,
  DeliveryStatus
} from '../models/dashboard-stats.model';

@Injectable({
  providedIn: 'root'
})
export class DashboardService {
  private readonly baseUrl = `${environment.apiUrl}/admin/dashboard`;
  private statsCache$ = new BehaviorSubject<DashboardStats | null>(null);
  private previousStatsCache$ = new BehaviorSubject<DashboardStats | null>(null);

  constructor(private http: HttpClient) {}

  /**
   * Récupère les statistiques globales du dashboard
   */
  getOverview(period: DashboardPeriod = 'today'): Observable<DashboardStats> {
    const params = new HttpParams().set('period', period);
    
    return this.http.get<DashboardOverviewResponse>(`${this.baseUrl}/overview`, { params })
      .pipe(
        retry(1),
        map(response => {
          if (response.success && response.data.statistics) {
            return response.data.statistics;
          }
          throw new Error('Invalid response format');
        }),
        catchError(this.handleError)
      );
  }

  /**
   * Récupère les statistiques avec comparaison de période
   */
  getOverviewWithComparison(
    currentPeriod: DashboardPeriod = 'today',
    previousPeriod: DashboardPeriod = 'week'
  ): Observable<{ current: DashboardStats; previous: DashboardStats }> {
    return new Observable(observer => {
      Promise.all([
        this.getOverview(currentPeriod).toPromise(),
        this.getOverview(previousPeriod).toPromise()
      ]).then(([current, previous]) => {
        if (current && previous) {
          this.statsCache$.next(current);
          this.previousStatsCache$.next(previous);
          observer.next({ current, previous });
          observer.complete();
        }
      }).catch(error => observer.error(error));
    });
  }

  /**
   * Récupère les données pour le graphique d'évolution des ventes
   */
  getSalesChart(
    period: ChartPeriod = 'month',
    groupBy: ChartGroupBy = 'day'
  ): Observable<SalesChartData> {
    const params = new HttpParams()
      .set('period', period)
      .set('group_by', groupBy);
    
    return this.http.get<SalesChartResponse>(`${this.baseUrl}/sales-chart`, { params })
      .pipe(
        retry(1),
        map(response => {
          if (response.success && response.data) {
            return response.data;
          }
          throw new Error('Invalid sales chart response');
        }),
        catchError(this.handleError)
      );
  }

  /**
   * Récupère la liste des produits les plus vendus
   */
  getTopProducts(
    limit: number = 10,
    period: DashboardPeriod = 'month'
  ): Observable<TopProduct[]> {
    const params = new HttpParams()
      .set('limit', limit.toString())
      .set('period', period);
    
    return this.http.get<TopProductsResponse>(`${this.baseUrl}/top-products`, { params })
      .pipe(
        retry(1),
        map(response => {
          if (response.success && response.data) {
            return response.data;
          }
          throw new Error('Invalid top products response');
        }),
        catchError(this.handleError)
      );
  }

  /**
   * Récupère les statistiques de performance des livreurs
   */
  // REMPLACE LA MÉTHODE COMPLÈTE :
getDeliveryPerformance(period: string = 'month'): Observable<DriverPerformance[]> {
  const params = new HttpParams().set('period', period);
  
  return this.http.get<DeliveryPerformanceResponse>(`${this.baseUrl}/delivery-performance`, { params })
    .pipe(
      retry(1),
      map(response => {
        if (response.success && response.data) {
          // Transformation des données pour correspondre au modèle frontend
          return response.data.map(driver => ({
            id: driver.id,
            driverName: driver.name,
            statistics: driver.statistics,
            deliveriesCompleted: driver.statistics?.deliveries?.delivered || 0,
            successRate: driver.statistics?.deliveries?.success_rate || 0,
            averageTime: this.parseAverageTime(driver.statistics?.performance?.average_delivery_time)
          }));
        }
        throw new Error('Invalid delivery performance response');
      }),
      catchError(this.handleError)
    );
}

// AJOUTE CETTE NOUVELLE MÉTHODE JUSTE APRÈS :
public parseAverageTime(timeString: string | undefined): number {
  if (!timeString) return 0;
  const match = timeString.match(/(\d+)/);
  return match ? parseInt(match[1], 10) : 0;
}

  /**
   * Récupère l'activité récente du système
   */
  getRecentActivity(limit: number = 20): Observable<RecentActivity[]> {
    const params = new HttpParams().set('limit', limit.toString());
    
    return this.http.get<RecentActivityResponse>(`${this.baseUrl}/recent-activity`, { params })
      .pipe(
        retry(1),
        map(response => {
          if (response.success && response.data) {
            return response.data;
          }
          throw new Error('Invalid recent activity response');
        }),
        catchError(this.handleError)
      );
  }

  /**
   * Mappe le type d'activité vers un statut de livraison
   */
  private mapActivityTypeToStatus(type: string): DeliveryStatus {
    const mapping: Record<string, DeliveryStatus> = {
      'new_order': 'ready_to_ship',
      'order_shipped': 'in_transit',
      'order_delivered': 'delivered',
      'order_failed': 'failed',
      'order_returned': 'returned'
    };
    return mapping[type] || 'ready_to_ship';
  }

  /**
   * Récupère toutes les données du dashboard en une seule fois
   */
  getDashboardData(period: DashboardPeriod = 'today'): Observable<{
    stats: DashboardStats;
    previousStats: DashboardStats;
    recentActivities: RecentActivity[];
    driverPerformance: DriverPerformance[];
  }> {
    return new Observable(observer => {
      Promise.all([
        this.getOverviewWithComparison(period, 'week').toPromise(),
        this.getRecentActivity(20).toPromise(),
        this.getDeliveryPerformance('month').toPromise()
      ]).then(([overview, activities, drivers]) => {
        if (overview && activities && drivers) {          
          observer.next({
            stats: overview.current,
            previousStats: overview.previous,
            recentActivities: activities.slice(0, 10),
            driverPerformance: drivers.slice(0, 5)
          });
          observer.complete();
        }
      }).catch(error => observer.error(error));
    });
  }

  /**
   * Active le rafraîchissement automatique des données
   */
  startAutoRefresh(intervalMs: number = 30000): Observable<{
    stats: DashboardStats;
    previousStats: DashboardStats;
    recentActivities: RecentActivity[];
    driverPerformance: DriverPerformance[];
  }> {
    return timer(0, intervalMs).pipe(
      switchMap(() => this.getDashboardData()),
      shareReplay(1)
    );
  }

  /**
   * Récupère les stats en cache
   */
  getCachedStats(): DashboardStats | null {
    return this.statsCache$.value;
  }

  /**
   * Récupère les stats précédentes en cache
   */
  getCachedPreviousStats(): DashboardStats | null {
    return this.previousStatsCache$.value;
  }

  /**
   * Gestion centralisée des erreurs
   */
  private handleError(error: any): Observable<never> {
    let errorMessage = 'Une erreur est survenue';
    
    if (error.error instanceof ErrorEvent) {
      // Erreur côté client
      errorMessage = `Erreur: ${error.error.message}`;
    } else {
      // Erreur côté serveur
      errorMessage = `Code: ${error.status}\nMessage: ${error.message}`;
    }
    
    console.error('Dashboard Service Error:', errorMessage);
    return throwError(() => new Error(errorMessage));
  }
}