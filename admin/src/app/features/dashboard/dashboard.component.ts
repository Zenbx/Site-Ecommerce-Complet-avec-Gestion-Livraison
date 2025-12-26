import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Subject, Subscription, interval } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { DashboardService } from '../../core/services/dashboard.service';
import {
  DashboardStats,
  DriverPerformance,
  DashboardAlert,
  DeliveryStatus,
  RecentActivity
} from '../../core/models/dashboard-stats.model';


@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit, OnDestroy {
  // Données du dashboard
  stats?: DashboardStats;
  previousStats?: DashboardStats;
  recentActivities: RecentActivity[] = [];
  driverPerformance: DriverPerformance[] = [];
  alerts: DashboardAlert[] = [];

  // États de l'interface
  loading = false;
  wsConnected = false;
  lastUpdateTime: Date | null = null;

  // Exposer Math pour le template
  Math = Math;

  // Gestion des subscriptions
  private destroy$ = new Subject<void>();
  private autoRefreshSubscription?: Subscription;
  private wsSimulationInterval?: any;

  constructor(private dashboardService: DashboardService) { }

  ngOnInit(): void {
    this.loadDashboardData();
    this.startAutoRefresh();
    this.simulateWebSocketConnection();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();

    if (this.autoRefreshSubscription) {
      this.autoRefreshSubscription.unsubscribe();
    }

    if (this.wsSimulationInterval) {
      clearInterval(this.wsSimulationInterval);
    }
  }

  /**
   * Charge toutes les données du dashboard
   */
  loadDashboardData(): void {
    this.loading = true;

    this.dashboardService.getDashboardData('today')
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (data) => {
          this.stats = data.stats;
          this.previousStats = data.previousStats;
          this.recentActivities = data.recentActivities;
          this.driverPerformance = data.driverPerformance;
          this.lastUpdateTime = new Date();
          this.loading = false;
          
          console.log('✅ Dashboard data loaded:', data);
          
          // Vérifier les alertes
          this.checkForAlerts();
        },
        error: (error) => {
          console.error('Erreur lors du chargement du dashboard:', error);
          this.loading = false;
          this.addAlert('error', 'Erreur lors du chargement des données');
        }
      });
  }

  /**
   * Rafraîchissement manuel déclenché par l'utilisateur
   */
  manualRefresh(): void {
    if (this.loading) return;
    this.loadDashboardData();
  }

  /**
   * Démarre le rafraîchissement automatique toutes les 30 secondes
   */
  private startAutoRefresh(): void {
    this.autoRefreshSubscription = this.dashboardService
      .startAutoRefresh(30000)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (data) => {
          // Mise à jour silencieuse sans loading
          this.stats = data.stats;
          this.previousStats = data.previousStats;
          this.recentActivities = data.recentActivities;
          this.driverPerformance = data.driverPerformance;
          this.lastUpdateTime = new Date();
          this.checkForAlerts();
        },
        error: (error) => {
          console.error('Erreur auto-refresh:', error);
        }
      });
  }

  /**
   * Simule une connexion WebSocket (remplacer par vraie implémentation)
   */
  private simulateWebSocketConnection(): void {
    // Connexion simulée après 1 seconde
    setTimeout(() => {
      this.wsConnected = true;
    }, 1000);

    // Simulation de déconnexion/reconnexion aléatoire
    this.wsSimulationInterval = setInterval(() => {
      // 95% de chance de rester connecté
      this.wsConnected = Math.random() > 0.05;
    }, 10000);
  }

  /**
   * Retourne le temps écoulé depuis la dernière mise à jour
   */
  getTimeSinceLastUpdate(): string {
    if (!this.lastUpdateTime) return 'jamais';

    const now = new Date();
    const diffMs = now.getTime() - this.lastUpdateTime.getTime();
    const diffSeconds = Math.floor(diffMs / 1000);
    const diffMinutes = Math.floor(diffSeconds / 60);

    if (diffSeconds < 10) return 'à l\'instant';
    if (diffSeconds < 60) return `il y a ${diffSeconds}s`;
    if (diffMinutes < 60) return `il y a ${diffMinutes}min`;

    const diffHours = Math.floor(diffMinutes / 60);
    return `il y a ${diffHours}h`;
  }

  /**
   * Vérifie si une valeur a augmenté
   */
  hasIncreased(current: number, previous: number): boolean {
    return current > previous;
  }

  /**
   * Vérifie si une valeur a diminué
   */
  hasDecreased(current: number, previous: number): boolean {
    return current < previous;
  }

  /**
   * Retourne l'icône de tendance (flèche haut/bas)
   */
  getTrendIcon(current: number, previous: number): string {
    if (current > previous) return '↑';
    if (current < previous) return '↓';
    return '→';
  }

  /**
   * Calcule le pourcentage de changement entre deux valeurs
   */
  getChangePercentage(current: number, previous: number): number {
    if (previous === 0) return current > 0 ? 100 : 0;
    const change = ((current - previous) / previous) * 100;
    return Math.round(change * 10) / 10; // Arrondi à 1 décimale
  }

  /**
   * Retourne la classe CSS pour un statut de livraison
   */
  getStatusClass(status: DeliveryStatus): string {
    const statusClasses: Record<DeliveryStatus, string> = {
      'ready_to_ship': 'status-warning',
      'in_transit': 'status-info',
      'delivered': 'status-success',
      'failed': 'status-error',
      'returned': 'status-error'
    };
    return statusClasses[status] || 'status-default';
  }

  /**
   * Retourne le libellé français pour un statut
   */
  getStatusLabel(status: DeliveryStatus): string {
    const statusLabels: Record<DeliveryStatus, string> = {
      'ready_to_ship': 'Prête',
      'in_transit': 'En transit',
      'delivered': 'Livrée',
      'failed': 'Échec',
      'returned': 'Retournée'
    };
    return statusLabels[status] || status;
  }

  /**
   * Formate un temps en minutes vers un format lisible
   */
  formatTime(minutes: number | undefined): string {
    if (minutes === undefined || minutes === null || isNaN(minutes)) {
      return 'N/A';
    }

    if (minutes < 60) {
      return `${Math.round(minutes)}min`;
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = Math.round(minutes % 60);

    if (remainingMinutes === 0) {
      return `${hours}h`;
    }

    return `${hours}h${remainingMinutes}min`;
  }

/**
 * Vérifie et génère des alertes selon les statistiques
 */
private checkForAlerts(): void {
  if (!this.stats) return;

  // Réinitialiser les alertes
  this.alerts = [];

  // Alerte si trop de commandes prêtes à expédier
  if (this.stats.deliveries.assigned > 50) {
    this.addAlert('warning', `${this.stats.deliveries.assigned} commandes en attente d'expédition`);
  }

  // Alerte si taux d'échec élevé
  const totalOrders = this.stats.deliveries.delivered + this.stats.deliveries.failed;
  if (totalOrders > 0) {
    const failureRate = (this.stats.deliveries.failed / totalOrders) * 100;
    if (failureRate > 10) {
      this.addAlert('error', `Taux d'échec élevé: ${Math.round(failureRate)}%`);
    }
  }

  // // Alerte si délai moyen trop long
  // if (this.dashboardService. > 180) { // Plus de 3h
  //   this.addAlert('warning', `Délai moyen de livraison élevé: ${this.formatTime(this.driverPerformance.averageTime)}`);
  // }

  // Garder seulement les 3 alertes les plus importantes
  this.alerts = this.alerts.slice(0, 3);
}

/**
 * Ajoute une alerte à la liste
 */
private addAlert(type: 'info' | 'warning' | 'error', message: string): void {
  this.alerts.push({
    type,
    message,
    timestamp: new Date()
  });
}
}