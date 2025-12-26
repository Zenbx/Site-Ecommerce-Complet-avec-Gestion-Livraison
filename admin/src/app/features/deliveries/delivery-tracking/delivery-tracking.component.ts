// src/app/features/deliveries/delivery-tracking/delivery-tracking.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject, takeUntil, interval } from 'rxjs';
import * as L from 'leaflet';

import { DeliveriesService } from '../../../core/services/deliveries.service';
import { WebSocketService } from '../../../core/services/websocket.service';
import { Delivery } from '../../../core/models/delivery.model';
import { MapComponent } from '../../map/map.component';

// ============================================
// INTERFACES
// ============================================

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

// ============================================
// CONFIGURATION LEAFLET
// ============================================

const iconRetinaUrl = 'assets/marker-icon-2x.png';
const iconUrl = 'assets/marker-icon.png';
const shadowUrl = 'assets/marker-shadow.png';

const iconDefault = L.icon({
  iconRetinaUrl,
  iconUrl,
  shadowUrl,
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  tooltipAnchor: [16, -28],
  shadowSize: [41, 41]
});
L.Marker.prototype.options.icon = iconDefault;

// ============================================
// COMPOSANT
// ============================================

@Component({
  selector: 'app-delivery-tracking',
  standalone: true,
  imports: [CommonModule, MapComponent],
  templateUrl: './delivery-tracking.component.html',
  styleUrls: ['./delivery-tracking.component.scss']
})
export class DeliveryTrackingComponent implements OnInit, OnDestroy {
  
  // ============================================
  // PROPRIÉTÉS
  // ============================================
  
  // Données principales
  delivery: Delivery | null = null;
  driverLocation: DriverLocation | null = null;
  loading = true;
  error: string | null = null;
  
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
  
  // Leaflet
  private map: L.Map | null = null;
  private driverMarker: L.Marker | null = null;
  private destinationMarker: L.Marker | null = null;
  private routeLine: L.Polyline | null = null;
  
  // RxJS
  private destroy$ = new Subject<void>();

  // ============================================
  // CONSTRUCTEUR
  // ============================================

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private deliveriesService: DeliveriesService,
    private wsService: WebSocketService
  ) {}

  // ============================================
  // HOOKS DU CYCLE DE VIE
  // ============================================

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
    
    if (this.map) {
      this.map.remove();
      this.map = null;
    }
  }

  // ============================================
  // CHARGEMENT DES DONNÉES
  // ============================================

  public loadDelivery(deliveryId: number): void {
    this.loading = true;
    this.deliveriesService.getDelivery(deliveryId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (delivery) => {
          console.log('📦 Livraison chargée:', delivery);
          this.delivery = delivery;
          this.loading = false;

          // Calculer la distance totale
        //   if (delivery.delivery_person?.currentLocation && delivery.address?.latitude) {
        //     this.totalDistance = this.calculateDistanceBetweenPoints(
        //       delivery.delivery_person.currentLocation.latitude,
        //       delivery.delivery_person.currentLocation.longitude,
        //       delivery.address.latitude,
        //       delivery.address.longitude
        //     );
        //   }

          // Initialiser la carte
          // setTimeout(() => {
          //   this.initializeMap();
          // }, 100);

          // Si mode test, simuler le GPS
          if (this.TEST_MODE && delivery.status === 'in_progress') {
            // this.startGPSSimulation(deliveryId);
          }
        },
        error: (error) => {
          console.error('❌ Erreur lors du chargement de la livraison:', error);
          this.error = 'Impossible de charger la livraison';
          this.loading = false;
        }
      });
  }

  // ============================================
  // WEBSOCKET
  // ============================================

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
        
        if (this.delivery?.delivery_person && message.data.driverId === this.delivery.delivery_person.id) {
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
          // this.onStatusChanged(oldStatus, message.data.status);
        }
      });

    // Écouter les événements de livraison
    this.wsService.getMessagesByType('delivery.completed')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        if (this.delivery && message.data.deliveryId === this.delivery.id) {
          console.log('✅ Livraison terminée !');
          this.showNotification('Livraison terminée avec succès !', 'success');
          
          if (this.gpsSimulationInterval) {
            clearInterval(this.gpsSimulationInterval);
          }
        }
      });

    // Monitorer la connexion WebSocket
    interval(5000)
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        const timeSinceLastUpdate = this.lastUpdateTime 
          ? Date.now() - this.lastUpdateTime.getTime()
          : 0;
        
        if (timeSinceLastUpdate > 30000) {
          console.warn('⚠️ Pas de mise à jour GPS depuis 30 secondes');
        }
      });
  }

  private updateDriverLocation(locationData: any): void {
    this.driverLocation = {
      id: locationData.id || Date.now(),
      driverId: locationData.driverId,
      driverName: locationData.driverName || `${this.delivery?.delivery_person?.name}`,
      latitude: locationData.latitude,
      longitude: locationData.longitude,
      speed: locationData.speed || 0,
      heading: locationData.heading || 0,
      timestamp: locationData.timestamp || new Date().toISOString(),
      accuracy: locationData.accuracy || 10
    };

    this.lastUpdateTime = new Date();

    // // Recalculer la distance restante
    // if (this.delivery?.delivery_address?.latitude) {
    //   this.completedDistance = this.totalDistance - parseFloat(this.calculateDistance());
    // }

    // Recalculer l'ETA
    // this.updateETA();

    // Mettre à jour la carte
    // this.updateDriverMarker();
  }

  // private onStatusChanged(oldStatus: DeliveryStatus, newStatus: DeliveryStatus): void {
  //   const messages: { [key: string]: string } = {
  //     [DeliveryStatus.ASSIGNED]: '📋 Livraison assignée à un livreur',
  //     [DeliveryStatus.IN_PROGRESS]: '🚚 Le livreur est en route !',
  //     [DeliveryStatus.DELIVERED]: '✅ Livraison terminée avec succès',
  //     [DeliveryStatus.FAILED]: '❌ Échec de la livraison',
  //     [DeliveryStatus.CANCELLED]: '🚫 Livraison annulée'
  //   };

  //   const message = messages[newStatus];
  //   if (message) {
  //     this.showNotification(message, newStatus === DeliveryStatus.DELIVERED ? 'success' : 'info');
  //   }
  // }

  // ============================================
  // SIMULATION GPS (MODE TEST)
  // ============================================

  // private startGPSSimulation(deliveryId: number): void {
  //   if (!this.delivery?.address?.latitude || !this.delivery?.address?.longitude) {
  //     return;
  //   }

  //   console.log('🧪 MODE TEST - Simulation GPS activée');

  //   const destLat = this.delivery.address.latitude;
  //   const destLng = this.delivery.address.longitude;
    
  //   let currentLat = destLat + (Math.random() - 0.5) * 0.05;
  //   let currentLng = destLng + (Math.random() - 0.5) * 0.05;

  //   let simulationStep = 0;
  //   const totalSteps = 50;

  //   this.gpsSimulationInterval = setInterval(() => {
  //     if (!this.delivery || simulationStep >= totalSteps) {
  //       clearInterval(this.gpsSimulationInterval);
  //       return;
  //     }

  //     const progress = simulationStep / totalSteps;
  //     currentLat = currentLat + (destLat - currentLat) * 0.05;
  //     currentLng = currentLng + (destLng - currentLng) * 0.05;

  //     const speed = 20 + Math.random() * 30 + (progress * 10);
  //     const heading = Math.atan2(destLng - currentLng, destLat - currentLat) * 180 / Math.PI;

  //     this.updateDriverLocation({
  //       driverId: this.delivery.driver?.id || 1,
  //       driverName: `${this.delivery.driver?.firstName} ${this.delivery.driver?.lastName}`,
  //       latitude: currentLat,
  //       longitude: currentLng,
  //       speed: Math.round(speed),
  //       heading: Math.round(heading),
  //       timestamp: new Date().toISOString(),
  //       accuracy: 5 + Math.random() * 10
  //     });

  //     simulationStep++;

  //     if (simulationStep === totalSteps - 5) {
  //       console.log('🎯 Presque arrivé - Simulation terminée');
  //       if (this.delivery) {
  //         this.delivery.status = DeliveryStatus.DELIVERED;
  //         this.delivery.deliveredAt = new Date().toISOString();
  //       }
  //     }
  //   }, 2000);
  // }

  // // ============================================
  // // CALCULS DE DISTANCE ET ETA
  // // ============================================

  // private updateETA(): void {
  //   if (!this.driverLocation) {
  //     this.estimatedTimeMinutes = 0;
  //     return;
  //   }

  //   const distance = parseFloat(this.calculateDistance());
  //   const speed = this.driverLocation.speed || 30;

  //   const timeInHours = distance / speed;
  //   this.estimatedTimeMinutes = Math.ceil(timeInHours * 60);
  // }

  // calculateDistance(): string {
  //   if (!this.driverLocation || !this.delivery?.address?.latitude) {
  //     return 'N/A';
  //   }

  //   const distance = this.calculateDistanceBetweenPoints(
  //     this.driverLocation.latitude,
  //     this.driverLocation.longitude,
  //     this.delivery.address.latitude,
  //     this.delivery.address.longitude
  //   );

  //   return distance.toFixed(2);
  // }

  // private calculateDistanceBetweenPoints(lat1: number, lon1: number, lat2: number, lon2: number): number {
  //   const R = 6371;
  //   const dLat = (lat2 - lat1) * Math.PI / 180;
  //   const dLon = (lon2 - lon1) * Math.PI / 180;
    
  //   const a =
  //     Math.sin(dLat / 2) * Math.sin(dLat / 2) +
  //     Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
  //     Math.sin(dLon / 2) * Math.sin(dLon / 2);
    
  //   const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  //   return R * c;
  // }

  // estimateArrivalTime(): string {
  //   if (!this.driverLocation) {
  //     return 'N/A';
  //   }

  //   if (this.estimatedTimeMinutes === 0) {
  //     this.updateETA();
  //   }

  //   if (this.estimatedTimeMinutes < 1) {
  //     return 'Imminent (< 1min)';
  //   } else if (this.estimatedTimeMinutes < 60) {
  //     return `${this.estimatedTimeMinutes} min`;
  //   } else {
  //     const hours = Math.floor(this.estimatedTimeMinutes / 60);
  //     const minutes = this.estimatedTimeMinutes % 60;
  //     return `${hours}h ${minutes}min`;
  //   }
  // }

  // getProgressPercentage(): number {
  //   if (this.totalDistance === 0) return 0;
  //   return Math.min(100, Math.round((this.completedDistance / this.totalDistance) * 100));
  // }

  // ============================================
  // LEAFLET - INITIALISATION
  // ============================================

  // private initializeMap(): void {
  //   if (!this.delivery) {
  //     console.warn('⚠️ Impossible d\'initialiser la carte : pas de données de livraison');
  //     return;
  //   }

  //   const mapElement = document.getElementById('tracking-map');
  //   if (!mapElement) {
  //     console.error('❌ Élément de carte introuvable dans le DOM');
  //     return;
  //   }

  //   try {
  //     this.map = L.map('tracking-map', {
  //       center: [this.delivery.address.latitude, this.delivery.address.longitude],
  //       zoom: 13,
  //       scrollWheelZoom: false
  //     });

  //     L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  //       attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  //       maxZoom: 19
  //     }).addTo(this.map);

  //     const destinationIcon = L.divIcon({
  //       html: `
  //         <div style="
  //           width: 24px;
  //           height: 24px;
  //           background: #ea4335;
  //           border-radius: 50%;
  //           border: 3px solid white;
  //           box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
  //         "></div>
  //       `,
  //       className: '',
  //       iconSize: [24, 24],
  //       iconAnchor: [12, 12]
  //     });

  //     this.destinationMarker = L.marker(
  //       [this.delivery.address.latitude, this.delivery.address.longitude],
  //       { icon: destinationIcon }
  //     ).addTo(this.map);

  //     this.destinationMarker.bindPopup(`
  //       <div style="text-align: center; padding: 4px;">
  //         <strong style="color: #ea4335;">📍 Destination</strong><br>
  //         <span style="font-size: 12px;">${this.delivery.address.street}</span>
  //       </div>
  //     `);

  //     if (this.driverLocation) {
  //       this.updateDriverMarker();
  //     }

  //     console.log('✅ Carte Leaflet initialisée avec succès');
  //   } catch (error) {
  //     console.error('❌ Erreur lors de l\'initialisation de la carte Leaflet:', error);
  //     this.error = 'Impossible d\'initialiser la carte de suivi';
  //   }
  // }

  // // ============================================
  // // LEAFLET - MISE À JOUR
  // // ============================================

  // private updateDriverMarker(): void {
  //   if (!this.map || !this.driverLocation || !this.delivery) {
  //     return;
  //   }

  //   const driverIcon = L.divIcon({
  //     html: `
  //       <div style="
  //         width: 28px;
  //         height: 28px;
  //         background: #1a73e8;
  //         border-radius: 50%;
  //         border: 3px solid white;
  //         box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
  //         position: relative;
  //       ">
  //         <div style="
  //           position: absolute;
  //           top: -8px;
  //           right: -8px;
  //           width: 12px;
  //           height: 12px;
  //           background: #34a853;
  //           border-radius: 50%;
  //           border: 2px solid white;
  //         "></div>
  //       </div>
  //     `,
  //     className: '',
  //     iconSize: [28, 28],
  //     iconAnchor: [14, 14]
  //   });

  //   if (this.driverMarker) {
  //     this.driverMarker.setLatLng([this.driverLocation.latitude, this.driverLocation.longitude]);
  //     this.driverMarker.setPopupContent(`
  //       <div style="text-align: center; padding: 4px;">
  //         <strong style="color: #1a73e8;">🚚 Livreur</strong><br>
  //         <span style="font-size: 12px;">${this.driverLocation.driverName}</span><br>
  //         <span style="font-size: 11px; color: #5f6368;">
  //           Vitesse: ${this.driverLocation.speed} km/h
  //         </span>
  //       </div>
  //     `);
  //   } else {
  //     this.driverMarker = L.marker(
  //       [this.driverLocation.latitude, this.driverLocation.longitude],
  //       { icon: driverIcon }
  //     ).addTo(this.map);

  //     this.driverMarker.bindPopup(`
  //       <div style="text-align: center; padding: 4px;">
  //         <strong style="color: #1a73e8;">🚚 Livreur</strong><br>
  //         <span style="font-size: 12px;">${this.driverLocation.driverName}</span><br>
  //         <span style="font-size: 11px; color: #5f6368;">
  //           Vitesse: ${this.driverLocation.speed} km/h
  //         </span>
  //       </div>
  //     `);
  //   }

  //   this.updateRouteLine();
  //   this.fitMapBounds();
  // }

  // private updateRouteLine(): void {
  //   if (!this.map || !this.driverLocation || !this.delivery) {
  //     return;
  //   }

  //   const points: [number, number][] = [
  //     [this.driverLocation.latitude, this.driverLocation.longitude],
  //     [this.delivery.address.latitude, this.delivery.address.longitude]
  //   ];

  //   if (this.routeLine) {
  //     this.routeLine.setLatLngs(points);
  //   } else {
  //     this.routeLine = L.polyline(points, {
  //       color: '#1a73e8',
  //       weight: 3,
  //       opacity: 0.7,
  //       dashArray: '10, 10',
  //       lineJoin: 'round'
  //     }).addTo(this.map);
  //   }
  // }

  // private fitMapBounds(): void {
  //   if (!this.map || !this.driverLocation || !this.delivery) {
  //     return;
  //   }

  //   const bounds = L.latLngBounds([
  //     [this.driverLocation.latitude, this.driverLocation.longitude],
  //     [this.delivery.address.latitude, this.delivery.address.longitude]
  //   ]);

  //   this.map.fitBounds(bounds, {
  //     padding: [50, 50],
  //     maxZoom: 15,
  //     animate: true,
  //     duration: 0.5
  //   });
  // }

  // ============================================
  // LEAFLET - CONTRÔLES
  // ============================================

  zoomIn(): void {
    if (this.map) {
      this.map.zoomIn();
    }
  }

  zoomOut(): void {
    if (this.map) {
      this.map.zoomOut();
    }
  }

  // centerOnDriver(): void {
  //   if (this.map && this.driverLocation) {
  //     this.map.setView(
  //       [this.driverLocation.latitude, this.driverLocation.longitude],
  //       15,
  //       { animate: true, duration: 0.5 }
  //     );
  //   } else if (this.map && this.delivery) {
  //     this.map.setView(
  //       [this.delivery.address.latitude, this.delivery.address.longitude],
  //       15,
  //       { animate: true, duration: 0.5 }
  //     );
  //   }
  // }

  // ============================================
  // MÉTHODES UTILITAIRES
  // ============================================

  getStatusLabel(status: string): string {
    const statusLabels: { [key: string]: string } = {
      'PENDING': 'En attente',
      'ASSIGNED': 'Assignée',
      'IN_TRANSIT': 'En cours',
      'DELIVERED': 'Livrée',
      'FAILED': 'Échouée',
      'CANCELLED': 'Annulée'
    };
    return statusLabels[status] || status;
  }

  getStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'pending': 'status-pending',
      'assigned': 'status-assigned',
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
      'in_progress': 'local_shipping',
      'delivered': 'check_circle',
      'failed': 'cancel',
      'cancelled': 'block'
    };
    return icons[status] || 'help_outline';
  }

  getPriorityIcon(priority: string): string {
    const icons: { [key: string]: string } = {
      'high': 'priority_high',
      'medium': 'remove',
      'low': 'arrow_downward'
    };
    return icons[priority] || 'remove';
  }

  getPriorityLabel(priority: string): string {
    const labels: { [key: string]: string } = {
      'high': 'Urgente',
      'medium': 'Normale',
      'low': 'Basse'
    };
    return labels[priority] || priority;
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
      return 'À l\'instant';
    } else if (diffSecs < 60) {
      return `Il y a ${diffSecs} seconde${diffSecs > 1 ? 's' : ''}`;
    } else if (diffMins < 60) {
      return `Il y a ${diffMins} minute${diffMins > 1 ? 's' : ''}`;
    } else if (diffHours < 24) {
      return `Il y a ${diffHours} heure${diffHours > 1 ? 's' : ''}`;
    } else {
      return this.formatDate(date.toISOString());
    }
  }

  canShowProof(): boolean {
    return this.delivery?.status === "delivered";
  }

  viewProof(): void {
    if (this.delivery?.id) {
      this.router.navigate(['/deliveries/proof', this.delivery.id]);
    }
  }

  goBack(): void {
    this.router.navigate(['/deliveries']);
  }

  private showNotification(message: string, type: 'success' | 'info' | 'warning' | 'error'): void {
    console.log(`${type.toUpperCase()}: ${message}`);
    alert(message);
  }
}