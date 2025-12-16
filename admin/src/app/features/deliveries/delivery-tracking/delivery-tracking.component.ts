// src/app/features/deliveries/delivery-tracking/delivery-tracking.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject, takeUntil, interval } from 'rxjs';
import { DeliveriesService } from '../deliveries.service';
import { WebSocketService } from '../../../core/services/websocket.service';
import { Delivery, DeliveryStatus } from '../models/delivery.model';
import { DeliveryMapComponent } from '../delivery-map/delivery-map.component';

interface DriverLocation {
  id: number;
  driverId: number;
  driverName: string;
  latitude: number;
  longitude: number;
  speed: number;
  heading: number;
  timestamp: string;
  accuracy: number;
}

@Component({
  selector: 'app-delivery-tracking',
  standalone: true,
  imports: [CommonModule, DeliveryMapComponent],
  templateUrl: './delivery-tracking.component.html',
  styleUrls: ['./delivery-tracking.component.scss']
})
export class DeliveryTrackingComponent implements OnInit, OnDestroy {
  // Variables
  delivery: Delivery | null = null;
  driverLocation: DriverLocation | null = null;
  loading = true;
  error: string | null = null;
  private destroy$ = new Subject<void>();

  // WebSocket
  wsConnected = false;
  lastUpdateTime: Date | null = null;

  // Simulation GPS (MODE TEST)
  private TEST_MODE = true;
  private gpsSimulationInterval: any;

  // Statistiques
  totalDistance: number = 0;
  completedDistance: number = 0;
  estimatedTimeMinutes: number = 0;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private deliveriesService: DeliveriesService,
    private wsService: WebSocketService
  ) {}

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
    
    if (this.gpsSimulationInterval) {
      clearInterval(this.gpsSimulationInterval);
    }
  }

  /**
   * CHARGER LES DÉTAILS DE LA LIVRAISON
   */
  private loadDelivery(deliveryId: number): void {
    this.loading = true;
    this.deliveriesService.getDelivery(deliveryId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (delivery) => {
          console.log('📦 Livraison chargée:', delivery);
          this.delivery = delivery;
          this.loading = false;

          // Calculer la distance totale
          if (delivery.driver?.currentLocation && delivery.address?.latitude) {
            this.totalDistance = this.calculateDistanceBetweenPoints(
              delivery.driver.currentLocation.latitude,
              delivery.driver.currentLocation.longitude,
              delivery.address.latitude,
              delivery.address.longitude
            );
          }

          // Si mode test, simuler le GPS
          if (this.TEST_MODE && delivery.status === DeliveryStatus.IN_PROGRESS) {
            this.startGPSSimulation(deliveryId);
          }
        },
        error: (error) => {
          console.error('❌ Erreur lors du chargement de la livraison:', error);
          this.error = 'Impossible de charger la livraison';
          this.loading = false;
        }
      });
  }

  /**
   * CONFIGURER WEBSOCKET
   */
  private setupWebSocket(deliveryId: number): void {
    this.wsService.connect();
    this.wsConnected = true;

    // Souscrire au canal de la livraison
    this.wsService.send({
      type: 'subscribe',
      channel: `delivery.${deliveryId}`
    });

    // Écouter les mises à jour de position GPS
    this.wsService.getMessagesByType('driver.location.updated')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        console.log('📍 Position GPS reçue:', message.data);
        
        // Vérifier que c'est bien notre livraison
        if (this.delivery?.driver && message.data.driverId === this.delivery.driver.id) {
          this.updateDriverLocation(message.data);
        }
      });

    // Écouter les mises à jour de statut
    this.wsService.getMessagesByType('delivery.status.updated')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        console.log('📬 Statut mis à jour:', message.data);
        
        if (this.delivery && message.data.deliveryId === this.delivery.id) {
          const oldStatus = this.delivery.status;
          this.delivery.status = message.data.status;
          
          // Notification de changement de statut
          this.onStatusChanged(oldStatus, message.data.status);
        }
      });

    // Écouter les événements de livraison
    this.wsService.getMessagesByType('delivery.completed')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        if (this.delivery && message.data.deliveryId === this.delivery.id) {
          console.log('✅ Livraison terminée !');
          this.showNotification('Livraison terminée avec succès !', 'success');
          
          // Arrêter la simulation GPS
          if (this.gpsSimulationInterval) {
            clearInterval(this.gpsSimulationInterval);
          }
        }
      });

    // Monitorer la connexion WebSocket
    interval(5000)
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        // Vérifier que la connexion est toujours active
        const timeSinceLastUpdate = this.lastUpdateTime 
          ? Date.now() - this.lastUpdateTime.getTime()
          : 0;
        
        if (timeSinceLastUpdate > 30000) {
          console.warn('⚠️ Pas de mise à jour GPS depuis 30 secondes');
        }
      });
  }

  /**
   * METTRE À JOUR LA POSITION DU LIVREUR
   */
  private updateDriverLocation(locationData: any): void {
    this.driverLocation = {
      id: locationData.id || Date.now(),
      driverId: locationData.driverId,
      driverName: locationData.driverName || this.delivery?.driver?.firstName + ' ' + this.delivery?.driver?.lastName,
      latitude: locationData.latitude,
      longitude: locationData.longitude,
      speed: locationData.speed || 0,
      heading: locationData.heading || 0,
      timestamp: locationData.timestamp || new Date().toISOString(),
      accuracy: locationData.accuracy || 10
    };

    this.lastUpdateTime = new Date();

    // Recalculer la distance restante
    if (this.delivery?.address?.latitude) {
      this.completedDistance = this.totalDistance - parseFloat(this.calculateDistance());
    }

    // Recalculer l'ETA
    this.updateETA();
  }

  /**
   * NOTIFICATION DE CHANGEMENT DE STATUT
   */
  private onStatusChanged(oldStatus: DeliveryStatus, newStatus: DeliveryStatus): void {
    const messages: { [key: string]: string } = {
      [DeliveryStatus.ASSIGNED]: '📋 Livraison assignée à un livreur',
      [DeliveryStatus.IN_PROGRESS]: '🚚 Le livreur est en route !',
      [DeliveryStatus.DELIVERED]: '✅ Livraison terminée avec succès',
      [DeliveryStatus.FAILED]: '❌ Échec de la livraison',
      [DeliveryStatus.CANCELLED]: '🚫 Livraison annulée'
    };

    const message = messages[newStatus];
    if (message) {
      this.showNotification(message, newStatus === DeliveryStatus.DELIVERED ? 'success' : 'info');
    }
  }

  /**
   * SIMULATION GPS EN MODE TEST
   * Simule un déplacement progressif vers la destination
   */
  private startGPSSimulation(deliveryId: number): void {
    if (!this.delivery?.address?.latitude || !this.delivery?.address?.longitude) {
      return;
    }

    console.log('🧪 MODE TEST - Simulation GPS activée');

    // Position de départ (un peu éloignée de la destination)
    const destLat = this.delivery.address.latitude;
    const destLng = this.delivery.address.longitude;
    
    let currentLat = destLat + (Math.random() - 0.5) * 0.05; // ~5km
    let currentLng = destLng + (Math.random() - 0.5) * 0.05;

    let simulationStep = 0;
    const totalSteps = 50; // 50 mises à jour pour arriver

    this.gpsSimulationInterval = setInterval(() => {
      if (!this.delivery || simulationStep >= totalSteps) {
        clearInterval(this.gpsSimulationInterval);
        return;
      }

      // Progression vers la destination
      const progress = simulationStep / totalSteps;
      currentLat = currentLat + (destLat - currentLat) * 0.05;
      currentLng = currentLng + (destLng - currentLng) * 0.05;

      // Vitesse variable (15-50 km/h)
      const speed = 20 + Math.random() * 30 + (progress * 10);

      // Heading (direction approximative)
      const heading = Math.atan2(destLng - currentLng, destLat - currentLat) * 180 / Math.PI;

      // Mettre à jour la position
      this.updateDriverLocation({
        driverId: this.delivery.driver?.id || 1,
        driverName: `${this.delivery.driver?.firstName} ${this.delivery.driver?.lastName}`,
        latitude: currentLat,
        longitude: currentLng,
        speed: Math.round(speed),
        heading: Math.round(heading),
        timestamp: new Date().toISOString(),
        accuracy: 5 + Math.random() * 10
      });

      simulationStep++;

      // Si presque arrivé, changer le statut
      if (simulationStep === totalSteps - 5) {
        console.log('🎯 Presque arrivé - Simulation terminée');
        if (this.delivery) {
          this.delivery.status = DeliveryStatus.DELIVERED;
          this.delivery.deliveredAt = new Date().toISOString();
        }
      }

    }, 2000); // Mise à jour toutes les 2 secondes
  }

  /**
   * CALCULER L'ETA (ESTIMATED TIME OF ARRIVAL)
   */
  private updateETA(): void {
    if (!this.driverLocation) {
      this.estimatedTimeMinutes = 0;
      return;
    }

    const distance = parseFloat(this.calculateDistance());
    const speed = this.driverLocation.speed || 30; // Vitesse par défaut 30 km/h

    // Temps en heures puis en minutes
    const timeInHours = distance / speed;
    this.estimatedTimeMinutes = Math.ceil(timeInHours * 60);
  }

  /**
   * CALCULER LA DISTANCE ENTRE LE LIVREUR ET LA DESTINATION
   */
  calculateDistance(): string {
    if (!this.driverLocation || !this.delivery?.address?.latitude) {
      return 'N/A';
    }

    const distance = this.calculateDistanceBetweenPoints(
      this.driverLocation.latitude,
      this.driverLocation.longitude,
      this.delivery.address.latitude,
      this.delivery.address.longitude
    );

    return distance.toFixed(2);
  }

  /**
   * FORMULE HAVERSINE - DISTANCE ENTRE DEUX POINTS GPS
   */
  private calculateDistanceBetweenPoints(lat1: number, lon1: number, lat2: number, lon2: number): number {
    const R = 6371; // Rayon de la Terre en km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  /**
   * ESTIMER LE TEMPS D'ARRIVÉE (FORMAT LISIBLE)
   */
  estimateArrivalTime(): string {
    if (!this.driverLocation) {
      return 'N/A';
    }

    if (this.estimatedTimeMinutes === 0) {
      this.updateETA();
    }

    if (this.estimatedTimeMinutes < 1) {
      return 'Imminent (< 1min)';
    } else if (this.estimatedTimeMinutes < 60) {
      return `${this.estimatedTimeMinutes} min`;
    } else {
      const hours = Math.floor(this.estimatedTimeMinutes / 60);
      const minutes = this.estimatedTimeMinutes % 60;
      return `${hours}h ${minutes}min`;
    }
  }

  /**
   * OBTENIR LE POURCENTAGE DE PROGRESSION
   */
  getProgressPercentage(): number {
    if (this.totalDistance === 0) return 0;
    return Math.min(100, Math.round((this.completedDistance / this.totalDistance) * 100));
  }

  /**
   * AFFICHER UNE NOTIFICATION
   */
  private showNotification(message: string, type: 'success' | 'info' | 'warning' | 'error'): void {
    // Vous pouvez utiliser une bibliothèque de toast ici
    console.log(`${type.toUpperCase()}: ${message}`);
    alert(message);
  }

  /**
   * RETOUR À LA LISTE
   */
  goBack(): void {
    this.router.navigate(['/deliveries']);
  }

  // Méthodes existantes pour le template

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

  formatDate(date: string | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  canShowProof(): boolean {
    return this.delivery?.status === DeliveryStatus.DELIVERED && !!this.delivery.proof;
  }

  viewProof(): void {
    if (this.delivery?.id) {
      this.router.navigate(['/deliveries/proof', this.delivery.id]);
    }
  }
}