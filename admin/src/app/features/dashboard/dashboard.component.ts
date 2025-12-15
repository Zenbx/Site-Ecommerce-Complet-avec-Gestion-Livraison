// src/app/features/dashboard/dashboard.component.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Subject, takeUntil } from 'rxjs';
import { DashboardService } from './dashboard.service';
import { WebSocketService } from '../../core/services/websocket.service';
import { DashboardStats, RecentDelivery, DriverPerformance } from './models/dashboard-stats.model';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [
    CommonModule
  ],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit, OnDestroy {
  stats: DashboardStats | null = null;
  recentDeliveries: RecentDelivery[] = [];
  driverPerformance: DriverPerformance[] = [];
  loading = true;
  private destroy$ = new Subject<void>();

  constructor(
    private dashboardService: DashboardService,
    private wsService: WebSocketService
  ) {}

  ngOnInit(): void {
    this.loadDashboardData();
    this.setupWebSocketListeners();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private loadDashboardData(): void {
    this.loading = true;

    // Charger les statistiques
    this.dashboardService.getStats()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (stats) => {
          this.stats = stats;
          this.loading = false;
        },
        error: (error) => {
          console.error('Error loading stats:', error);
          this.loading = false;
        }
      });

    // Charger les livraisons récentes
    this.dashboardService.getRecentDeliveries()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (deliveries) => {
          this.recentDeliveries = deliveries;
        },
        error: (error) => {
          console.error('Error loading recent deliveries:', error);
        }
      });

    // Charger les performances des livreurs
    this.dashboardService.getDriverPerformance()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (performance) => {
          this.driverPerformance = performance;
        },
        error: (error) => {
          console.error('Error loading driver performance:', error);
        }
      });
  }

  private setupWebSocketListeners(): void {
    // Connexion au WebSocket
    this.wsService.connect();

    // S'abonner aux mises à jour des livraisons
    this.wsService.subscribeToAllDeliveries();

    // Écouter les mises à jour en temps réel
    this.wsService.getMessagesByType('delivery.updated')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        this.updateStatsFromWebSocket(message.data);
      });

    // Écouter les nouvelles livraisons
    this.wsService.getMessagesByType('delivery.created')
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        this.refreshStats();
      });

    // Écouter les changements de statut des livreurs
    this.wsService.getMessagesByType('driver.status.changed')
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        this.refreshStats();
      });
  }

  private updateStatsFromWebSocket(data: any): void {
    if (!this.stats) return;

    // Mettre à jour les statistiques en fonction du changement de statut
    switch (data.status) {
      case 'assigned':
        this.stats.readyToShip--;
        break;
      case 'in_progress':
        this.stats.inTransit++;
        break;
      case 'delivered':
        this.stats.inTransit--;
        this.stats.delivered++;
        break;
      case 'failed':
        this.stats.inTransit--;
        this.stats.failed++;
        break;
    }

    // Actualiser la liste des livraisons récentes
    this.dashboardService.getRecentDeliveries(10)
      .pipe(takeUntil(this.destroy$))
      .subscribe(deliveries => {
        this.recentDeliveries = deliveries;
      });
  }

  private refreshStats(): void {
    this.dashboardService.getStats()
      .pipe(takeUntil(this.destroy$))
      .subscribe(stats => {
        this.stats = stats;
      });
  }

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

  formatTime(minutes: number): string {
    if (minutes < 60) {
      return `${minutes} min`;
    }
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}min`;
  }
}