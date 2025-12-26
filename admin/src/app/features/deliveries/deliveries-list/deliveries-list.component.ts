// src/app/features/deliveries/deliveries-list/deliveries-list.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import { DeliveriesService, DeliveryFilters } from '../../../core/services/deliveries.service';
import { DeliveryDriversService, DeliveryDriver } from '../../../core/services/delivery-drivers.service';
import { WebSocketService } from '../../../core/services/websocket.service';
import { Delivery } from '../../../core/models/delivery.model';
import { RouterModule } from '@angular/router';
@Component({
  selector: 'app-deliveries-list',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
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
  selectedStatus  = '';
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
    { value: 'pending', label: 'En attente' },
    { value: 'assigned', label: 'Assignée' },
    { value: 'in_progress', label: 'En cours' },
    { value: 'delivered', label: 'Livrée' },
    { value: 'failed', label: 'Échouée' }
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

  // Ajoutez cette fonction :
  refresh(): void {
    console.log('Actualisation des livraisons...');
    this.currentPage = 1; // On revient à la première page
    this.loadDeliveries(); // Appelle votre méthode existante qui récupère les données
  }

  loadDeliveries(): void {
    this.loading = true;
    const filters: DeliveryFilters = {};

    if (this.searchTerm) {
      filters.search = this.searchTerm;
    }

    if (this.selectedStatus) {
      filters.status = this.selectedStatus as string;
    }

    this.deliveriesService.getDeliveries(filters)
      .subscribe({
        next: (response) => {
          this.deliveries = response.data;
          console.log('Livraisons chargées:', this.deliveries);
          
          this.totalDeliveries = response.meta.total;
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

    this.deliveriesService.assignDriver(
      this.selectedDelivery.id,
      this.selectedDriverId
    ).subscribe({
      next: (updatedDelivery) => {
        console.log('✅ Livraison assignée avec succès');
        
        // Mettre à jour la livraison dans la liste
        this.updateDeliveryInList(updatedDelivery);
        
        // Afficher une notification de succès
        this.showSuccessNotification(
          `Livraison #${updatedDelivery.order.order_number} assignée à ${updatedDelivery.delivery_person?.name}`
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
    if (!confirm(`Voulez-vous assigner automatiquement la livraison #${delivery.order.order_number} au meilleur livreur disponible ?`)) {
      return;
    }

    this.autoAssigning = true;

    this.deliveriesService.autoAssignDriver(delivery.id)
      .subscribe({
        next: (result) => {
         /// console.log('🤖 Assignation automatique réussie');
         // console.log(`   Livreur: ${result.driver.firstName} ${result.driver.lastName}`);
         // console.log(`   Raison: ${result.reason}`);
         // console.log(`   Score: ${result.score}`);
          
          // Mettre à jour la livraison dans la liste
          this.updateDeliveryInList(result);
          
          // Afficher une notification détaillée
          this.showSuccessNotification(
            `Livraison #${result.order.order_number} assignée automatiquement à ${result.delivery_person?.name}`
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
    if (!confirm(`Voulez-vous retirer l'assignation de la livraison #${delivery.order.order_number} ?`)) {
      return;
    }

    this.deliveriesService.removeDriver(delivery.id)
      .subscribe({
        next: (updatedDelivery) => {
          console.log('✅ Livraison désassignée');
          this.updateDeliveryInList(updatedDelivery);
          this.showSuccessNotification(`Livraison #${updatedDelivery.order.order_number} désassignée`);
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

  getStatusClass(status: string): string {
    const statusClasses: { [key: string]: string } = {
      'pending': 'status-pending',
      'assigned': 'status-assigned',
      'in_progress': 'status-in-progress',
      'delivered': 'status-delivered',
      'failed': 'status-failed',
      'cancelled': 'status-cancelled'
    };
    return statusClasses[status] || '';
  }

  getStatusLabel(status: string): string {
    const statusLabels: { [key: string]: string } = {
      'PENDING': 'En attente',
      'ASSIGNED': 'Assignée',
      'IN_TRANSIT': 'En cours',
      'DELIVERED': 'Livrée',
      'FAILED': 'Échouée',
      'CANCELLED': 'Annulée'
    };
    return statusLabels[status].toUpperCase() || status;
  }

  getTotalPages(): number {
    return Math.ceil(this.totalDeliveries / this.perPage);
  }

  canAssign(delivery: Delivery): boolean {
    return delivery.status === 'pending';
  }

  canUnassign(delivery: Delivery): boolean {
    return delivery.status === 'assigned';
  }

  canTrack(delivery: Delivery): boolean {
    return delivery.status === 'in_progress' || 
           delivery.status === 'assigned';
  }

  // Méthodes communes à ajouter dans les composants

getPriorityIcon(priority: string): string {
  const icons: Record<string, string> = {
    'high': 'priority_high',
    'medium': 'remove',
    'low': 'arrow_downward'
  };
  return icons[priority] || 'remove';
}

getPriorityLabel(priority: string): string {
  const labels: Record<string, string> = {
    'high': 'Urgente',
    'medium': 'Normale',
    'low': 'Basse'
  };
  return labels[priority] || priority;
}

getProofTypeIcon(type: string): string {
  const icons: Record<string, string> = {
    'SIGNATURE': 'draw',
    'PHOTO': 'photo_camera',
    'QR_CODE': 'qr_code'
  };
  return icons[type] || 'image';
}

getProofStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    'PENDING': 'En attente',
    'VALIDATED': 'Validée',
    'REJECTED': 'Rejetée'
  };
  return labels[status] || status;
}
}