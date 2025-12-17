// src/app/features/map/map.component.ts

import { Component, OnInit, OnDestroy, Input, Output, EventEmitter, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { trigger, transition, style, animate } from '@angular/animations';
import * as L from 'leaflet';

/**
 * Interface représentant une livraison sur la carte
 * Cette interface est maintenant exportée pour pouvoir être utilisée par d'autres composants
 */
export interface Delivery {
  id: number;
  orderNumber: string;
  status: 'pending' | 'assigned' | 'in_transit' | 'delivered' | 'failed';
  customer: {
    name: string;
    phone: string;
  };
  address: {
    street: string;
    city: string;
    postalCode: string;
    country: string;
    latitude: number;
    longitude: number;
  };
  driver?: {
    id: number;
    firstName: string;
    lastName: string;
    phone: string;
  };
  priority?: 'high' | 'medium' | 'low';
}

// Configuration des icônes Leaflet
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

@Component({
  selector: 'app-map',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './map.component.html',
  styleUrls: ['./map.component.scss'],
  animations: [
    trigger('slideIn', [
      transition(':enter', [
        style({ transform: 'translateX(-100%)', opacity: 0 }),
        animate('300ms cubic-bezier(0.4, 0, 0.2, 1)', 
          style({ transform: 'translateX(0)', opacity: 1 }))
      ]),
      transition(':leave', [
        animate('300ms cubic-bezier(0.4, 0, 0.2, 1)', 
          style({ transform: 'translateX(-100%)', opacity: 0 }))
      ])
    ])
  ]
})
export class MapComponent implements OnInit, OnDestroy, OnChanges {
  /**
   * PROPRIÉTÉS D'ENTRÉE (@Input)
   * 
   * Le décorateur @Input() transforme une propriété en point d'entrée pour les données.
   * Cela signifie que le composant parent peut maintenant passer des valeurs à ces propriétés
   * en utilisant la syntaxe de binding [propertyName]="value" dans son template.
   * 
   * Ces propriétés créent un contrat clair : "Je suis un composant qui peut recevoir
   * ces informations de l'extérieur". C'est l'équivalent des paramètres d'une fonction,
   * mais pour un composant Angular.
   */
  
  /**
   * Tableau des livraisons à afficher sur la carte
   * 
   * Le parent peut maintenant contrôler quelles livraisons sont affichées en passant
   * un tableau. Si le parent change ce tableau, Angular détectera automatiquement
   * le changement et le composant mettra à jour la carte via OnChanges.
   * 
   * Le point d'exclamation indique à TypeScript que cette propriété sera initialisée
   * par Angular via l'Input, même si elle n'a pas de valeur par défaut explicite.
   */
  @Input() deliveries!: Delivery[];
  
  /**
   * État de chargement contrôlé par le parent
   * 
   * Le parent peut indiquer que les données sont en cours de chargement,
   * et notre composant affichera l'état de chargement approprié.
   * La valeur par défaut false signifie que si le parent ne passe rien,
   * nous considérons que les données ne sont pas en cours de chargement.
   */
  @Input() loading = false;
  
  /**
   * Message d'erreur contrôlé par le parent
   * 
   * Si le parent rencontre une erreur lors du chargement des données,
   * il peut nous le communiquer et nous afficherons un message d'erreur approprié.
   * Le type "string | null" signifie que cette propriété peut contenir
   * soit une chaîne de caractères (le message d'erreur) soit null (pas d'erreur).
   */
  @Input() error: string | null = null;
  
  /**
   * Hauteur personnalisée de la carte
   * 
   * Cette propriété optionnelle permet au parent de contrôler la hauteur de la carte.
   * Si le parent ne spécifie rien, la carte utilisera la hauteur par défaut définie
   * dans le CSS. C'est un exemple de propriété d'Input optionnelle qui offre de la
   * flexibilité sans être obligatoire.
   */
  @Input() height?: string;

  /**
   * PROPRIÉTÉS DE SORTIE (@Output)
   * 
   * Le décorateur @Output() crée un canal de communication dans le sens inverse :
   * du composant enfant vers le composant parent. Quand quelque chose d'important
   * se passe dans notre composant (comme la sélection d'une livraison), nous pouvons
   * "émettre" un événement que le parent peut écouter et traiter.
   * 
   * C'est comme lever la main en classe pour signaler quelque chose à l'enseignant.
   * Le parent décide ensuite quoi faire avec cette information.
   */
  
  /**
   * Événement émis quand l'utilisateur sélectionne une livraison
   * 
   * EventEmitter est un type spécial Angular qui permet d'émettre des événements.
   * Le type générique <Delivery> indique que cet événement transportera
   * un objet Delivery quand il sera émis.
   * 
   * Le parent peut écouter cet événement avec la syntaxe :
   * (deliverySelected)="onDeliverySelected($event)"
   * où $event contiendra l'objet Delivery sélectionné.
   */
  @Output() deliverySelected = new EventEmitter<Delivery>();
  
  /**
   * Événement émis quand l'utilisateur demande à voir les détails
   * 
   * Cet événement permet au parent de gérer la navigation ou l'affichage
   * des détails de la manière qui convient le mieux à son architecture.
   */
  @Output() viewDetailsRequested = new EventEmitter<Delivery>();

  /**
   * PROPRIÉTÉS PRIVÉES DE LA CARTE
   * 
   * Ces propriétés restent privées car elles sont des détails d'implémentation
   * interne que le parent n'a pas besoin de connaître ou de contrôler.
   */
  private map: L.Map | null = null;
  private markersLayer: L.LayerGroup | null = null;
  private markers: Map<number, L.Marker> = new Map();

  /**
   * PROPRIÉTÉS PUBLIQUES D'ÉTAT
   * 
   * Ces propriétés contrôlent l'interface utilisateur interne du composant.
   */
  showLegend = true;
  selectedDelivery: Delivery | null = null;

  constructor(private router: Router) {}

  /**
   * HOOK DE CYCLE DE VIE : ngOnInit
   * 
   * Cette méthode est appelée une fois après la première vérification des propriétés
   * d'entrée. C'est le moment idéal pour initialiser la carte car nous sommes sûrs
   * que toutes les propriétés @Input() ont été définies.
   */
  ngOnInit(): void {
    setTimeout(() => {
      this.initializeMap();
    }, 0);
  }

  /**
   * HOOK DE CYCLE DE VIE : ngOnChanges
   * 
   * Cette méthode est absolument cruciale pour un composant réutilisable avec des Inputs.
   * Elle est appelée automatiquement par Angular chaque fois qu'une propriété @Input()
   * change. Cela nous permet de réagir aux changements de données venant du parent.
   * 
   * Par exemple, si le parent charge de nouvelles livraisons et met à jour la propriété
   * deliveries, Angular appellera automatiquement cette méthode avec les anciennes
   * et nouvelles valeurs. Nous pouvons alors mettre à jour la carte en conséquence.
   * 
   * @param changes - Un objet contenant toutes les propriétés qui ont changé
   */
  ngOnChanges(changes: SimpleChanges): void {
    // Vérifier si la propriété deliveries a changé
    if (changes['deliveries'] && !changes['deliveries'].firstChange) {
      // Ce n'est pas le premier changement (qui est géré par ngOnInit)
      // donc nous devons mettre à jour les marqueurs avec les nouvelles données
      console.log('📊 Mise à jour des livraisons sur la carte');
      this.addMarkers();
      this.fitBounds();
    }
  }

  /**
   * HOOK DE CYCLE DE VIE : ngOnDestroy
   * 
   * Nettoyage des ressources pour éviter les fuites mémoire.
   */
  ngOnDestroy(): void {
    if (this.map) {
      this.map.remove();
      this.map = null;
    }
  }

  /**
   * Initialiser la carte Leaflet
   * 
   * Cette méthode reste largement identique à la version précédente,
   * mais elle est maintenant plus flexible car elle travaille avec
   * des données qui peuvent venir de l'extérieur.
   */
  private initializeMap(): void {
    try {
      const mapElement = document.getElementById('map');
      if (!mapElement) {
        console.error('❌ Élément de carte introuvable');
        return;
      }

      // Appliquer la hauteur personnalisée si fournie
      if (this.height) {
        mapElement.style.height = this.height;
      }

      // Créer la carte centrée sur Douala
      this.map = L.map('map').setView([4.0511, 9.7679], 12);

      // Ajouter les tuiles OpenStreetMap
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
      }).addTo(this.map);

      // Créer le groupe de calques pour les marqueurs
      this.markersLayer = L.layerGroup().addTo(this.map);

      // Ajouter les marqueurs si nous avons déjà des livraisons
      if (this.deliveries && this.deliveries.length > 0) {
        this.addMarkers();
        this.fitBounds();
      }

      console.log('✅ Carte initialisée avec succès');
    } catch (err) {
      console.error('❌ Erreur lors de l\'initialisation de la carte:', err);
    }
  }

  /**
   * Ajouter les marqueurs de livraison sur la carte
   */
  private addMarkers(): void {
    if (!this.map || !this.markersLayer || !this.deliveries) return;

    // Nettoyer les marqueurs existants
    this.markersLayer.clearLayers();
    this.markers.clear();

    // Créer un marqueur pour chaque livraison
    this.deliveries.forEach(delivery => {
      const iconHtml = this.createMarkerIcon(delivery.status);
      
      const customIcon = L.divIcon({
        html: iconHtml,
        className: 'custom-marker',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32]
      });

      const marker = L.marker(
        [delivery.address.latitude, delivery.address.longitude],
        { icon: customIcon }
      );

      // Quand l'utilisateur clique sur un marqueur, nous émettons un événement
      // plutôt que de gérer directement la sélection. Cela donne au parent
      // le contrôle sur ce qui devrait se passer.
      marker.on('click', () => {
        this.onMarkerClick(delivery);
      });

      const popupContent = `
        <div class="marker-popup">
          <strong>${delivery.orderNumber}</strong><br>
          ${delivery.customer.name}<br>
          <span class="status-${delivery.status}">
            ${this.getStatusLabel(delivery.status)}
          </span>
        </div>
      `;
      marker.bindPopup(popupContent);

      this.markersLayer!.addLayer(marker);
      this.markers.set(delivery.id, marker);
    });

    console.log(`📍 ${this.deliveries.length} marqueurs ajoutés`);
  }

  /**
   * Gérer le clic sur un marqueur
   * 
   * Cette méthode émet un événement pour informer le parent qu'une livraison
   * a été sélectionnée. Le parent peut alors décider quoi faire avec cette information.
   * 
   * @param delivery - La livraison qui a été cliquée
   */
  private onMarkerClick(delivery: Delivery): void {
    this.selectedDelivery = delivery;
    
    // Centrer la carte sur le marqueur
    if (this.map) {
      this.map.setView(
        [delivery.address.latitude, delivery.address.longitude],
        15,
        { animate: true, duration: 0.5 }
      );
    }

    // Émettre l'événement pour informer le parent
    // C'est comme dire : "Quelque chose d'important s'est passé, voici les détails"
    this.deliverySelected.emit(delivery);
    
    console.log('📍 Livraison sélectionnée:', delivery.orderNumber);
  }

  /**
   * Créer l'icône HTML d'un marqueur
   */
  private createMarkerIcon(status: string): string {
    let color = '#1a73e8';
    
    switch (status) {
      case 'pending': color = '#fbbc04'; break;
      case 'assigned': color = '#1a73e8'; break;
      case 'in_transit': color = '#4285f4'; break;
      case 'delivered': color = '#34a853'; break;
      case 'failed': color = '#ea4335'; break;
    }

    return `
      <svg width="32" height="32" viewBox="0 0 32 32">
        <circle cx="16" cy="16" r="12" fill="${color}" stroke="white" stroke-width="3"/>
        <circle cx="16" cy="16" r="5" fill="white" opacity="0.8"/>
      </svg>
    `;
  }

  /**
   * Ajuster la vue pour montrer tous les marqueurs
   */
  private fitBounds(): void {
    if (!this.map || !this.deliveries || this.deliveries.length === 0) return;

    const bounds = this.deliveries.map(d => 
      [d.address.latitude, d.address.longitude] as [number, number]
    );

    if (bounds.length > 0) {
      this.map.fitBounds(bounds, {
        padding: [50, 50],
        maxZoom: 15
      });
    }
  }

  /**
   * Fermer le panneau d'informations
   */
  closePanel(): void {
    this.selectedDelivery = null;
  }

  /**
   * Voir les détails d'une livraison
   * 
   * Au lieu de naviguer directement, nous émettons un événement pour que
   * le parent puisse gérer la navigation de la manière qui lui convient.
   * 
   * @param delivery - La livraison dont on veut voir les détails
   */
  viewDetails(delivery: Delivery): void {
    // Émettre l'événement pour demander au parent d'afficher les détails
    this.viewDetailsRequested.emit(delivery);
  }

  /**
   * MÉTHODES DE CONTRÔLE DE LA CARTE
   */

  zoomIn(): void {
    if (this.map) this.map.zoomIn();
  }

  zoomOut(): void {
    if (this.map) this.map.zoomOut();
  }

  centerMap(): void {
    this.fitBounds();
  }

  toggleFullscreen(): void {
    const mapElement = document.getElementById('map');
    if (!mapElement) return;

    if (!document.fullscreenElement) {
      mapElement.requestFullscreen().catch(err => {
        console.error('Erreur plein écran:', err);
      });
    } else {
      document.exitFullscreen();
    }
  }

  /**
   * MÉTHODES UTILITAIRES
   */

  getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      'pending': 'En attente',
      'assigned': 'Assignée',
      'in_transit': 'En transit',
      'delivered': 'Livrée',
      'failed': 'Échouée'
    };
    return labels[status] || status;
  }

  getStatusClass(status: string): string {
    return `status-${status}`;
  }
}