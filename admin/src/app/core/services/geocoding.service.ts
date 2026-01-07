import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError, of } from 'rxjs';
import { catchError, map, shareReplay } from 'rxjs/operators';

import { environment } from '../../../environments/environment';

/**
 * Interface pour une coordonnée géographique
 */
export interface Coordinates {
  latitude: number;
  longitude: number;
}

/**
 * Interface de réponse du geocoding
 */
interface GeocodeResponse {
  success: boolean;
  data: {
    address: string;
    latitude: number;
    longitude: number;
    formatted_address?: string;
    city?: string;
    country?: string;
  };
}

/**
 * Interface de réponse du calcul de distance
 */
interface DistanceResponse {
  success: boolean;
  data: {
    distance: number; // en mètres
    duration: number; // en secondes
    distance_text: string; // "5.2 km"
    duration_text: string; // "15 min"
  };
}

/**
 * Interface de réponse du calcul de route
 */
interface RouteResponse {
  success: boolean;
  data: {
    route: Array<[number, number]>; // Array de [lat, lng]
    distance: number;
    duration: number;
    instructions?: string[];
  };
}

/**
 * Service de géocodage et calculs géographiques
 * 
 * Gère :
 * - Conversion adresse → coordonnées (geocoding)
 * - Conversion coordonnées → adresse (reverse geocoding)
 * - Calcul de distance entre deux points
 * - Calcul d'itinéraire
 * - Cache des résultats pour optimiser
 */
@Injectable({
  providedIn: 'root'
})
export class GeocodingService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/map`;

  // Cache pour le geocoding (évite les appels répétés pour la même adresse)
  private geocodeCache = new Map<string, Observable<GeocodeResponse>>();

  // ============================================================================
  // GEOCODING (Adresse → Coordonnées)
  // ============================================================================

  /**
   * Convertit une adresse en coordonnées GPS
   * 
   * @param address - Adresse à géocoder
   * @param useCache - Utiliser le cache (défaut: true)
   * @returns Observable avec les coordonnées
   * 
   * @example
   * geocodingService.geocode('Yaoundé, Cameroun').subscribe(result => {
   *   console.log(result.data.latitude, result.data.longitude);
   * });
   */
  geocode(address: string, useCache: boolean = true): Observable<GeocodeResponse> {
    if (!address || address.trim() === '') {
      return throwError(() => new Error('Adresse invalide'));
    }

    const cacheKey = this.normalizeCacheKey(address);

    // Vérifier le cache
    if (useCache && this.geocodeCache.has(cacheKey)) {
      return this.geocodeCache.get(cacheKey)!;
    }

    // Créer la requête
    const request$ = this.http.post<GeocodeResponse>(`${this.baseUrl}/geocode`, {
      address: address.trim()
    }).pipe(
      shareReplay(1), // Partager le résultat entre plusieurs subscribers
      catchError(this.handleError)
    );

    // Mettre en cache
    if (useCache) {
      this.geocodeCache.set(cacheKey, request$);
    }

    return request$;
  }

  /**
   * Convertit plusieurs adresses en batch
   * 
   * @param addresses - Tableau d'adresses
   * @returns Observable avec tableau de résultats
   */
  geocodeBatch(addresses: string[]): Observable<GeocodeResponse[]> {
    const requests = addresses.map(address => 
      this.geocode(address).pipe(
        catchError(() => of(null)) // Continuer même si une adresse échoue
      )
    );

    return new Observable(observer => {
      Promise.all(requests.map(req => req.toPromise()))
        .then(results => {
          observer.next(results.filter(r => r !== null) as GeocodeResponse[]);
          observer.complete();
        })
        .catch(error => observer.error(error));
    });
  }

  // ============================================================================
  // REVERSE GEOCODING (Coordonnées → Adresse)
  // ============================================================================

  /**
   * Convertit des coordonnées GPS en adresse
   * 
   * @param latitude - Latitude
   * @param longitude - Longitude
   * @returns Observable avec l'adresse
   * 
   * @example
   * geocodingService.reverseGeocode(3.8667, 11.5167).subscribe(result => {
   *   console.log(result.data.address);
   * });
   */
  reverseGeocode(latitude: number, longitude: number): Observable<GeocodeResponse> {
    if (!this.isValidCoordinate(latitude, longitude)) {
      return throwError(() => new Error('Coordonnées invalides'));
    }

    return this.http.post<GeocodeResponse>(`${this.baseUrl}/geocode`, {
      latitude,
      longitude
    }).pipe(
      catchError(this.handleError)
    );
  }

  // ============================================================================
  // CALCUL DE DISTANCE
  // ============================================================================

  /**
   * Calcule la distance entre deux points
   * 
   * @param origin - Coordonnées d'origine
   * @param destination - Coordonnées de destination
   * @returns Observable avec distance et durée
   * 
   * @example
   * geocodingService.calculateDistance(
   *   { latitude: 3.8667, longitude: 11.5167 },
   *   { latitude: 4.0511, longitude: 9.7679 }
   * ).subscribe(result => {
   *   console.log(`Distance: ${result.data.distance_text}`);
   * });
   */
  calculateDistance(origin: Coordinates, destination: Coordinates): Observable<DistanceResponse> {
    if (!this.isValidCoordinate(origin.latitude, origin.longitude) ||
        !this.isValidCoordinate(destination.latitude, destination.longitude)) {
      return throwError(() => new Error('Coordonnées invalides'));
    }

    return this.http.post<DistanceResponse>(`${this.baseUrl}/distance`, {
      origin_lat: origin.latitude,
      origin_lng: origin.longitude,
      dest_lat: destination.latitude,
      dest_lng: destination.longitude
    }).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Calcule la distance "à vol d'oiseau" (méthode Haversine)
   * Calcul local sans appel API
   * 
   * @param lat1 - Latitude point 1
   * @param lon1 - Longitude point 1
   * @param lat2 - Latitude point 2
   * @param lon2 - Longitude point 2
   * @returns Distance en kilomètres
   */
  calculateHaversineDistance(
    lat1: number,
    lon1: number,
    lat2: number,
    lon2: number
  ): number {
    const R = 6371; // Rayon de la Terre en km
    const dLat = this.toRad(lat2 - lat1);
    const dLon = this.toRad(lon2 - lon1);
    
    const a = 
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c;
    
    return Math.round(distance * 100) / 100; // Arrondi à 2 décimales
  }

  // ============================================================================
  // CALCUL D'ITINÉRAIRE
  // ============================================================================

  /**
   * Calcule un itinéraire entre deux points
   * 
   * @param origin - Coordonnées d'origine
   * @param destination - Coordonnées de destination
   * @returns Observable avec l'itinéraire
   */
  calculateRoute(origin: Coordinates, destination: Coordinates): Observable<RouteResponse> {
    if (!this.isValidCoordinate(origin.latitude, origin.longitude) ||
        !this.isValidCoordinate(destination.latitude, destination.longitude)) {
      return throwError(() => new Error('Coordonnées invalides'));
    }

    return this.http.post<RouteResponse>(`${this.baseUrl}/route`, {
      origin_lat: origin.latitude,
      origin_lng: origin.longitude,
      dest_lat: destination.latitude,
      dest_lng: destination.longitude
    }).pipe(
      catchError(this.handleError)
    );
  }

  // ============================================================================
  // MÉTHODES UTILITAIRES
  // ============================================================================

  /**
   * Vérifie si des coordonnées sont valides
   */
  isValidCoordinate(lat: number, lng: number): boolean {
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
   * Convertit des degrés en radians
   */
  private toRad(degrees: number): number {
    return degrees * (Math.PI / 180);
  }

  /**
   * Normalise une clé de cache (lowercase, trim)
   */
  private normalizeCacheKey(address: string): string {
    return address.toLowerCase().trim();
  }

  /**
   * Vide le cache du geocoding
   */
  clearCache(): void {
    this.geocodeCache.clear();
  }

  /**
   * Formate une distance en texte lisible
   * 
   * @param meters - Distance en mètres
   * @returns Texte formaté (ex: "2.5 km" ou "350 m")
   */
  formatDistance(meters: number): string {
    if (meters < 1000) {
      return `${Math.round(meters)} m`;
    }
    return `${(meters / 1000).toFixed(1)} km`;
  }

  /**
   * Formate une durée en texte lisible
   * 
   * @param seconds - Durée en secondes
   * @returns Texte formaté (ex: "15 min" ou "1h 30min")
   */
  formatDuration(seconds: number): string {
    const minutes = Math.round(seconds / 60);
    
    if (minutes < 60) {
      return `${minutes} min`;
    }
    
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    
    if (remainingMinutes === 0) {
      return `${hours}h`;
    }
    
    return `${hours}h ${remainingMinutes}min`;
  }

  // ============================================================================
  // GESTION DES ERREURS
  // ============================================================================

  private handleError(error: HttpErrorResponse): Observable<never> {
    let errorMessage = 'Erreur de géocodage';

    if (error.error instanceof ErrorEvent) {
      errorMessage = `Erreur: ${error.error.message}`;
    } else {
      errorMessage = `Erreur ${error.status}: ${error.message}`;
      
      if (error.error?.message) {
        errorMessage = error.error.message;
      }
    }

    console.error('[GeocodingService]', errorMessage, error);
    return throwError(() => new Error(errorMessage));
  }
}