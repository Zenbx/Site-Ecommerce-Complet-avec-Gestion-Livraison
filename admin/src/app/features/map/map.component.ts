import { Component, OnInit, OnDestroy, Input, Output, EventEmitter, AfterViewInit, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Subject, interval, takeUntil, switchMap, startWith } from 'rxjs';
import * as L from 'leaflet';

import { DeliveryPersonLocationService } from '../../core/services/delivery-person-location.service';
import { GeocodingService } from '../../core/services/geocoding.service';
import { MapService } from '../../core/services/map.service';

import { Driver } from '../../core/models/driver.model';
import { DeliveryLocation } from '../../core/models/delivery-location.model';
import { MapConfig, MapMode } from '../../core/models/map-config.model';

/**
 * Composant de carte modulaire pour afficher :
 * - Mode 'overview': Tous les livreurs + livraisons optionnelles
 * - Mode 'tracking': Un livreur spécifique + sa livraison
 */
@Component({
  selector: 'app-map',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './map.component.html',
  styleUrls: ['./map.component.scss']
})
export class MapComponent implements OnInit, AfterViewInit, OnDestroy {
  @ViewChild('mapContainer', { static: false }) mapContainer!: ElementRef;

  // ============================================================================
  // INPUTS - Configuration du composant
  // ============================================================================

  /**
   * Mode d'affichage de la carte
   * - 'overview': Vue globale de tous les livreurs
   * - 'tracking': Suivi d'un livreur spécifique
   */
  @Input() mode: MapMode = 'overview';

  /**
   * ID du livreur à suivre (mode 'tracking' uniquement)
   */
  @Input() deliveryPersonId?: number;

  /**
   * ID de la livraison à afficher (mode 'tracking' uniquement)
   */
  @Input() deliveryId?: number;

  /**
   * Afficher les destinations des livraisons assignées (mode 'overview')
   */
  @Input() showDeliveryDestinations: boolean = false;

  /**
   * Intervalle de refresh en secondes (mode 'overview')
   */
  @Input() refreshInterval: number = 10;

  /**
   * Configuration personnalisée de la carte
   */
  @Input() mapConfig?: Partial<MapConfig>;

  // ============================================================================
  // OUTPUTS - Événements
  // ============================================================================

  /**
   * Émis quand un marqueur de livreur est cliqué
   */
  @Output() deliveryPersonClicked = new EventEmitter<Driver>();

  /**
   * Émis quand un marqueur de livraison est cliqué
   */
  @Output() deliveryClicked = new EventEmitter<DeliveryLocation>();

  /**
   * Émis quand une erreur survient
   */
  @Output() mapError = new EventEmitter<Error>();

  // ============================================================================
  // PROPRIÉTÉS PUBLIQUES
  // ============================================================================

  map?: L.Map;
  isLoading: boolean = true;
  error: string | null = null;

  // Compteurs pour l'UI
  onlineCount: number = 0;
  offlineCount: number = 0;
  deliveryCount: number = 0;

  // ============================================================================
  // PROPRIÉTÉS PRIVÉES
  // ============================================================================

  private destroy$ = new Subject<void>();
  private deliveryPersonMarkers: Map<number, L.Marker> = new Map();
  private deliveryMarkers: Map<number, L.Marker> = new Map();
  private routeLayer?: L.Polyline;

  // Groupes de layers pour une gestion facile
  private onlineDeliveryPersonsLayer!: L.LayerGroup;
  private offlineDeliveryPersonsLayer!: L.LayerGroup;
  private deliveriesLayer!: L.LayerGroup;

  // Configuration par défaut
  private readonly defaultConfig: MapConfig = {
    center: [3.8667, 11.5167], // Yaoundé, Cameroun
    zoom: 12,
    maxZoom: 18,
    minZoom: 10
  };

  // ============================================================================
  // CONSTRUCTOR
  // ============================================================================

  constructor(
    private deliveryPersonLocationService: DeliveryPersonLocationService,
    private geocodingService: GeocodingService,
    private mapService: MapService
  ) {}

  // ============================================================================
  // LIFECYCLE HOOKS
  // ============================================================================

  ngOnInit(): void {
    this.validateInputs();
  }

  ngAfterViewInit(): void {
    this.initializeMap();
    this.loadData();

    // Mode overview : refresh automatique
    if (this.mode === 'overview') {
      this.setupAutoRefresh();
    }

    this.toggleDeliveryDestinations();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    this.cleanupMap();
  }

  // ============================================================================
  // INITIALISATION
  // ============================================================================

  /**
   * Valide les inputs selon le mode
   */
  private validateInputs(): void {
    if (this.mode === 'tracking' && !this.deliveryPersonId) {
      console.warn('[MapComponent] Mode "tracking" requires deliveryPersonId');
    }
  }

  /**
   * Initialise la carte Leaflet
   */
  private initializeMap(): void {
    try {
      const config = { ...this.defaultConfig, ...this.mapConfig };

      // Créer la carte
      this.map = L.map(this.mapContainer.nativeElement, {
        center: config.center as L.LatLngExpression,
        zoom: config.zoom,
        maxZoom: config.maxZoom,
        minZoom: config.minZoom,
        zoomControl: true
      });

      // Ajouter le tile layer (OpenStreetMap)
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: config.maxZoom
      }).addTo(this.map);

      // Créer les layers groups
      this.onlineDeliveryPersonsLayer = L.layerGroup().addTo(this.map);
      this.offlineDeliveryPersonsLayer = L.layerGroup().addTo(this.map);
      this.deliveriesLayer = L.layerGroup().addTo(this.map);

      // Forcer un resize après initialisation
      setTimeout(() => {
        this.map?.invalidateSize();
      }, 100);

    } catch (error) {
      console.error('[MapComponent] Error initializing map:', error);
      this.handleError(error as Error);
    }
  }

  /**
   * Configure le refresh automatique (mode overview)
   */
  private setupAutoRefresh(): void {
    interval(this.refreshInterval * 1000)
      .pipe(
        startWith(0), // Démarrer immédiatement
        switchMap(() => this.deliveryPersonLocationService.getAllLocations()),
        takeUntil(this.destroy$)
      )
      .subscribe({
        next: (response) => this.updateDeliveryPersonMarkers(response.data),
        error: (error) => this.handleError(error)
      });
  }

  // ============================================================================
  // CHARGEMENT DES DONNÉES
  // ============================================================================

  /**
   * Charge les données selon le mode
   */
  private loadData(): void {
    this.isLoading = true;
    this.error = null;

    if (this.mode === 'overview') {
      this.loadAllDeliveryPersons();
    } else if (this.mode === 'tracking') {
      this.loadSingleDeliveryPerson();
    }
  }

  /**
   * Charge tous les livreurs (mode overview)
   */
  private loadAllDeliveryPersons(): void {
    this.deliveryPersonLocationService.getAllLocations()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          this.updateDeliveryPersonMarkers(response.data);
          this.isLoading = false;

          // Charger les livraisons si demandé
          if (this.showDeliveryDestinations) {
            this.loadAssignedDeliveries();
          }
        },
        error: (error) => {
          this.handleError(error);
          this.isLoading = false;
        }
      });
  }

  /**
   * Charge un livreur spécifique (mode tracking)
   */
  private loadSingleDeliveryPerson(): void {
    if (!this.deliveryPersonId) {
      this.isLoading = false;
      return;
    }

    this.deliveryPersonLocationService.getLocation(this.deliveryPersonId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          this.updateDeliveryPersonMarkers([response.data]);
          this.isLoading = false;

          // Charger la livraison si un ID est fourni
          if (this.deliveryId) {
            this.loadDeliveryRoute();
          }
        },
        error: (error) => {
          this.handleError(error);
          this.isLoading = false;
        }
      });
  }

  /**
   * Charge les livraisons assignées et leurs destinations
   */
  private loadAssignedDeliveries(): void {
    // TODO: Implémenter l'appel à l'API des livraisons
    // Pour l'instant, placeholder
    console.log('[MapComponent] Loading assigned deliveries...');
  }

  /**
   * Charge et affiche la route d'une livraison (mode tracking)
   */
  private loadDeliveryRoute(): void {
    // TODO: Implémenter le chargement de la route
    console.log('[MapComponent] Loading delivery route...');
  }

  // ============================================================================
  // GESTION DES MARQUEURS - LIVREURS
  // ============================================================================

  /**
   * Met à jour les marqueurs des livreurs
   */
  private updateDeliveryPersonMarkers(deliveryPersons: Driver[]): void {
    // Séparer les livreurs online/offline
    const online = deliveryPersons.filter(dp => dp.is_online && dp.current_latitude && dp.current_longitude);
    const offline = deliveryPersons.filter(dp => !dp.is_online && dp.current_latitude && dp.current_longitude);

    // Mettre à jour les compteurs
    this.onlineCount = online.length;
    this.offlineCount = offline.length;

    // Nettoyer les anciens marqueurs
    this.clearDeliveryPersonMarkers();

    // Créer les nouveaux marqueurs
    online.forEach(dp => this.createDeliveryPersonMarker(dp, true));
    offline.forEach(dp => this.createDeliveryPersonMarker(dp, false));

    // Ajuster la vue si nécessaire (mode overview uniquement)
    if (this.mode === 'overview' && deliveryPersons.length > 0) {
      this.fitBoundsToMarkers();
    }
  }

  /**
   * Crée un marqueur pour un livreur
   */
  private createDeliveryPersonMarker(deliveryPerson: Driver, isOnline: boolean): void {
    if (!deliveryPerson.current_latitude || !deliveryPerson.current_longitude) {
      return;
    }

    const lat = parseFloat(deliveryPerson.current_latitude);
    const lng = parseFloat(deliveryPerson.current_longitude);

    // Créer l'icône selon le statut
    const icon = this.mapService.createDeliveryPersonIcon(isOnline, deliveryPerson.is_available);

    // Créer le marqueur
    const marker = L.marker([lat, lng], { icon })
      .bindPopup(this.createDeliveryPersonPopup(deliveryPerson, isOnline))
      .on('click', () => this.onDeliveryPersonMarkerClick(deliveryPerson));

    // Ajouter au bon layer
    if (isOnline) {
      marker.addTo(this.onlineDeliveryPersonsLayer);
    } else {
      marker.addTo(this.offlineDeliveryPersonsLayer);
    }

    // Stocker la référence
    this.deliveryPersonMarkers.set(deliveryPerson.id, marker);
  }

  /**
   * Crée le contenu HTML du popup pour un livreur
   */
  private createDeliveryPersonPopup(deliveryPerson: Driver, isOnline: boolean): string {
    const status = isOnline ? '🟢 En ligne' : '⚫ Hors ligne';
    const availability = deliveryPerson.is_available ? '✅ Disponible' : '🚫 Non disponible';
    
    let lastUpdate = '';
    if (deliveryPerson.last_location_update) {
      lastUpdate = `<br><small>Mis à jour: ${this.formatDate(deliveryPerson.last_location_update)}</small>`;
    }

    return `
      <div class="delivery-person-popup">
        <strong>${deliveryPerson.name}</strong><br>
        ${status} | ${availability}
        ${lastUpdate}
        ${deliveryPerson.current_address ? `<br><small>📍 ${deliveryPerson.current_address}</small>` : ''}
      </div>
    `;
  }

  /**
   * Nettoie tous les marqueurs de livreurs
   */
  private clearDeliveryPersonMarkers(): void {
    this.onlineDeliveryPersonsLayer.clearLayers();
    this.offlineDeliveryPersonsLayer.clearLayers();
    this.deliveryPersonMarkers.clear();
  }

  // ============================================================================
  // GESTION DES MARQUEURS - LIVRAISONS
  // ============================================================================

  /**
   * Crée un marqueur pour une destination de livraison
   */
  private createDeliveryMarker(delivery: DeliveryLocation): void {
    // TODO: Implémenter
  }

  /**
   * Nettoie tous les marqueurs de livraisons
   */
  private clearDeliveryMarkers(): void {
    this.deliveriesLayer.clearLayers();
    this.deliveryMarkers.clear();
  }

  // ============================================================================
  // ÉVÉNEMENTS
  // ============================================================================

  /**
   * Gère le clic sur un marqueur de livreur
   */
  private onDeliveryPersonMarkerClick(deliveryPerson: Driver): void {
    this.deliveryPersonClicked.emit(deliveryPerson);
  }

  /**
   * Gère le clic sur un marqueur de livraison
   */
  private onDeliveryMarkerClick(delivery: DeliveryLocation): void {
    this.deliveryClicked.emit(delivery);
  }

  // ============================================================================
  // MÉTHODES UTILITAIRES
  // ============================================================================

  /**
   * Ajuste la vue pour afficher tous les marqueurs
   */
  private fitBoundsToMarkers(): void {
    const bounds = L.latLngBounds([]);
    
    this.deliveryPersonMarkers.forEach(marker => {
      bounds.extend(marker.getLatLng());
    });

    if (bounds.isValid()) {
      this.map?.fitBounds(bounds, { padding: [50, 50] });
    }
  }

  /**
   * Formate une date pour l'affichage
   */
  private formatDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'À l\'instant';
    if (diffMins < 60) return `Il y a ${diffMins} min`;
    if (diffMins < 1440) return `Il y a ${Math.floor(diffMins / 60)} h`;
    return `Il y a ${Math.floor(diffMins / 1440)} j`;
  }

  /**
   * Gère les erreurs
   */
  private handleError(error: Error): void {
    console.error('[MapComponent] Error:', error);
    this.error = error.message || 'Une erreur est survenue';
    this.mapError.emit(error);
  }

  /**
   * Nettoie les ressources de la carte
   */
  private cleanupMap(): void {
    if (this.map) {
      this.map.remove();
      this.map = undefined;
    }
  }

  // ============================================================================
  // MÉTHODES PUBLIQUES (API du composant)
  // ============================================================================

  /**
   * Recharge manuellement les données
   */
  public refresh(): void {
    this.loadData();
  }

  /**
   * Centre la carte sur des coordonnées spécifiques
   */
  public centerOn(lat: number, lng: number, zoom?: number): void {
    this.map?.setView([lat, lng], zoom || this.map.getZoom());
  }

  /**
   * Toggle l'affichage des destinations de livraison
   */
  public toggleDeliveryDestinations(): void {
    this.showDeliveryDestinations = !this.showDeliveryDestinations;
    
    if (this.showDeliveryDestinations) {
      this.loadAssignedDeliveries();
    } else {
      this.clearDeliveryMarkers();
    }
  }
}