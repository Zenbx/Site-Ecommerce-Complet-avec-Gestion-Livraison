/**
 * Modèle pour une localisation de livraison
 * 
 * Représente une destination de livraison avec ses coordonnées,
 * son statut, et les informations associées (commande, livreur, client)
 */

/**
 * Statuts possibles d'une livraison
 */
export enum DeliveryStatus {
  PENDING = 'pending',           // En attente d'assignation
  ASSIGNED = 'assigned',         // Assignée à un livreur
  ACCEPTED = 'accepted',         // Acceptée par le livreur
  PICKED_UP = 'picked_up',       // Récupérée (en route)
  IN_TRANSIT = 'in_transit',     // En transit
  DELIVERED = 'delivered',       // Livrée
  FAILED = 'failed',             // Échec de livraison
  CANCELLED = 'cancelled'        // Annulée
}

/**
 * Type de localisation
 */
export enum LocationType {
  PICKUP = 'pickup',             // Point de récupération (entrepôt/magasin)
  DELIVERY = 'delivery',         // Point de livraison (client)
  WAYPOINT = 'waypoint'          // Point intermédiaire
}

/**
 * Interface principale pour une localisation de livraison
 */
export interface DeliveryLocation {
  // Identifiants
  id: number;
  delivery_id: number;
  order_id: number;

  // Informations géographiques
  address: string;
  latitude: string | number;     // Peut être string depuis l'API
  longitude: string | number;
  city?: string;
  postal_code?: string;
  country?: string;
  formatted_address?: string;
  
  // Type et statut
  type: LocationType;
  status: DeliveryStatus;
  
  // Informations de livraison
  delivery_person_id?: number;
  delivery_person_name?: string;
  delivery_person_photo_url?: string;
  
  // Informations client
  client_id: number;
  client_name: string;
  client_phone: string;
  client_email?: string;
  
  // Informations de commande
  order_reference: string;
  order_total: number;
  items_count: number;
  
  // Instructions et notes
  delivery_instructions?: string;
  notes?: string;
  
  // Horaires
  estimated_arrival?: string;    // ISO 8601
  actual_arrival?: string;       // ISO 8601
  created_at: string;            // ISO 8601
  updated_at: string;            // ISO 8601
  
  // Métadonnées
  distance_from_delivery_person?: number;  // En mètres
  duration_from_delivery_person?: number;  // En secondes
  priority?: 'low' | 'normal' | 'high' | 'urgent';
  
  // QR Code pour confirmation
  qr_code?: string;
  
  // Preuves de livraison
  delivery_proof_photo_url?: string;
  signature_url?: string;
  
  // Problèmes signalés
  has_issues?: boolean;
  issue_description?: string;
}

/**
 * Interface simplifiée pour l'affichage sur la carte
 */
export interface DeliveryLocationMarker {
  id: number;
  latitude: number;
  longitude: number;
  type: LocationType;
  status: DeliveryStatus;
  address: string;
  clientName: string;
  deliveryPersonName?: string;
  priority?: 'low' | 'normal' | 'high' | 'urgent';
}

/**
 * Interface pour une route de livraison
 */
export interface DeliveryRoute {
  delivery_id: number;
  origin: {
    latitude: number;
    longitude: number;
    address: string;
    type: 'warehouse' | 'current_location';
  };
  destination: {
    latitude: number;
    longitude: number;
    address: string;
  };
  waypoints?: Array<{
    latitude: number;
    longitude: number;
    address: string;
    order: number;
  }>;
  route_coordinates: Array<[number, number]>;  // Polyline [lat, lng]
  total_distance: number;                      // En mètres
  total_duration: number;                      // En secondes
  estimated_arrival: string;                   // ISO 8601
}

/**
 * Interface pour le calcul de distance entre livreur et livraison
 */
export interface DeliveryDistance {
  delivery_id: number;
  delivery_person_id: number;
  distance: number;              // En mètres
  duration: number;              // En secondes
  distance_text: string;         // "2.5 km"
  duration_text: string;         // "8 min"
  is_nearby: boolean;            // < 5km par exemple
}

/**
 * Interface pour une zone de livraison
 */
export interface DeliveryZone {
  id: number;
  name: string;
  description?: string;
  polygon: Array<[number, number]>;  // Coordonnées du polygone
  color: string;                     // Couleur d'affichage
  is_active: boolean;
  delivery_fee?: number;
  estimated_duration?: number;       // En minutes
}

/**
 * Interface pour les statistiques de livraison
 */
export interface DeliveryLocationStats {
  total: number;
  pending: number;
  in_progress: number;
  completed: number;
  failed: number;
  by_zone?: Record<string, number>;
  average_delivery_time?: number;    // En minutes
  on_time_rate?: number;             // Pourcentage
}

/**
 * Type guard pour vérifier si une localisation a des coordonnées valides
 */
export function hasValidCoordinates(location: DeliveryLocation | DeliveryLocationMarker): boolean {
  const lat = typeof location.latitude === 'string' 
    ? parseFloat(location.latitude) 
    : location.latitude;
  
  const lng = typeof location.longitude === 'string'
    ? parseFloat(location.longitude)
    : location.longitude;
  
  return (
    !isNaN(lat) &&
    !isNaN(lng) &&
    lat >= -90 &&
    lat <= 90 &&
    lng >= -180 &&
    lng <= 180
  );
}

/**
 * Convertit une DeliveryLocation en DeliveryLocationMarker
 */
export function toMarker(location: DeliveryLocation): DeliveryLocationMarker {
  return {
    id: location.id,
    latitude: typeof location.latitude === 'string' 
      ? parseFloat(location.latitude) 
      : location.latitude,
    longitude: typeof location.longitude === 'string'
      ? parseFloat(location.longitude)
      : location.longitude,
    type: location.type,
    status: location.status,
    address: location.address,
    clientName: location.client_name,
    deliveryPersonName: location.delivery_person_name,
    priority: location.priority
  };
}

/**
 * Détermine la couleur du marqueur selon le statut
 */
export function getMarkerColorByStatus(status: DeliveryStatus): string {
  const colors: Record<DeliveryStatus, string> = {
    [DeliveryStatus.PENDING]: '#9ca3af',       // gray-400
    [DeliveryStatus.ASSIGNED]: '#3b82f6',      // blue-500
    [DeliveryStatus.ACCEPTED]: '#8b5cf6',      // violet-500
    [DeliveryStatus.PICKED_UP]: '#f59e0b',     // amber-500
    [DeliveryStatus.IN_TRANSIT]: '#f59e0b',    // amber-500
    [DeliveryStatus.DELIVERED]: '#10b981',     // green-500
    [DeliveryStatus.FAILED]: '#ef4444',        // red-500
    [DeliveryStatus.CANCELLED]: '#6b7280'      // gray-500
  };
  
  return colors[status] || '#9ca3af';
}

/**
 * Retourne un libellé français pour le statut
 */
export function getStatusLabel(status: DeliveryStatus): string {
  const labels: Record<DeliveryStatus, string> = {
    [DeliveryStatus.PENDING]: 'En attente',
    [DeliveryStatus.ASSIGNED]: 'Assignée',
    [DeliveryStatus.ACCEPTED]: 'Acceptée',
    [DeliveryStatus.PICKED_UP]: 'Récupérée',
    [DeliveryStatus.IN_TRANSIT]: 'En transit',
    [DeliveryStatus.DELIVERED]: 'Livrée',
    [DeliveryStatus.FAILED]: 'Échec',
    [DeliveryStatus.CANCELLED]: 'Annulée'
  };
  
  return labels[status] || status;
}

/**
 * Retourne une icône emoji pour le statut
 */
export function getStatusIcon(status: DeliveryStatus): string {
  const icons: Record<DeliveryStatus, string> = {
    [DeliveryStatus.PENDING]: '⏳',
    [DeliveryStatus.ASSIGNED]: '📋',
    [DeliveryStatus.ACCEPTED]: '✅',
    [DeliveryStatus.PICKED_UP]: '📦',
    [DeliveryStatus.IN_TRANSIT]: '🚚',
    [DeliveryStatus.DELIVERED]: '✅',
    [DeliveryStatus.FAILED]: '❌',
    [DeliveryStatus.CANCELLED]: '🚫'
  };
  
  return icons[status] || '📍';
}

/**
 * Détermine si une livraison est en cours (active)
 */
export function isActiveDelivery(status: DeliveryStatus): boolean {
  return [
    DeliveryStatus.ASSIGNED,
    DeliveryStatus.ACCEPTED,
    DeliveryStatus.PICKED_UP,
    DeliveryStatus.IN_TRANSIT
  ].includes(status);
}

/**
 * Détermine si une livraison est terminée
 */
export function isCompletedDelivery(status: DeliveryStatus): boolean {
  return [
    DeliveryStatus.DELIVERED,
    DeliveryStatus.FAILED,
    DeliveryStatus.CANCELLED
  ].includes(status);
}

/**
 * Calcule le temps écoulé depuis une date
 */
export function getTimeElapsed(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);

  if (diffMins < 1) return 'À l\'instant';
  if (diffMins < 60) return `${diffMins} min`;
  if (diffMins < 1440) return `${Math.floor(diffMins / 60)} h`;
  return `${Math.floor(diffMins / 1440)} j`;
}