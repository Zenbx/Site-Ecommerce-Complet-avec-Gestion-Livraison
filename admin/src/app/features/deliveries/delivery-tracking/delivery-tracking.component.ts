import { Component, OnInit, OnDestroy, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject, takeUntil, interval } from 'rxjs';

import { MapComponent } from '../../map/map.component';
import { DeliveriesService } from '../../../core/services/deliveries.service';
import { WebSocketService } from '../../../core/services/websocket.service';
import { Delivery } from '../../../core/models/delivery.model';
import { Driver } from '../../../core/models/driver.model';

/**
 * Interface pour la position GPS du livreur
 */
interface DriverLocation {
  latitude: number;
  longitude: number;
  speed: number;
  heading: number;
  timestamp: string;
  accuracy: number;
}

/**
 * Composant de suivi en temps réel d'une livraison
 * 
 * Affiche :
 * - La carte avec le livreur et la destination
 * - Les informations de la livraison
 * - Le statut en temps réel
 * - L'historique
 */
@Component({
  selector: 'app-delivery-tracking',
  standalone: true,
  imports: [CommonModule, MapComponent],
  templateUrl: './delivery-tracking.component.html',
  styleUrls: ['./delivery-tracking.component.scss']
})
export class DeliveryTrackingComponent implements OnInit, OnDestroy {
  @ViewChild(MapComponent) mapComponent!: MapComponent;

  // ============================================================================
  // PROPRIÉTÉS PUBLIQUES
  // ============================================================================

  delivery: Delivery | null = null;
  driverLocation: DriverLocation | null = null;
  loading = true;
  error: string | null = null;
  
  wsConnected = false;
  lastUpdateTime: Date | null = null;

  // ============================================================================
  // PROPRIÉTÉS PRIVÉES
  // ============================================================================

  private destroy$ = new Subject<void>();

  // ============================================================================
  // CONSTRUCTOR
  // ============================================================================

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private deliveriesService: DeliveriesService,
    private wsService: WebSocketService
  ) {}

  // ============================================================================
  // LIFECYCLE HOOKS
  // ============================================================================

  ngOnInit(): void {
    this.route.params
      .pipe(takeUntil(this.destroy$))
      .subscribe(params => {
        const deliveryId = +params['id'];
        if (deliveryId) {
          this.loadDelivery(deliveryId);
          this.setupWebSocket(deliveryId);
        }
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    
    // Déconnecter le WebSocket
    if (this.wsConnected) {
      this.wsService.disconnect();
    }
  }

  // ============================================================================
  // CHARGEMENT DES DONNÉES
  // ============================================================================

  /**
   * Charge les informations de la livraison
   */
  public loadDelivery(deliveryId: number): void {
    this.loading = true;
    this.error = null;

    this.deliveriesService.getDelivery(deliveryId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (delivery) => {
          console.log('📦 Livraison chargée:', delivery);
          this.delivery = delivery;
          this.loading = false;

          // Si le livreur a déjà une position, l'initialiser
          if (delivery.delivery_person?.current_latitude && delivery.delivery_person?.current_longitude) {
            this.driverLocation = {
              latitude: parseFloat(delivery.delivery_person.current_latitude),
              longitude: parseFloat(delivery.delivery_person.current_longitude),
              speed: 0,
              heading: 0,
              timestamp: delivery.delivery_person.last_location_update || new Date().toISOString(),
              accuracy: 10
            };
            this.lastUpdateTime = new Date(delivery.delivery_person.last_location_update || Date.now());
          }
        },
        error: (error) => {
          console.error('❌ Erreur lors du chargement de la livraison:', error);
          this.error = 'Impossible de charger la livraison';
          this.loading = false;
        }
      });
  }

  // ============================================================================
  // WEBSOCKET - SUIVI EN TEMPS RÉEL
  // ============================================================================

  /**
   * Configure la connexion WebSocket pour le suivi temps réel
   */
  private setupWebSocket(deliveryId: number): void {
    this.wsService.connect();
    this.wsConnected = true;

    // Souscrire au canal de la livraison
    this.wsService.send({
      type: 'subscribe',
      channel: `delivery.${deliveryId}`
    });

    // Souscrire au canal du livreur si disponible
    if (this.delivery?.delivery_person?.id) {
      this.wsService.send({
        type: 'subscribe',
        channel: `delivery-person.${this.delivery.delivery_person.id}`
      });
    }

    // Écouter les mises à jour de position GPS
    this.wsService.getMessagesByType('delivery-person.location.updated')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        console.log('📍 Position GPS reçue:', message.data);

        if (this.delivery?.delivery_person?.id && 
            message.data.delivery_person_id === this.delivery.delivery_person.id) {
          this.updateDriverLocation(message.data);
        }
      });

    // Écouter les mises à jour de statut de livraison
    this.wsService.getMessagesByType('delivery.status.updated')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        console.log('📬 Statut mis à jour:', message.data);
        
        if (this.delivery && message.data.delivery_id === this.delivery.id) {
          this.delivery.status = message.data.status;
          
          // Mettre à jour delivered_at si livraison terminée
          if (message.data.status === 'delivered' && message.data.delivered_at) {
            this.delivery.delivered_at = message.data.delivered_at;
          }
        }
      });

    // Écouter la confirmation de livraison
    this.wsService.getMessagesByType('delivery.completed')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        if (this.delivery && message.data.delivery_id === this.delivery.id) {
          console.log('✅ Livraison terminée !');
          this.showNotification('Livraison terminée avec succès !', 'success');
        }
      });

    // Monitorer la connexion WebSocket (afficher warning si pas de signal)
    interval(30000) // Toutes les 30 secondes
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        if (this.lastUpdateTime) {
          const timeSinceLastUpdate = Date.now() - this.lastUpdateTime.getTime();
          
          if (timeSinceLastUpdate > 60000) { // Plus de 1 minute
            console.warn('⚠️ Pas de mise à jour GPS depuis plus de 1 minute');
          }
        }
      });
  }

  /**
   * Met à jour la position du livreur
   */
  private updateDriverLocation(locationData: any): void {
    this.driverLocation = {
      latitude: locationData.latitude,
      longitude: locationData.longitude,
      speed: locationData.speed || 0,
      heading: locationData.heading || 0,
      timestamp: locationData.timestamp || new Date().toISOString(),
      accuracy: locationData.accuracy || 10
    };

    this.lastUpdateTime = new Date();

    // Mettre à jour la position sur la carte
    // Le MapComponent gère déjà le refresh automatique via le service
    // Mais on peut forcer un recentrage si nécessaire
    // this.mapComponent?.centerOn(this.driverLocation.latitude, this.driverLocation.longitude);
  }

  // ============================================================================
  // MÉTHODES UTILITAIRES - FORMATAGE
  // ============================================================================

  getStatusLabel(status: string): string {
    const statusLabels: { [key: string]: string } = {
      'pending': 'En attente',
      'assigned': 'Assignée',
      'accepted': 'Acceptée',
      'picked_up': 'Récupérée',
      'in_transit': 'En cours',
      'in_progress': 'En cours',
      'delivered': 'Livrée',
      'failed': 'Échouée',
      'cancelled': 'Annulée'
    };
    return statusLabels[status] || status;
  }

  getStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'pending': 'status-pending',
      'assigned': 'status-assigned',
      'accepted': 'status-accepted',
      'picked_up': 'status-picked-up',
      'in_transit': 'status-in-transit',
      'in_progress': 'status-in-progress',
      'delivered': 'status-delivered',
      'failed': 'status-failed',
      'cancelled': 'status-cancelled'
    };
    return classes[status] || '';
  }

  getStatusIcon(status: string): string {
    const icons: { [key: string]: string } = {
      'pending': 'schedule',
      'assigned': 'assignment_ind',
      'accepted': 'check_circle_outline',
      'picked_up': 'inventory',
      'in_transit': 'local_shipping',
      'in_progress': 'local_shipping',
      'delivered': 'check_circle',
      'failed': 'cancel',
      'cancelled': 'block'
    };
    return icons[status] || 'help_outline';
  }

  formatDate(date: string | null): string {
    if (!date) return 'N/A';
    
    return new Date(date).toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  formatRelativeTime(date: Date): string {
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);

    if (diffSecs < 10) {
      return 'à l\'instant';
    } else if (diffSecs < 60) {
      return `il y a ${diffSecs}s`;
    } else if (diffMins < 60) {
      return `il y a ${diffMins} min`;
    } else if (diffHours < 24) {
      return `il y a ${diffHours}h`;
    } else {
      const diffDays = Math.floor(diffHours / 24);
      return `il y a ${diffDays}j`;
    }
  }

  // ============================================================================
  // ACTIONS
  // ============================================================================

  canShowProof(): boolean {
    return this.delivery?.status === 'delivered';
  }

  viewProof(): void {
    if (this.delivery?.id) {
      this.router.navigate(['/deliveries/proof', this.delivery.id]);
    }
  }

  goBack(): void {
    this.router.navigate(['/deliveries']);
  }

  // ============================================================================
  // ÉVÉNEMENTS DE LA CARTE
  // ============================================================================

  /**
   * Gère le clic sur le marqueur du livreur
   */
  onDeliveryPersonClicked(deliveryPerson: Driver): void {
    console.log('Livreur cliqué:', deliveryPerson);
  }

  /**
   * Gère les erreurs de la carte
   */
  onMapError(error: Error): void {
    console.error('Erreur de carte:', error);
    this.showNotification('Erreur lors du chargement de la carte', 'error');
  }

  // ============================================================================
  // NOTIFICATIONS
  // ============================================================================

  private showNotification(message: string, type: 'success' | 'info' | 'warning' | 'error'): void {
    // TODO: Intégrer un vrai système de toast/notifications
    console.log(`${type.toUpperCase()}: ${message}`);
    
    // Temporaire : utiliser alert
    if (type === 'success' || type === 'error') {
      alert(message);
    }
  }
}