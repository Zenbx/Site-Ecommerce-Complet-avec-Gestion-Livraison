// src/app/features/deliveries/deliveries-list/deliveries-list.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import { DeliveriesService, DeliveryFilters } from '../deliveries.service';
import { WebSocketService } from '../../../core/services/websocket.service';
import { Delivery, DeliveryStatus } from '../models/delivery.model';

@Component({
  selector: 'app-deliveries-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './deliveries-list.component.html',
  styleUrls: ['./deliveries-list.component.scss']
})

export class DeliveriesListComponent implements OnInit, OnDestroy {
  deliveries: Delivery[] = [];
  loading = true;
  currentPage = 1;
  perPage = 15;
  totalDeliveries = 0;
  searchTerm = '';
  selectedStatus: DeliveryStatus | '' = '';
  private destroy$ = new Subject<void>();

  statusOptions = [
    { value: '', label: 'Tous les statuts' },
    { value: DeliveryStatus.PENDING, label: 'En attente' },
    { value: DeliveryStatus.ASSIGNED, label: 'Assignée' },
    { value: DeliveryStatus.IN_PROGRESS, label: 'En cours' },
    { value: DeliveryStatus.DELIVERED, label: 'Livrée' },
    { value: DeliveryStatus.FAILED, label: 'Échouée' }
  ];

  constructor(
    private deliveriesService: DeliveriesService,
    private wsService: WebSocketService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadDeliveries();
    this.setupWebSocketListeners();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private setupWebSocketListeners(): void {
    this.wsService.connect();
    this.wsService.subscribeToAllDeliveries();

    this.wsService.getMessagesByType('delivery.updated')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        this.updateDeliveryInList(message.data);
      });

    this.wsService.getMessagesByType('delivery.created')
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        this.loadDeliveries();
      });
  }

  private updateDeliveryInList(updatedDelivery: Delivery): void {
    const index = this.deliveries.findIndex(d => d.id === updatedDelivery.id);
    if (index !== -1) {
      this.deliveries[index] = { ...this.deliveries[index], ...updatedDelivery };
    }
  }

  loadDeliveries(): void {
    this.loading = true;
    const filters: DeliveryFilters = {};

    if (this.searchTerm) {
      filters.search = this.searchTerm;
    }

    if (this.selectedStatus) {
      filters.status = this.selectedStatus as DeliveryStatus;
    }

    this.deliveriesService.getDeliveries(this.currentPage, this.perPage, filters)
      .subscribe({
        next: (response) => {
          this.deliveries = response.data;
          this.totalDeliveries = response.total;
          this.loading = false;
        },
        error: (error) => {
          console.error('Error loading deliveries:', error);
          this.loading = false;
        }
      });
  }

  onSearch(): void {
    this.currentPage = 1;
    this.loadDeliveries();
  }

  onFilterChange(): void {
    this.currentPage = 1;
    this.loadDeliveries();
  }

  onPageChange(page: number): void {
    this.currentPage = page;
    this.loadDeliveries();
  }

  viewDelivery(delivery: Delivery): void {
    this.router.navigate(['/deliveries', delivery.id]);
  }

  assignDelivery(delivery: Delivery): void {
    this.router.navigate(['/deliveries/assign', delivery.id]);
  }

  trackDelivery(delivery: Delivery): void {
    this.router.navigate(['/deliveries/track', delivery.id]);
  }

  getStatusClass(status: DeliveryStatus): string {
    const statusClasses: { [key: string]: string } = {
      [DeliveryStatus.PENDING]: 'status-pending',
      [DeliveryStatus.ASSIGNED]: 'status-assigned',
      [DeliveryStatus.IN_PROGRESS]: 'status-in-progress',
      [DeliveryStatus.DELIVERED]: 'status-delivered',
      [DeliveryStatus.FAILED]: 'status-failed',
      [DeliveryStatus.CANCELLED]: 'status-cancelled'
    };
    return statusClasses[status] || '';
  }

  getStatusLabel(status: DeliveryStatus): string {
    const statusLabels: { [key: string]: string } = {
      [DeliveryStatus.PENDING]: 'En attente',
      [DeliveryStatus.ASSIGNED]: 'Assignée',
      [DeliveryStatus.IN_PROGRESS]: 'En cours',
      [DeliveryStatus.DELIVERED]: 'Livrée',
      [DeliveryStatus.FAILED]: 'Échouée',
      [DeliveryStatus.CANCELLED]: 'Annulée'
    };
    return statusLabels[status] || status;
  }

  getPriorityClass(priority: string): string {
    return `priority-${priority}`;
  }

  getTotalPages(): number {
    return Math.ceil(this.totalDeliveries / this.perPage);
  }
}