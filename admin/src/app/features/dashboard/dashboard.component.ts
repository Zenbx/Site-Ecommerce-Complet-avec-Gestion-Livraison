// src/app/features/dashboard/dashboard.component.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Subject, takeUntil, interval } from 'rxjs';
import { DashboardService } from './dashboard.service';
import { WebSocketService } from '../../core/services/websocket.service';
import { DashboardStats, RecentDelivery, DriverPerformance } from './models/dashboard-stats.model';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit, OnDestroy {
  Math = Math;
  stats: DashboardStats | null = null;
  previousStats: DashboardStats | null = null;
  recentDeliveries: RecentDelivery[] = [];
  driverPerformance: DriverPerformance[] = [];
  alerts: any[] = [];
  loading = true;
  wsConnected = false;
  lastUpdate: Date = new Date();
  
  // Configuration
  private AUTO_REFRESH_INTERVAL = 30000; // 30 secondes
  private ENABLE_WEBSOCKET = false; // Désactivé en mode mock
  private destroy$ = new Subject<void>();

  // Filtres
  selectedPeriod: 'today' | 'week' | 'month' = 'today';

  constructor(
    private dashboardService: DashboardService,
    private wsService: WebSocketService
  ) {}

  ngOnInit(): void {
    this.loadDashboardData();
    this.setupAutoRefresh();
    
    if (this.ENABLE_WEBSOCKET) {
      this.setupWebSocketListeners();
    }
    
    this.loadAlerts();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    if (this.ENABLE_WEBSOCKET) {
      this.wsService.disconnect();
    }
  }

  /**
   * Charge toutes les données du dashboard
   */
  private loadDashboardData(): void {
    this.loading = true;
    this.lastUpdate = new Date();

    // Sauvegarder les stats précédentes pour comparer
    this.previousStats = this.stats ? { ...this.stats } : null;

    // Charger les statistiques
    this.dashboardService.getStats()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (stats) => {
          this.stats = stats;
          this.loading = false;
        },
        error: (error) => {
          console.error('❌ Error loading stats:', error);
          this.loading = false;
        }
      });

    // Charger les livraisons récentes
    this.dashboardService.getRecentDeliveries(8)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (deliveries) => {
          this.recentDeliveries = deliveries;
        },
        error: (error) => {
          console.error('❌ Error loading recent deliveries:', error);
        }
      });

    // Charger les performances des livreurs (top 5)
    this.dashboardService.getDriverPerformance()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (performance) => {
          this.driverPerformance = performance.slice(0, 5);
        },
        error: (error) => {
          console.error('❌ Error loading driver performance:', error);
        }
      });
  }

  /**
   * Charge les alertes actives
   */
  private loadAlerts(): void {
    this.dashboardService.getActiveAlerts()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (alerts) => {
          this.alerts = alerts;
        },
        error: (error) => {
          console.error('❌ Error loading alerts:', error);
        }
      });
  }

  /**
   * Configure le rafraîchissement automatique
   */
  private setupAutoRefresh(): void {
    interval(this.AUTO_REFRESH_INTERVAL)
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        console.log('🔄 Auto-refresh dashboard data');
        this.loadDashboardData();
        this.loadAlerts();
      });
  }

  /**
   * Configure les listeners WebSocket
   */
  private setupWebSocketListeners(): void {
    // Connexion au WebSocket
    this.wsService.connect();

    // Vérifier l'état de connexion
    this.wsService.messages$
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          this.wsConnected = true;
        },
        error: () => {
          this.wsConnected = false;
        }
      });

    // S'abonner aux mises à jour des livraisons
    this.wsService.subscribeToAllDeliveries();

    // Écouter les mises à jour en temps réel
    this.wsService.getMessagesByType('delivery.updated')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        console.log('📦 Delivery updated:', message.data);
        this.updateStatsFromWebSocket(message.data);
      });

    // Écouter les nouvelles livraisons
    this.wsService.getMessagesByType('delivery.created')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        console.log('🆕 New delivery:', message.data);
        this.refreshStats();
      });

    // Écouter les changements de statut des livreurs
    this.wsService.getMessagesByType('driver.status.changed')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        console.log('👤 Driver status changed:', message.data);
        this.refreshStats();
      });
  }

  /**
   * Met à jour les stats à partir des événements WebSocket
   */
  private updateStatsFromWebSocket(data: any): void {
    if (!this.stats) return;

    // Sauvegarder l'état précédent
    this.previousStats = { ...this.stats };

    // Mettre à jour les statistiques en fonction du changement de statut
    switch (data.status) {
      case 'assigned':
        this.stats.readyToShip = Math.max(0, this.stats.readyToShip - 1);
        break;
      case 'in_progress':
        this.stats.inTransit++;
        break;
      case 'delivered':
        this.stats.inTransit = Math.max(0, this.stats.inTransit - 1);
        this.stats.delivered++;
        break;
      case 'failed':
        this.stats.inTransit = Math.max(0, this.stats.inTransit - 1);
        this.stats.failed++;
        break;
    }

    // Actualiser la liste des livraisons récentes
    this.dashboardService.getRecentDeliveries(8)
      .pipe(takeUntil(this.destroy$))
      .subscribe(deliveries => {
        this.recentDeliveries = deliveries;
      });

    this.lastUpdate = new Date();
  }

  /**
   * Rafraîchit toutes les statistiques
   */
  private refreshStats(): void {
    this.dashboardService.getStats()
      .pipe(takeUntil(this.destroy$))
      .subscribe(stats => {
        this.previousStats = this.stats;
        this.stats = stats;
        this.lastUpdate = new Date();
      });
  }

  /**
   * Rafraîchissement manuel
   */
  manualRefresh(): void {
    console.log('🔄 Manual refresh triggered');
    this.loadDashboardData();
    this.loadAlerts();
  }

  /**
   * Change la période de filtrage
   */
  changePeriod(period: 'today' | 'week' | 'month'): void {
    this.selectedPeriod = period;
    console.log(`📅 Period changed to: ${period}`);
    // TODO: Implémenter le filtrage par période
    this.loadDashboardData();
  }

  /**
   * Retourne la classe CSS pour le statut
   */
  getStatusClass(status: string): string {
    const statusClasses: { [key: string]: string } = {
      'pending': 'status-pending',
      'assigned': 'status-assigned',
      'in_progress': 'status-in-progress',
      'delivered': 'status-delivered',
      'failed': 'status-failed'
    };
    return statusClasses[status] || '';
  }

  /**
   * Retourne le label du statut
   */
  getStatusLabel(status: string): string {
    const statusLabels: { [key: string]: string } = {
      'pending': 'En attente',
      'assigned': 'Assignée',
      'in_progress': 'En cours',
      'delivered': 'Livrée',
      'failed': 'Échouée'
    };
    return statusLabels[status] || status;
  }

  /**
   * Formate le temps en heures/minutes
   */
  formatTime(minutes: number): string {
    if (minutes < 60) {
      return `${minutes} min`;
    }
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`;
  }

  /**
   * Calcule le pourcentage de changement
   */
  getChangePercentage(current: number, previous: number): number {
    if (!previous || previous === 0) return 0;
    return Math.round(((current - previous) / previous) * 100);
  }

  /**
   * Détermine si la valeur a augmenté
   */
  hasIncreased(current: number, previous: number): boolean {
    return current > previous;
  }

  /**
   * Détermine si la valeur a diminué
   */
  hasDecreased(current: number, previous: number): boolean {
    return current < previous;
  }

  /**
   * Retourne l'icône de tendance
   */
  getTrendIcon(current: number, previous: number): string {
    if (this.hasIncreased(current, previous)) return '📈';
    if (this.hasDecreased(current, previous)) return '📉';
    return '➡️';
  }

  /**
   * Formate le temps depuis la dernière mise à jour
   */
  getTimeSinceLastUpdate(): string {
    const seconds = Math.floor((new Date().getTime() - this.lastUpdate.getTime()) / 1000);
    
    if (seconds < 60) return `${seconds}s`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}min`;
    return `${Math.floor(seconds / 3600)}h`;
  }

  /**
   * Classe CSS pour le type d'alerte
   */
  getAlertClass(type: string): string {
    const alertClasses: { [key: string]: string } = {
      'warning': 'alert-warning',
      'error': 'alert-error',
      'info': 'alert-info',
      'success': 'alert-success'
    };
    return alertClasses[type] || 'alert-info';
  }
}