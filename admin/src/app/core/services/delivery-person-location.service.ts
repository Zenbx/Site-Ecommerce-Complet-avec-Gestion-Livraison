import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError, BehaviorSubject } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';

import { environment } from '../../../environments/environment';
import { Driver } from '../models/driver.model';

/**
 * Interface de réponse API pour la liste des livreurs
 */
interface DeliveryPersonsLocationResponse {
  success: boolean;
  data: Driver[];
}

/**
 * Interface de réponse API pour un livreur unique
 */
interface DeliveryPersonLocationResponse {
  success: boolean;
  data: Driver;
}

/**
 * Service de gestion des localisations des livreurs
 * 
 * Gère les appels API pour :
 * - Récupérer toutes les positions des livreurs
 * - Récupérer la position d'un livreur spécifique
 * - Filtrer les livreurs actifs
 * - Cache local pour optimiser les performances
 */
@Injectable({
  providedIn: 'root'
})
export class DeliveryPersonLocationService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/admin/delivery-persons`;

  // Cache local des positions (utile pour éviter les appels répétés)
  private deliveryPersonsCache$ = new BehaviorSubject<Driver[]>([]);
  private lastFetchTime: number = 0;
  private readonly CACHE_DURATION = 5000; // 5 secondes

  // Observable public du cache (pour que les composants puissent s'abonner)
  public deliveryPersons$ = this.deliveryPersonsCache$.asObservable();

  // ============================================================================
  // API CALLS
  // ============================================================================

  /**
   * Récupère toutes les positions des livreurs
   * 
   * @param forceRefresh - Force le refresh même si le cache est valide
   * @returns Observable avec la liste des livreurs
   */
  getAllLocations(forceRefresh: boolean = false): Observable<DeliveryPersonsLocationResponse> {
    // Vérifier le cache si pas de force refresh
    if (!forceRefresh && this.isCacheValid()) {
      return new Observable(observer => {
        observer.next({
          success: true,
          data: this.deliveryPersonsCache$.value
        });
        observer.complete();
      });
    }

    return this.http.get<DeliveryPersonsLocationResponse>(`${this.baseUrl}/locations`).pipe(
      tap(response => {
        // Mettre à jour le cache
        this.deliveryPersonsCache$.next(response.data);
        this.lastFetchTime = Date.now();
      }),
      catchError(this.handleError)
    );
  }

  /**
   * Récupère la position d'un livreur spécifique
   * 
   * @param deliveryPersonId - ID du livreur
   * @returns Observable avec les données du livreur
   */
  getLocation(deliveryPersonId: number): Observable<DeliveryPersonLocationResponse> {
    return this.http.get<DeliveryPersonLocationResponse>(
      `${this.baseUrl}/${deliveryPersonId}/location`
    ).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Récupère uniquement les livreurs actifs (en ligne)
   * 
   * @returns Observable avec la liste des livreurs actifs
   */
  getActiveDeliveryPersons(): Observable<DeliveryPersonsLocationResponse> {
    return this.http.get<DeliveryPersonsLocationResponse>(
      `${this.baseUrl}/tracking/active`
    ).pipe(
      catchError(this.handleError)
    );
  }

  // ============================================================================
  // MÉTHODES UTILITAIRES
  // ============================================================================

  /**
   * Filtre les livreurs en ligne depuis le cache local
   * 
   * @returns Tableau des livreurs en ligne
   */
  getOnlineDeliveryPersons(): Driver[] {
    return this.deliveryPersonsCache$.value.filter(
      dp => dp.is_online && dp.current_latitude && dp.current_longitude
    );
  }

  /**
   * Filtre les livreurs hors ligne depuis le cache local
   * 
   * @returns Tableau des livreurs hors ligne
   */
  getOfflineDeliveryPersons(): Driver[] {
    return this.deliveryPersonsCache$.value.filter(
      dp => !dp.is_online && dp.current_latitude && dp.current_longitude
    );
  }

  /**
   * Trouve un livreur dans le cache par son ID
   * 
   * @param id - ID du livreur
   * @returns Le livreur ou undefined
   */
  getDeliveryPersonFromCache(id: number): Driver | undefined {
    return this.deliveryPersonsCache$.value.find(dp => dp.id === id);
  }

  /**
   * Compte le nombre de livreurs disponibles
   * 
   * @returns Nombre de livreurs disponibles
   */
  getAvailableCount(): number {
    return this.deliveryPersonsCache$.value.filter(
      dp => dp.is_available && dp.is_online
    ).length;
  }

  /**
   * Vérifie si un livreur a une position valide
   * 
   * @param deliveryPerson - Le livreur à vérifier
   * @returns true si position valide
   */
  hasValidLocation(deliveryPerson: Driver): boolean {
    return !!(
      deliveryPerson.current_latitude &&
      deliveryPerson.current_longitude &&
      this.isValidCoordinate(
        parseFloat(deliveryPerson.current_latitude),
        parseFloat(deliveryPerson.current_longitude)
      )
    );
  }

  /**
   * Vérifie si des coordonnées sont valides
   * 
   * @param lat - Latitude
   * @param lng - Longitude
   * @returns true si valides
   */
  private isValidCoordinate(lat: number, lng: number): boolean {
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
   * Vérifie si le cache est encore valide
   * 
   * @returns true si le cache est valide
   */
  private isCacheValid(): boolean {
    return (
      this.deliveryPersonsCache$.value.length > 0 &&
      Date.now() - this.lastFetchTime < this.CACHE_DURATION
    );
  }

  /**
   * Invalide le cache (force le prochain appel API)
   */
  invalidateCache(): void {
    this.lastFetchTime = 0;
  }

  /**
   * Vide complètement le cache
   */
  clearCache(): void {
    this.deliveryPersonsCache$.next([]);
    this.lastFetchTime = 0;
  }

  // ============================================================================
  // GESTION DES ERREURS
  // ============================================================================

  /**
   * Gère les erreurs HTTP
   */
  private handleError(error: HttpErrorResponse): Observable<never> {
    let errorMessage = 'Une erreur est survenue';

    if (error.error instanceof ErrorEvent) {
      // Erreur côté client
      errorMessage = `Erreur: ${error.error.message}`;
    } else {
      // Erreur côté serveur
      errorMessage = `Erreur ${error.status}: ${error.message}`;
      
      if (error.error?.message) {
        errorMessage = error.error.message;
      }
    }

    console.error('[DeliveryPersonLocationService]', errorMessage, error);
    return throwError(() => new Error(errorMessage));
  }
}