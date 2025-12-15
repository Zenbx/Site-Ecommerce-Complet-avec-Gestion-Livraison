// src/app/features/deliveries/delivery-tracking/delivery-tracking.component.ts

import { Component, OnInit, OnDestroy, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { Subject, takeUntil, interval } from 'rxjs';
import { DeliveriesService } from '../deliveries.service';
import { WebSocketService } from '../../../core/services/websocket.service';
import { Delivery, DeliveryStatus } from '../models/delivery.model';

declare var L: any; // Leaflet

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
  imports: [CommonModule],
  templateUrl: './delivery-tracking.component.html',
  styleUrls: ['./delivery-tracking.component.scss']
})
export class DeliveryTrackingComponent implements OnInit, OnDestroy {
  @ViewChild('mapContainer', { static: false }) mapContainer!: ElementRef;

  // Variables
  delivery: Delivery | null = null;
  driverLocation: DriverLocation | null = null;
  loading = true;
  error: string | null = null;
  private destroy$ = new Subject<void>();

  // Carte
  map: any;
  driverMarker: any;
  deliveryMarker: any;
  routePath: any;
  routePolyline: any;

  // WebSocket
  wsConnected = false;

  constructor(
    private route: ActivatedRoute,
    private deliveriesService: DeliveriesService,
    private wsService: WebSocketService
  ) {}

  ngOnInit(): void {
    // Récupérer l'ID de la livraison
    this.route.params
      .pipe(takeUntil(this.destroy$))
      .subscribe(params => {
        const deliveryId = params['id'];
        if (deliveryId) {
          this.loadDelivery(deliveryId);
          this.setupWebSocket(deliveryId);
        }
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    if (this.map) {
      this.map.remove();
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
          this.initializeMap();
        },
        error: (error) => {
          console.error('❌ Erreur lors du chargement de la livraison:', error);
          this.error = 'Impossible de charger la livraison';
          this.loading = false;
        }
      });
  }

  /**
   * INITIALISER LA CARTE
   */
  private initializeMap(): void {
    if (!this.delivery || !this.mapContainer) return;

    // Coordonnées par défaut (Casablanca)
    const defaultLat = this.delivery.address?.latitude || 33.5731;
    const defaultLng = this.delivery.address?.longitude || -7.5898;

    // Initialiser Leaflet
    if (!this.map) {
      this.map = L.map(this.mapContainer.nativeElement).setView(
        [defaultLat, defaultLng],
        13
      );

      // Ajouter les tuiles OpenStreetMap
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
      }).addTo(this.map);
    }

    // Ajouter le marqueur de destination
    this.deliveryMarker = L.marker([defaultLat, defaultLng], {
      icon: L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
      }),
      title: 'Destination'
    }).addTo(this.map);

    this.deliveryMarker.bindPopup(`
      <div class="marker-popup">
        <strong>Destination</strong><br>
        ${this.delivery.customer.name}<br>
        ${this.delivery.address.city}
      </div>
    `);

    // Ajouter le marqueur du livreur s'il existe
    if (this.driverLocation) {
      this.updateDriverMarker();
    }
  }

  /**
   * METTRE À JOUR LE MARQUEUR DU LIVREUR
   */
  private updateDriverMarker(): void {
    if (!this.map || !this.driverLocation) return;

    const driverLat = this.driverLocation.latitude;
    const driverLng = this.driverLocation.longitude;

    if (this.driverMarker) {
      this.driverMarker.setLatLng([driverLat, driverLng]);
      this.driverMarker.setRotationAngle(this.driverLocation.heading || 0);
    } else {
      // Créer le marqueur du livreur
      this.driverMarker = L.marker([driverLat, driverLng], {
        icon: L.icon({
          iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
          shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41]
        }),
        title: 'Livreur'
      }).addTo(this.map);

      this.driverMarker.bindPopup(`
        <div class="marker-popup">
          <strong>${this.driverLocation.driverName}</strong><br>
          Vitesse: ${this.driverLocation.speed} km/h<br>
          Précision: ±${this.driverLocation.accuracy}m
        </div>
      `);
    }

    // Dessiner le trajet entre le livreur et la destination
    this.drawRoute();

    // Recentrer la carte pour voir les deux points
    if (this.delivery) {
      const destLat = this.delivery.address?.latitude || 33.5731;
      const destLng = this.delivery.address?.longitude || -7.5898;
      const bounds = L.latLngBounds(
        [driverLat, driverLng],
        [destLat, destLng]
      );
      this.map.fitBounds(bounds, { padding: [50, 50] });
    }
  }

  /**
   * DESSINER LE TRAJET
   */
  private drawRoute(): void {
    if (!this.map || !this.driverLocation || !this.delivery) return;

    if (this.routePolyline) {
      this.map.removeLayer(this.routePolyline);
    }

    const destLat = this.delivery.address?.latitude || 33.5731;
    const destLng = this.delivery.address?.longitude || -7.5898;

    this.routePolyline = L.polyline(
      [
        [this.driverLocation.latitude, this.driverLocation.longitude],
        [destLat, destLng]
      ],
      {
        color: '#4299e1',
        weight: 3,
        opacity: 0.7,
        dashArray: '5, 5'
      }
    ).addTo(this.map);
  }

  /**
   * CONFIGURER WEBSOCKET
   */
  private setupWebSocket(deliveryId: number): void {
    this.wsService.connect();

    // Subscribe aux mises à jour de position GPS
    this.wsService.getMessagesByType('driver.location.updated')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        console.log('📍 Position du livreur reçue:', message.data);
        this.driverLocation = message.data;
        this.updateDriverMarker();
      });

    // Subscribe aux mises à jour de statut
    this.wsService.getMessagesByType('delivery.status.updated')
      .pipe(takeUntil(this.destroy$))
      .subscribe((message) => {
        console.log('📬 Statut de livraison mis à jour:', message.data);
        if (this.delivery) {
          this.delivery.status = message.data.status;
        }
      });

    // MarkerWebSocket est maintenant connecté
    this.wsConnected = true;
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
   * FORMATER LA DATE
   */
  formatDate(date: string | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('fr-FR');
  }

  /**
   * CALCULER LA DISTANCE
   */
  calculateDistance(): string {
    if (!this.driverLocation || !this.delivery) return 'N/A';

    const R = 6371; // Rayon de la Terre en km
    const lat1 = this.driverLocation.latitude;
    const lon1 = this.driverLocation.longitude;
    const lat2 = this.delivery.address?.latitude || 33.5731;
    const lon2 = this.delivery.address?.longitude || -7.5898;

    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c;

    return distance.toFixed(2);
  }

  /**
   * ESTIMER LE TEMPS D'ARRIVÉE
   */
  estimateArrivalTime(): string {
    if (!this.driverLocation) return 'N/A';

    const distance = parseFloat(this.calculateDistance());
    const speed = this.driverLocation.speed || 30; // Vitesse moyenne par défaut
    const timeInHours = distance / speed;
    const timeInMinutes = timeInHours * 60;

    if (timeInMinutes < 1) {
      return 'Imminent';
    } else if (timeInMinutes < 60) {
      return `${Math.ceil(timeInMinutes)}min`;
    } else {
      const hours = Math.floor(timeInMinutes / 60);
      const minutes = Math.ceil(timeInMinutes % 60);
      return `${hours}h ${minutes}min`;
    }
  }
}
