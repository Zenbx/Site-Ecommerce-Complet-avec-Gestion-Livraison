// src/app/features/deliveries/deliveries-list/deliveries-list.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import { DeliveriesService, DeliveryFilters } from '../deliveries.service';
import { DeliveryDriversService } from '../../delivery-drivers/delivery-drivers.service';
import { WebSocketService } from '../../../core/services/websocket.service';
import { Delivery, DeliveryStatus, DeliveryDriver } from '../models/delivery.model';

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

  // Modal d'assignation
  showAssignModal = false;
  selectedDelivery: Delivery | null = null;
  availableDrivers: DeliveryDriver[] = [];
  selectedDriverId: number | null = null;
  assigning = false;
  autoAssigning = false;

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
    private driversService: DeliveryDriversService,
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

    // Mise à jour d'une livraison
    this.wsService.getMessagesByType('delivery.updated')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        this.updateDeliveryInList(message.data);
      });

    // Nouvelle livraison créée
    this.wsService.getMessagesByType('delivery.created')
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        this.loadDeliveries();
      });

    // Livraison assignée (notification)
    this.wsService.getMessagesByType('delivery.assigned')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        console.log('📬 Livraison assignée:', message.data);
        this.updateDeliveryInList(message.data);
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

  /**
   * OUVRIR LE MODAL D'ASSIGNATION
   */
  openAssignModal(delivery: Delivery): void {
    console.log('🔓 Ouverture du modal pour la livraison:', delivery);
    this.selectedDelivery = delivery;
    this.selectedDriverId = null;
    this.showAssignModal = true;
    console.log('✅ Modal ouvert, showAssignModal:', this.showAssignModal);

    // Charger les livreurs disponibles
    this.driversService.getAvailableDrivers()
      .subscribe({
        next: (drivers) => {
          console.log('📦 Livreurs disponibles chargés:', drivers);
          this.availableDrivers = drivers;
        },
        error: (error) => {
          console.error('❌ Erreur lors du chargement des livreurs:', error);
          this.availableDrivers = [];
        }
      });
  }

  /**
   * FERMER LE MODAL
   */
  closeAssignModal(): void {
    this.showAssignModal = false;
    this.selectedDelivery = null;
    this.selectedDriverId = null;
    this.availableDrivers = [];
  }

  /**
   * ASSIGNATION MANUELLE
   */
  confirmAssignment(): void {
    if (!this.selectedDelivery || !this.selectedDriverId) {
      alert('Veuillez sélectionner un livreur');
      return;
    }

    this.assigning = true;

    this.deliveriesService.assignDeliveryManually(
      this.selectedDelivery.id,
      this.selectedDriverId
    ).subscribe({
      next: (updatedDelivery) => {
        console.log('✅ Livraison assignée avec succès');
        
        // Mettre à jour la livraison dans la liste
        this.updateDeliveryInList(updatedDelivery);
        
        // Afficher une notification de succès
        this.showSuccessNotification(
          `Livraison #${updatedDelivery.orderNumber} assignée à ${updatedDelivery.driver?.firstName} ${updatedDelivery.driver?.lastName}`
        );
        
        this.assigning = false;
        this.closeAssignModal();
      },
      error: (error) => {
        console.error('❌ Erreur lors de l\'assignation:', error);
        alert('Erreur lors de l\'assignation de la livraison');
        this.assigning = false;
      }
    });
  }

  /**
   * ASSIGNATION AUTOMATIQUE
   */
  autoAssignDelivery(delivery: Delivery): void {
    if (!confirm(`Voulez-vous assigner automatiquement la livraison #${delivery.orderNumber} au meilleur livreur disponible ?`)) {
      return;
    }

    this.autoAssigning = true;

    this.deliveriesService.assignDeliveryAutomatically(delivery.id)
      .subscribe({
        next: (result) => {
          console.log('🤖 Assignation automatique réussie');
          console.log(`   Livreur: ${result.driver.firstName} ${result.driver.lastName}`);
          console.log(`   Raison: ${result.reason}`);
          console.log(`   Score: ${result.score}`);
          
          // Mettre à jour la livraison dans la liste
          this.updateDeliveryInList(result.delivery);
          
          // Afficher une notification détaillée
          this.showSuccessNotification(
            `Livraison #${result.delivery.orderNumber} assignée automatiquement à ${result.driver.firstName} ${result.driver.lastName}. ${result.reason}`
          );
          
          this.autoAssigning = false;
        },
        error: (error) => {
          console.error('❌ Erreur lors de l\'assignation automatique:', error);
          alert('Erreur lors de l\'assignation automatique');
          this.autoAssigning = false;
        }
      });
  }

  /**
   * DÉSASSIGNER UNE LIVRAISON
   */
  unassignDelivery(delivery: Delivery): void {
    if (!confirm(`Voulez-vous retirer l'assignation de la livraison #${delivery.orderNumber} ?`)) {
      return;
    }

    this.deliveriesService.unassignDelivery(delivery.id)
      .subscribe({
        next: (updatedDelivery) => {
          console.log('✅ Livraison désassignée');
          this.updateDeliveryInList(updatedDelivery);
          this.showSuccessNotification(`Livraison #${updatedDelivery.orderNumber} désassignée`);
        },
        error: (error) => {
          console.error('❌ Erreur lors de la désassignation:', error);
          alert('Erreur lors de la désassignation');
        }
      });
  }

  /**
   * NOTIFICATION DE SUCCÈS
   */
  private showSuccessNotification(message: string): void {
    // Vous pouvez utiliser une bibliothèque de toast/notifications ici
    // Pour l'instant, on utilise une alerte simple
    alert(`✅ ${message}`);
  }

  // Méthodes existantes...

  viewDelivery(delivery: Delivery): void {
    this.router.navigate(['/deliveries', delivery.id]);
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

  canAssign(delivery: Delivery): boolean {
    return delivery.status === DeliveryStatus.PENDING;
  }

  canUnassign(delivery: Delivery): boolean {
    return delivery.status === DeliveryStatus.ASSIGNED;
  }

  canTrack(delivery: Delivery): boolean {
    return delivery.status === DeliveryStatus.IN_PROGRESS || 
           delivery.status === DeliveryStatus.ASSIGNED;
  }
}