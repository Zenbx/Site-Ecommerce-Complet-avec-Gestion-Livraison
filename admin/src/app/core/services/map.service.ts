import { Injectable } from '@angular/core';
import * as L from 'leaflet';

import { Driver } from '../models/driver.model';

/**
 * Configuration des couleurs des marqueurs
 */
interface MarkerColors {
  online: string;
  offline: string;
  available: string;
  unavailable: string;
  delivery: string;
  client: string;
}

/**
 * Service utilitaire pour la gestion des cartes Leaflet
 * 
 * Fournit des méthodes pour :
 * - Créer des icônes personnalisées
 * - Créer des popups stylisés
 * - Dessiner des routes
 * - Gérer les bounds de la carte
 * - Utilitaires de manipulation de carte
 */
@Injectable({
  providedIn: 'root'
})
export class MapService {
  // Configuration des couleurs
  private readonly colors: MarkerColors = {
    online: '#10b981',      // Vert (Tailwind green-500)
    offline: '#6b7280',     // Gris (Tailwind gray-500)
    available: '#3b82f6',   // Bleu (Tailwind blue-500)
    unavailable: '#ef4444', // Rouge (Tailwind red-500)
    delivery: '#f59e0b',    // Orange (Tailwind amber-500)
    client: '#8b5cf6'       // Violet (Tailwind violet-500)
  };

  // ============================================================================
  // CRÉATION D'ICÔNES PERSONNALISÉES
  // ============================================================================

  /**
   * Crée une icône pour un livreur
   * 
   * @param isOnline - Le livreur est-il en ligne
   * @param isAvailable - Le livreur est-il disponible
   * @returns Icône Leaflet personnalisée
   */
  createDeliveryPersonIcon(isOnline: boolean, isAvailable: boolean = true): L.DivIcon {
    const color = isOnline ? this.colors.online : this.colors.offline;
    const borderColor = isAvailable ? this.colors.available : this.colors.unavailable;
    
    const iconHtml = `
      <div style="
        position: relative;
        width: 32px;
        height: 32px;
      ">
        <!-- Cercle principal -->
        <div style="
          position: absolute;
          width: 100%;
          height: 100%;
          background: ${color};
          border: 3px solid ${borderColor};
          border-radius: 50%;
          box-shadow: 0 2px 8px rgba(0,0,0,0.3);
          display: flex;
          align-items: center;
          justify-content: center;
        ">
          <!-- Icône de livreur -->
          <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
            <path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3zm0 18.5c-3.87-1.06-6.67-4.88-6.67-8.91V6.5L12 4.34l6.67 2.16v4.09c0 4.03-2.8 7.85-6.67 8.91z"/>
            <path d="M10.5 13.5l-2-2-1.06 1.06L10.5 15.62l6.06-6.06L15.5 8.5z"/>
          </svg>
        </div>
        
        ${isOnline ? `
          <!-- Indicateur "pulsating" pour les livreurs en ligne -->
          <div style="
            position: absolute;
            top: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            background: ${this.colors.online};
            border: 2px solid white;
            border-radius: 50%;
            animation: pulse 2s infinite;
          "></div>
        ` : ''}
      </div>
    `;

    return L.divIcon({
      html: iconHtml,
      className: 'delivery-person-marker',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
      popupAnchor: [0, -16]
    });
  }

  /**
   * Crée une icône pour une destination de livraison
   * 
   * @param isCompleted - La livraison est-elle terminée
   * @returns Icône Leaflet personnalisée
   */
  createDeliveryIcon(isCompleted: boolean = false): L.DivIcon {
    const color = isCompleted ? this.colors.online : this.colors.delivery;
    
    const iconHtml = `
      <div style="
        position: relative;
        width: 36px;
        height: 46px;
      ">
        <!-- Pin de localisation -->
        <svg width="36" height="46" viewBox="0 0 36 46" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
          <path d="M18 0C8.059 0 0 8.059 0 18c0 13.5 18 28 18 28s18-14.5 18-28c0-9.941-8.059-18-18-18z" 
                fill="${color}"/>
          <circle cx="18" cy="18" r="8" fill="white"/>
          ${isCompleted ? `
            <!-- Checkmark pour livraison terminée -->
            <path d="M16 22l-4-4 1.5-1.5L16 19l6.5-6.5L24 14z" fill="${color}"/>
          ` : `
            <!-- Package icon pour livraison en cours -->
            <path d="M14 14h8v8h-8z M14 14l4 4 4-4 M18 14v8" 
                  stroke="${color}" 
                  stroke-width="1.5" 
                  fill="none"/>
          `}
        </svg>
      </div>
    `;

    return L.divIcon({
      html: iconHtml,
      className: 'delivery-marker',
      iconSize: [36, 46],
      iconAnchor: [18, 46],
      popupAnchor: [0, -46]
    });
  }

  /**
   * Crée une icône pour un client
   * 
   * @returns Icône Leaflet personnalisée
   */
  createClientIcon(): L.DivIcon {
    const iconHtml = `
      <div style="
        width: 32px;
        height: 32px;
        background: ${this.colors.client};
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
      ">
        <!-- User icon -->
        <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
          <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
        </svg>
      </div>
    `;

    return L.divIcon({
      html: iconHtml,
      className: 'client-marker',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
      popupAnchor: [0, -16]
    });
  }

  // ============================================================================
  // CRÉATION DE POPUPS
  // ============================================================================

  /**
   * Crée un popup stylisé pour un livreur
   * 
   * @param deliveryPerson - Données du livreur
   * @returns HTML du popup
   */
  createDeliveryPersonPopup(deliveryPerson: Driver): string {
    const statusBadge = deliveryPerson.is_online 
      ? '<span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">🟢 En ligne</span>'
      : '<span style="background: #6b7280; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">⚫ Hors ligne</span>';
    
    const availabilityBadge = deliveryPerson.is_available
      ? '<span style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">✅ Disponible</span>'
      : '<span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">🚫 Non disponible</span>';

    return `
      <div style="min-width: 200px; font-family: system-ui, -apple-system, sans-serif;">
        <div style="display: flex; align-items: center; margin-bottom: 8px;">
          ${deliveryPerson.photo_url 
            ? `<img src="${deliveryPerson.photo_url}" 
                    style="width: 40px; height: 40px; border-radius: 50%; margin-right: 8px; object-fit: cover;" 
                    alt="${deliveryPerson.name}"/>`
            : `<div style="width: 40px; height: 40px; border-radius: 50%; background: #e5e7eb; margin-right: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #6b7280;">
                 ${deliveryPerson.name.charAt(0).toUpperCase()}
               </div>`
          }
          <div>
            <strong style="font-size: 14px;">${deliveryPerson.name}</strong>
            <div style="font-size: 11px; color: #6b7280;">${deliveryPerson.email}</div>
          </div>
        </div>
        
        <div style="display: flex; gap: 4px; margin-bottom: 8px;">
          ${statusBadge}
          ${availabilityBadge}
        </div>
        
        ${deliveryPerson.current_address 
          ? `<div style="font-size: 12px; color: #6b7280; margin-top: 8px;">
               📍 ${deliveryPerson.current_address}
             </div>`
          : ''
        }
        
        ${deliveryPerson.last_location_update 
          ? `<div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">
               Mis à jour: ${this.formatRelativeTime(deliveryPerson.last_location_update)}
             </div>`
          : ''
        }
      </div>
    `;
  }

  // ============================================================================
  // DESSIN DE ROUTES
  // ============================================================================

  /**
   * Dessine une route sur la carte
   * 
   * @param map - Instance de la carte Leaflet
   * @param coordinates - Tableau de coordonnées [lat, lng]
   * @param options - Options de style
   * @returns La polyline créée
   */
  drawRoute(
    map: L.Map,
    coordinates: Array<[number, number]>,
    options?: {
      color?: string;
      weight?: number;
      opacity?: number;
      dashArray?: string;
    }
  ): L.Polyline {
    const defaultOptions = {
      color: this.colors.available,
      weight: 4,
      opacity: 0.7,
      dashArray: '10, 5',
      ...options
    };

    const polyline = L.polyline(coordinates, defaultOptions).addTo(map);
    
    // Ajuster la vue pour afficher toute la route
    map.fitBounds(polyline.getBounds(), { padding: [50, 50] });
    
    return polyline;
  }

  /**
   * Dessine une ligne directe entre deux points
   * 
   * @param map - Instance de la carte
   * @param start - Coordonnées de départ [lat, lng]
   * @param end - Coordonnées d'arrivée [lat, lng]
   * @param color - Couleur de la ligne
   * @returns La polyline créée
   */
  drawStraightLine(
    map: L.Map,
    start: [number, number],
    end: [number, number],
    color?: string
  ): L.Polyline {
    return L.polyline([start, end], {
      color: color || this.colors.delivery,
      weight: 2,
      opacity: 0.5,
      dashArray: '5, 10'
    }).addTo(map);
  }

  // ============================================================================
  // GESTION DES BOUNDS
  // ============================================================================

  /**
   * Ajuste la carte pour afficher tous les marqueurs
   * 
   * @param map - Instance de la carte
   * @param markers - Tableau de marqueurs
   * @param padding - Padding en pixels [vertical, horizontal]
   */
  fitBoundsToMarkers(
    map: L.Map,
    markers: L.Marker[],
    padding: [number, number] = [50, 50]
  ): void {
    if (markers.length === 0) return;

    const bounds = L.latLngBounds(markers.map(m => m.getLatLng()));
    map.fitBounds(bounds, { padding });
  }

  /**
   * Crée un bounds à partir d'un tableau de coordonnées
   * 
   * @param coordinates - Tableau de [lat, lng]
   * @returns Bounds Leaflet
   */
  createBounds(coordinates: Array<[number, number]>): L.LatLngBounds {
    return L.latLngBounds(coordinates);
  }

  // ============================================================================
  // MÉTHODES UTILITAIRES
  // ============================================================================

  /**
   * Formate une date relative (ex: "Il y a 5 min")
   */
  private formatRelativeTime(dateString: string): string {
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
   * Ajoute l'animation CSS pour le pulse
   * (À appeler une seule fois au démarrage de l'app)
   */
  injectPulseAnimation(): void {
    if (document.getElementById('map-pulse-animation')) return;

    const style = document.createElement('style');
    style.id = 'map-pulse-animation';
    style.textContent = `
      @keyframes pulse {
        0% {
          transform: scale(1);
          opacity: 1;
        }
        50% {
          transform: scale(1.5);
          opacity: 0.5;
        }
        100% {
          transform: scale(1);
          opacity: 1;
        }
      }
    `;
    document.head.appendChild(style);
  }

  /**
   * Retourne les couleurs utilisées par le service
   */
  getColors(): Readonly<MarkerColors> {
    return { ...this.colors };
  }
}