import { Component, OnInit, OnDestroy, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';

import { MapComponent } from '../../features/map/map.component';
import { DeliveryPersonLocationService } from '../../core/services/delivery-person-location.service';
import { Driver } from '../../core/models/driver.model';

/**
 * Interface pour les filtres de la carte
 */
interface MapFilters {
  onlineOnly: boolean;
  availableOnly: boolean;
  withDeliveries: boolean;
}

/**
 * Interface pour les statistiques globales
 */
interface GlobalStats {
  online: number;
  available: number;
  activeDeliveries: number;
  completedToday: number;
  avgResponseTime: number;
  onlineChange: number;
  responseTimeChange: number;
}

/**
 * Page de vue d'ensemble de la carte
 * 
 * Affiche tous les livreurs sur une carte avec:
 * - Statistiques globales
 * - Filtres rapides
 * - Liste latérale des livreurs
 * - Recherche
 */
@Component({
  selector: 'app-map-overview',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MapComponent
  ],
  templateUrl: './map-overview.component.html',
  // styleUrls: ['./map-overview.component.scss']
})
export class MapOverviewComponent implements OnInit, OnDestroy {
  @ViewChild(MapComponent) mapComponent!: MapComponent;

  // ============================================================================
  // PROPRIÉTÉS PUBLIQUES
  // ============================================================================

  // Panneau latéral
  sidePanelOpen: boolean = true;
  
  // Affichage des destinations
  showDeliveryDestinations: boolean = false;
  
  // Intervalle de refresh (secondes)
  refreshInterval: number = 10;
  
  // Recherche
  searchQuery: string = '';
  
  // Filtres
  filters: MapFilters = {
    onlineOnly: false,
    availableOnly: false,
    withDeliveries: false
  };
  
  // Statistiques
  stats: GlobalStats = {
    online: 0,
    available: 0,
    activeDeliveries: 0,
    completedToday: 0,
    avgResponseTime: 0,
    onlineChange: 0,
    responseTimeChange: 0
  };
  
  // Données
  deliveryPersons: Driver[] = [];
  filteredDeliveryPersons: Driver[] = [];
  selectedDeliveryPerson: Driver | null = null;

  // ============================================================================
  // PROPRIÉTÉS PRIVÉES
  // ============================================================================

  private destroy$ = new Subject<void>();

  // ============================================================================
  // CONSTRUCTOR
  // ============================================================================

  constructor(
    private deliveryPersonLocationService: DeliveryPersonLocationService,
    private router: Router
  ) {}

  // ============================================================================
  // LIFECYCLE HOOKS
  // ============================================================================

  ngOnInit(): void {
    this.loadDeliveryPersons();
    this.loadStatistics();
    
    // S'abonner aux mises à jour du cache
    this.deliveryPersonLocationService.deliveryPersons$
      .pipe(takeUntil(this.destroy$))
      .subscribe(persons => {
        this.deliveryPersons = persons;
        this.filterDeliveryPersons();
        this.updateStats();
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  // ============================================================================
  // CHARGEMENT DES DONNÉES
  // ============================================================================

  /**
   * Charge la liste des livreurs
   */
  private loadDeliveryPersons(): void {
    this.deliveryPersonLocationService.getAllLocations(true)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          this.deliveryPersons = response.data;
          this.filterDeliveryPersons();
        },
        error: (error) => {
          console.error('[MapOverview] Error loading delivery persons:', error);
        }
      });
  }

  /**
   * Charge les statistiques globales
   */
  private loadStatistics(): void {
    // TODO: Implémenter l'appel API pour les statistiques
    // Pour l'instant, calculer depuis les données locales
    this.updateStats();
  }

  /**
   * Met à jour les statistiques depuis les données locales
   */
  private updateStats(): void {
    this.stats.online = this.deliveryPersonLocationService.getOnlineDeliveryPersons().length;
    this.stats.available = this.deliveryPersonLocationService.getAvailableCount();
    
    // TODO: Récupérer les vraies stats depuis l'API
    this.stats.activeDeliveries = 12;
    this.stats.completedToday = 45;
    this.stats.avgResponseTime = 8;
    this.stats.onlineChange = 2;
    this.stats.responseTimeChange = -1;
  }

  // ============================================================================
  // FILTRAGE ET RECHERCHE
  // ============================================================================

  /**
   * Filtre les livreurs selon les critères actifs
   */
  filterDeliveryPersons(): void {
    let filtered = [...this.deliveryPersons];

    // Filtre: En ligne uniquement
    if (this.filters.onlineOnly) {
      filtered = filtered.filter(dp => dp.is_online);
    }

    // Filtre: Disponibles uniquement
    if (this.filters.availableOnly) {
      filtered = filtered.filter(dp => dp.is_available);
    }

    // Filtre: Avec livraisons
    if (this.filters.withDeliveries) {
      // TODO: Filtrer ceux qui ont des livraisons actives
    }

    // Recherche par nom ou email
    if (this.searchQuery.trim()) {
      const query = this.searchQuery.toLowerCase();
      filtered = filtered.filter(dp => 
        dp.name.toLowerCase().includes(query) ||
        dp.email.toLowerCase().includes(query)
      );
    }

    // Trier: En ligne d'abord, puis par nom
    filtered.sort((a, b) => {
      if (a.is_online && !b.is_online) return -1;
      if (!a.is_online && b.is_online) return 1;
      return a.name.localeCompare(b.name);
    });

    this.filteredDeliveryPersons = filtered;
  }

  /**
   * Toggle un filtre
   */
  toggleFilter(filterName: keyof MapFilters): void {
    this.filters[filterName] = !this.filters[filterName];
    this.filterDeliveryPersons();
  }

  // ============================================================================
  // GESTION DES ÉVÉNEMENTS DE LA CARTE
  // ============================================================================

  /**
   * Gère le clic sur un marqueur de livreur
   */
  onDeliveryPersonClicked(deliveryPerson: Driver): void {
    this.selectedDeliveryPerson = deliveryPerson;
    
    // Scroller vers l'élément dans la liste
    setTimeout(() => {
      const element = document.querySelector(`.delivery-person-item.selected`);
      element?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
  }

  /**
   * Gère le clic sur un marqueur de livraison
   */
  onDeliveryClicked(delivery: any): void {
    console.log('[MapOverview] Delivery clicked:', delivery);
    // TODO: Naviguer vers la page de détail de la livraison
  }

  /**
   * Gère les erreurs de la carte
   */
  onMapError(error: Error): void {
    console.error('[MapOverview] Map error:', error);
    // TODO: Afficher une notification toast
  }

  // ============================================================================
  // ACTIONS SUR LES LIVREURS
  // ============================================================================

  /**
   * Sélectionne un livreur
   */
  selectDeliveryPerson(deliveryPerson: Driver): void {
    this.selectedDeliveryPerson = deliveryPerson;
    this.centerOnDeliveryPerson(deliveryPerson);
  }

  /**
   * Centre la carte sur un livreur
   */
  centerOnDeliveryPerson(deliveryPerson: Driver): void {
    if (!deliveryPerson.current_latitude || !deliveryPerson.current_longitude) {
      return;
    }

    const lat = parseFloat(deliveryPerson.current_latitude);
    const lng = parseFloat(deliveryPerson.current_longitude);

    this.mapComponent.centerOn(lat, lng, 16);
  }

  /**
   * Navigue vers la page de détail d'un livreur
   */
  viewDeliveryPersonDetails(deliveryPerson: Driver): void {
    this.router.navigate(['/admin/delivery-persons', deliveryPerson.id]);
  }

  // ============================================================================
  // ACTIONS GLOBALES
  // ============================================================================

  /**
   * Toggle le panneau latéral
   */
  toggleSidePanel(): void {
    this.sidePanelOpen = !this.sidePanelOpen;
  }

  // ============================================================================
  // MÉTHODES UTILITAIRES
  // ============================================================================

  /**
   * TrackBy pour ngFor (optimisation)
   */
  trackByDeliveryPersonId(index: number, item: Driver): number {
    return item.id;
  }

  /**
   * Formate une date en relatif (ex: "il y a 5 min")
   */
  formatRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'à l\'instant';
    if (diffMins < 60) return `il y a ${diffMins} min`;
    if (diffMins < 1440) return `il y a ${Math.floor(diffMins / 60)} h`;
    return `il y a ${Math.floor(diffMins / 1440)} j`;
  }
}