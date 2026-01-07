/**
 * Modèles de configuration pour les cartes Leaflet
 * 
 * Définit les types et interfaces pour configurer
 * le comportement et l'apparence des cartes
 */

import { LatLngExpression } from 'leaflet';

/**
 * Modes d'affichage de la carte
 */
export type MapMode = 'overview' | 'tracking';

/**
 * Types de tiles (fonds de carte)
 */
export enum TileProvider {
  OPENSTREETMAP = 'openstreetmap',
  OPENSTREETMAP_FR = 'openstreetmap_fr',
  CARTODB_POSITRON = 'cartodb_positron',
  CARTODB_DARK = 'cartodb_dark',
  ESRI_WORLDIMAGERY = 'esri_worldimagery'
}

/**
 * Configuration principale de la carte
 */
export interface MapConfig {
  // Position initiale
  center: LatLngExpression;      // [latitude, longitude]
  zoom: number;                  // Niveau de zoom (1-18)
  
  // Limites de zoom
  minZoom?: number;
  maxZoom?: number;
  
  // Limites géographiques (bounds)
  maxBounds?: [[number, number], [number, number]];
  
  // Options d'interaction
  zoomControl?: boolean;
  scrollWheelZoom?: boolean;
  doubleClickZoom?: boolean;
  dragging?: boolean;
  touchZoom?: boolean;
  
  // Provider de tiles
  tileProvider?: TileProvider;
  
  // Attribution personnalisée
  attribution?: string;
}

/**
 * Configuration des marqueurs
 */
export interface MarkerConfig {
  // Taille de l'icône
  iconSize?: [number, number];
  iconAnchor?: [number, number];
  popupAnchor?: [number, number];
  
  // Apparence
  color?: string;
  borderColor?: string;
  borderWidth?: number;
  
  // Animation
  animated?: boolean;
  pulseAnimation?: boolean;
  
  // Clustering
  clustering?: boolean;
  clusterRadius?: number;
  maxClusterRadius?: number;
}

/**
 * Configuration des popups
 */
export interface PopupConfig {
  // Dimensions
  maxWidth?: number;
  minWidth?: number;
  maxHeight?: number;
  
  // Comportement
  autoClose?: boolean;
  closeButton?: boolean;
  closeOnClick?: boolean;
  
  // Position
  offset?: [number, number];
  
  // Style
  className?: string;
}

/**
 * Configuration des routes (polylines)
 */
export interface RouteConfig {
  // Apparence
  color?: string;
  weight?: number;
  opacity?: number;
  dashArray?: string;
  lineCap?: 'butt' | 'round' | 'square';
  lineJoin?: 'miter' | 'round' | 'bevel';
  
  // Animation
  animated?: boolean;
  animationSpeed?: number;  // Vitesse en ms
  
  // Flèches directionnelles
  showDirectionArrows?: boolean;
  arrowInterval?: number;   // Distance entre flèches en pixels
}

/**
 * Configuration du clustering des marqueurs
 */
export interface ClusterConfig {
  enabled: boolean;
  maxClusterRadius?: number;     // Rayon de clustering en pixels
  showCoverageOnHover?: boolean;
  zoomToBoundsOnClick?: boolean;
  spiderfyOnMaxZoom?: boolean;
  removeOutsideVisibleBounds?: boolean;
  disableClusteringAtZoom?: number;
  
  // Style des clusters
  iconCreateFunction?: (cluster: any) => any;
}

/**
 * Configuration des contrôles de la carte
 */
export interface MapControlsConfig {
  // Contrôles de zoom
  zoomControl?: {
    position: 'topleft' | 'topright' | 'bottomleft' | 'bottomright';
  };
  
  // Contrôle de couches (layers)
  layersControl?: {
    position: 'topleft' | 'topright' | 'bottomleft' | 'bottomright';
    collapsed?: boolean;
  };
  
  // Contrôle d'échelle
  scaleControl?: {
    position: 'topleft' | 'topright' | 'bottomleft' | 'bottomright';
    metric?: boolean;
    imperial?: boolean;
  };
  
  // Contrôle de localisation
  locateControl?: {
    position: 'topleft' | 'topright' | 'bottomleft' | 'bottomright';
    drawCircle?: boolean;
    follow?: boolean;
  };
  
  // Contrôle de mesure de distance
  measureControl?: {
    position: 'topleft' | 'topright' | 'bottomleft' | 'bottomright';
    primaryLengthUnit?: 'meters' | 'kilometers' | 'feet' | 'miles';
  };
}

/**
 * Configuration des filtres de carte
 */
export interface MapFiltersConfig {
  // Filtrer les livreurs
  showOnlineDeliveryPersons?: boolean;
  showOfflineDeliveryPersons?: boolean;
  showAvailableOnly?: boolean;
  
  // Filtrer les livraisons
  showPendingDeliveries?: boolean;
  showActiveDeliveries?: boolean;
  showCompletedDeliveries?: boolean;
  
  // Filtres par priorité
  showHighPriority?: boolean;
  showNormalPriority?: boolean;
  showLowPriority?: boolean;
  
  // Filtres temporels
  showLastHour?: boolean;
  showLast24Hours?: boolean;
  showLastWeek?: boolean;
}

/**
 * Configuration du mode tracking
 */
export interface TrackingConfig {
  // Suivi automatique
  autoCenter?: boolean;           // Recentrer automatiquement sur le livreur
  autoCenterInterval?: number;    // Intervalle en ms
  
  // Traçage du parcours
  showTrail?: boolean;            // Afficher la trace du parcours
  trailMaxPoints?: number;        // Nombre max de points de la trace
  trailColor?: string;
  trailOpacity?: number;
  
  // Estimation d'arrivée
  showETA?: boolean;              // Afficher l'heure d'arrivée estimée
  updateETAInterval?: number;     // Intervalle de mise à jour en ms
  
  // Notifications
  notifyOnArrival?: boolean;
  notifyRadius?: number;          // Rayon en mètres pour notification
}

/**
 * Configuration complète de la carte
 */
export interface FullMapConfig {
  map: MapConfig;
  markers?: MarkerConfig;
  popups?: PopupConfig;
  routes?: RouteConfig;
  clustering?: ClusterConfig;
  controls?: MapControlsConfig;
  filters?: MapFiltersConfig;
  tracking?: TrackingConfig;
}

/**
 * Configurations prédéfinies
 */
export const MAP_PRESETS = {
  /**
   * Configuration pour la vue d'ensemble (Cameroun)
   */
  CAMEROON_OVERVIEW: {
    center: [3.8667, 11.5167] as LatLngExpression,  // Yaoundé
    zoom: 12,
    minZoom: 10,
    maxZoom: 18,
    tileProvider: TileProvider.OPENSTREETMAP
  } as MapConfig,
  
  /**
   * Configuration pour Yaoundé
   */
  YAOUNDE: {
    center: [3.8667, 11.5167] as LatLngExpression,
    zoom: 13,
    minZoom: 11,
    maxZoom: 18,
    tileProvider: TileProvider.OPENSTREETMAP
  } as MapConfig,
  
  /**
   * Configuration pour Douala
   */
  DOUALA: {
    center: [4.0511, 9.7679] as LatLngExpression,
    zoom: 13,
    minZoom: 11,
    maxZoom: 18,
    tileProvider: TileProvider.OPENSTREETMAP
  } as MapConfig,
  
  /**
   * Configuration pour le mode tracking (zoom serré)
   */
  TRACKING_MODE: {
    center: [3.8667, 11.5167] as LatLngExpression,
    zoom: 15,
    minZoom: 13,
    maxZoom: 18,
    tileProvider: TileProvider.OPENSTREETMAP,
    scrollWheelZoom: true,
    dragging: true
  } as MapConfig,
  
  /**
   * Configuration pour le mode dark (tableau de bord nocturne)
   */
  DARK_MODE: {
    center: [3.8667, 11.5167] as LatLngExpression,
    zoom: 12,
    minZoom: 10,
    maxZoom: 18,
    tileProvider: TileProvider.CARTODB_DARK
  } as MapConfig
};

/**
 * URLs des tile providers
 */
export const TILE_PROVIDER_URLS: Record<TileProvider, string> = {
  [TileProvider.OPENSTREETMAP]: 
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
  [TileProvider.OPENSTREETMAP_FR]: 
    'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
  [TileProvider.CARTODB_POSITRON]: 
    'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
  [TileProvider.CARTODB_DARK]: 
    'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',
  [TileProvider.ESRI_WORLDIMAGERY]: 
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
};

/**
 * Attributions des tile providers
 */
export const TILE_ATTRIBUTIONS: Record<TileProvider, string> = {
  [TileProvider.OPENSTREETMAP]: 
    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  [TileProvider.OPENSTREETMAP_FR]: 
    '&copy; OpenStreetMap France | &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  [TileProvider.CARTODB_POSITRON]: 
    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
  [TileProvider.CARTODB_DARK]: 
    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
  [TileProvider.ESRI_WORLDIMAGERY]: 
    'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
};

/**
 * Helper pour créer une config par défaut
 */
export function createDefaultMapConfig(mode: MapMode = 'overview'): FullMapConfig {
  const baseConfig = mode === 'tracking' 
    ? MAP_PRESETS.TRACKING_MODE 
    : MAP_PRESETS.CAMEROON_OVERVIEW;
  
  return {
    map: {
      ...baseConfig,
      zoomControl: true,
      scrollWheelZoom: true,
      doubleClickZoom: true,
      dragging: true,
      touchZoom: true
    },
    markers: {
      iconSize: [32, 32],
      iconAnchor: [16, 16],
      popupAnchor: [0, -16],
      animated: true,
      pulseAnimation: mode === 'tracking',
      clustering: mode === 'overview',
      clusterRadius: 80
    },
    popups: {
      maxWidth: 300,
      minWidth: 200,
      autoClose: false,
      closeButton: true,
      closeOnClick: false
    },
    routes: {
      color: '#3b82f6',
      weight: 4,
      opacity: 0.7,
      dashArray: '10, 5',
      animated: mode === 'tracking',
      showDirectionArrows: true
    },
    controls: {
      zoomControl: {
        position: 'topright'
      },
      scaleControl: {
        position: 'bottomleft',
        metric: true,
        imperial: false
      }
    },
    filters: {
      showOnlineDeliveryPersons: true,
      showOfflineDeliveryPersons: true,
      showAvailableOnly: false,
      showActiveDeliveries: true,
      showCompletedDeliveries: false
    },
    tracking: mode === 'tracking' ? {
      autoCenter: true,
      autoCenterInterval: 5000,
      showTrail: true,
      trailMaxPoints: 50,
      trailColor: '#3b82f6',
      trailOpacity: 0.5,
      showETA: true,
      updateETAInterval: 30000
    } : undefined
  };
}