// src/app/features/orders/orders-list/orders-list.component.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Subject, takeUntil } from 'rxjs';
import { OrdersService, OrderFilters } from '../../core/services/order.service';
import { Order, OrderStatus } from '../../core/models/order.model';
import { DeliveryDriver } from '../../core/models/delivery.model';

@Component({
  selector: 'app-orders-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './orders-list.component.html',
  styleUrls: ['./orders-list.component.scss']
})
export class OrdersListComponent implements OnInit, OnDestroy {
  orders: Order[] = [];
  loading = true;
  currentPage = 1;
  perPage = 12;
  totalOrders = 0;
  searchTerm = '';
  selectedStatus: OrderStatus | '' = '';

  // Modals
  showDetailsModal = false;
  showAssignModal = false;
  selectedOrder: Order | null = null;
  availableDrivers: DeliveryDriver[] = [];
  selectedDriverId: number | null = null;
  assigning = false;

  private destroy$ = new Subject<void>();

  // Options de filtre
  assignedFilter: 'ALL' | 'ASSIGNED' | 'UNASSIGNED' = 'ALL';


  constructor(public ordersService: OrdersService) { }

  ngOnInit(): void {
    this.loadOrders();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  /**
   * CHARGER LES COMMANDES
   */
  loadOrders(): void {
    this.loading = true;
    const filters: OrderFilters = {};

    if (this.searchTerm) {
      filters.search = this.searchTerm;
    }

    if (this.selectedStatus) {
      filters.status = this.selectedStatus as OrderStatus;
    }

    this.ordersService.getOrders(this.currentPage, this.perPage, filters)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          let orders = response.data;

          // 🔎 FILTRE ASSIGNÉ / NON ASSIGNÉ
          if (this.assignedFilter === 'ASSIGNED') {
            orders = orders.filter(o => o.delivery && o.delivery.delivery_person);
          }

          if (this.assignedFilter === 'UNASSIGNED') {
            orders = orders.filter(o => !o.delivery || !o.delivery.delivery_person);
          }

          this.orders = orders;
          this.totalOrders = response.meta?.total || orders.length;
          this.loading = false;
        },
        error: (error) => {
          console.error('❌ Erreur lors du chargement des commandes:', error);
          this.loading = false;
        }
      });
  }

  onSearch(): void {
    this.currentPage = 1;
    this.loadOrders();
  }

  onFilterChange(): void {
    this.currentPage = 1;
    this.loadOrders();
  }

  onPageChange(page: number): void {
    this.currentPage = page;
    this.loadOrders();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  getTotalPages(): number {
    return Math.ceil(this.totalOrders / this.perPage);
  }

  /**
   * OUVRIR LE MODAL DES DÉTAILS
   */
  openDetailsModal(order: Order): void {
    this.selectedOrder = order;
    this.showDetailsModal = true;

    // Recharger les détails complets si nécessaire
    if (!order.delivery) {
      this.ordersService.getOrder(order.id)
        .pipe(takeUntil(this.destroy$))
        .subscribe({
          next: (fullOrder) => {
            this.selectedOrder = fullOrder;
          },
          error: (error) => {
            console.error('❌ Erreur chargement détails:', error);
          }
        });
    }
  }

  closeDetailsModal(): void {
    this.showDetailsModal = false;
    this.selectedOrder = null;
  }

  /**
   * OUVRIR LE MODAL D'ASSIGNATION
   */
  openAssignModal(order: Order): void {
    this.selectedOrder = order;
    this.selectedDriverId = null;
    this.showAssignModal = true;

    // Charger les livreurs disponibles
    this.ordersService.getAvailableDeliveryPersons()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (drivers) => {
          console.log('🚚 Livreurs disponibles reçus:', drivers);
          console.log('📊 Type:', typeof drivers, 'Array?', Array.isArray(drivers));
          this.availableDrivers = drivers;
          console.log('✅ Livreurs disponibles chargés:', drivers.length);
        },
        error: (error) => {
          console.error('❌ Erreur chargement livreurs:', error);
          this.availableDrivers = [];
        }
      });
  }

  closeAssignModal(): void {
    this.showAssignModal = false;
    this.selectedOrder = null;
    this.selectedDriverId = null;
  }

  /**
   * CONFIRMER L'ASSIGNATION
   */
  confirmAssignment(): void {
    if (!this.selectedOrder || !this.selectedDriverId) {
      alert('Veuillez sélectionner un livreur');
      return;
    }

    this.assigning = true;

    this.ordersService.assignDeliveryPerson(
      this.selectedOrder.id,
      {
        delivery_person_id: this.selectedDriverId,
        delivery_address: this.selectedOrder.client.address || ''
      }
    )
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (updatedOrder) => {
          console.log('✅ Livreur assigné avec succès');

          // Mettre à jour la commande dans la liste
          const index = this.orders.findIndex(o => o.id === updatedOrder.id);
          if (index !== -1) {
            this.orders[index] = updatedOrder;
          }

          alert(`✅ Livreur assigné à la commande ${updatedOrder.order_number}`);
          this.assigning = false;
          this.closeAssignModal();

          // Recharger la liste pour retirer les commandes assignées
          this.loadOrders();
        },
        error: (error) => {
          console.error('❌ Erreur lors de l\'assignation:', error);
          alert('Erreur lors de l\'assignation du livreur');
          this.assigning = false;
        }
      });
  }

  /**
   * VÉRIFIER SI UNE COMMANDE PEUT ÊTRE ASSIGNÉE
   */
  canAssign(order: Order): boolean {
    // Peut être assignée si pas de delivery ou si delivery sans livreur
    return !order.delivery || !order.delivery.delivery_person;
  }

  /**
   * CALCULER LE NOMBRE TOTAL D'ARTICLES
   */
  getTotalItemsCount(order: Order): number {
    return order.items.reduce((sum, item) => sum + item.quantity, 0);
  }

  /**
   * FORMATER LA DATE
   */
  formatDate(date: string): string {
    return new Date(date).toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  /**
   * OBTENIR LES INITIALES DU LIVREUR
   */
  getDriverInitials(driver: any): string {
    if (driver.firstName && driver.lastName) {
      return `${driver.firstName.charAt(0)}${driver.lastName.charAt(0)}`;
    }
    if (driver.name) {
      const nameParts = driver.name.split(' ');
      if (nameParts.length >= 2) {
        return `${nameParts[0].charAt(0)}${nameParts[1].charAt(0)}`;
      }
      return driver.name.substring(0, 2).toUpperCase();
    }
    return '??';
  }

  /**
   * OBTENIR LE NOM COMPLET DU LIVREUR
   */
  getDriverFullName(driver: any): string {
    if (driver.firstName && driver.lastName) {
      return `${driver.firstName} ${driver.lastName}`;
    }
    return driver.name || 'Nom inconnu';
  }
}