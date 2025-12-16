// src/app/features/deliveries/delivery-details/delivery-details.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import { DeliveriesService } from '../deliveries.service';
import { Delivery, DeliveryStatus } from '../models/delivery.model';

@Component({
  selector: 'app-delivery-details',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './delivery-details.component.html',
  styleUrls: ['./delivery-details.component.scss']
})
export class DeliveryDetailsComponent implements OnInit, OnDestroy {
  delivery: Delivery | null = null;
  loading = true;
  error: string | null = null;
  private destroy$ = new Subject<void>();

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private deliveriesService: DeliveriesService
  ) {}

  ngOnInit(): void {
    this.route.params
      .pipe(takeUntil(this.destroy$))
      .subscribe(params => {
        const deliveryId = params['id'];
        if (deliveryId) {
          this.loadDelivery(deliveryId);
        }
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  /**
   * CHARGER LES DÉTAILS DE LA LIVRAISON
   */
  private loadDelivery(deliveryId: number): void {
    this.loading = true;
    this.error = null;

    this.deliveriesService.getDelivery(deliveryId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (delivery) => {
          console.log('📦 Livraison chargée:', delivery);
          this.delivery = delivery;
          this.loading = false;
        },
        error: (error) => {
          console.error('❌ Erreur lors du chargement de la livraison:', error);
          this.error = 'Impossible de charger la livraison';
          this.loading = false;
        }
      });
  }

  /**
   * NAVIGUER VERS LE SUIVI
   */
  goToTracking(): void {
    if (this.delivery) {
      this.router.navigate(['/deliveries/track', this.delivery.id]);
    }
  }

  /**
   * NAVIGUER VERS LA PREUVE DE LIVRAISON
   */
  goToProof(): void {
    if (this.delivery) {
      this.router.navigate(['/deliveries/proof', this.delivery.id]);
    }
  }

  /**
   * RETOURNER À LA LISTE
   */
  goBack(): void {
    this.router.navigate(['/deliveries']);
  }

  /**
   * OBTENIR LE LABEL DU STATUT
   */
  getStatusLabel(status: DeliveryStatus): string {
    const labels: { [key: string]: string } = {
      [DeliveryStatus.PENDING]: 'En attente',
      [DeliveryStatus.ASSIGNED]: 'Assignée',
      [DeliveryStatus.IN_PROGRESS]: 'En cours',
      [DeliveryStatus.DELIVERED]: 'Livrée',
      [DeliveryStatus.FAILED]: 'Échouée',
      [DeliveryStatus.CANCELLED]: 'Annulée'
    };
    return labels[status] || status;
  }

  /**
   * OBTENIR LA CLASSE DU STATUT
   */
  getStatusClass(status: DeliveryStatus): string {
    const classes: { [key: string]: string } = {
      [DeliveryStatus.PENDING]: 'status-pending',
      [DeliveryStatus.ASSIGNED]: 'status-assigned',
      [DeliveryStatus.IN_PROGRESS]: 'status-in-progress',
      [DeliveryStatus.DELIVERED]: 'status-delivered',
      [DeliveryStatus.FAILED]: 'status-failed',
      [DeliveryStatus.CANCELLED]: 'status-cancelled'
    };
    return classes[status] || '';
  }

  /**
   * OBTENIR LA CLASSE DE PRIORITÉ
   */
  getPriorityClass(priority: string): string {
    return `priority-${priority}`;
  }

  /**
   * FORMATER LA DATE
   */
  formatDate(date: string | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('fr-FR');
  }

  /**
   * VÉRIFIER SI ON PEUT AFFICHER LE TRACKING
   */
  canTrack(): boolean {
    if (!this.delivery) return false;
    return this.delivery.status === DeliveryStatus.IN_PROGRESS || 
           this.delivery.status === DeliveryStatus.ASSIGNED;
  }

  /**
   * VÉRIFIER SI ON PEUT AFFICHER LA PREUVE
   */
  canShowProof(): boolean {
    if (!this.delivery) return false;
    return this.delivery.status === DeliveryStatus.DELIVERED || 
           this.delivery.status === DeliveryStatus.FAILED;
  }
}
