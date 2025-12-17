// src/app/features/deliveries/deliveries.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpParams, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { environment } from '../../../environments/environment';
import {
  Delivery,
  DeliveryStatus,
  DeliveryDriver
} from './models/delivery.model';

// ============================================
// INTERFACES DE SERVICE
// ============================================

export interface DeliveriesListResponse {
  success: boolean;
  data: Delivery[];
  meta: {
    current_page: number;
    total: number;
    per_page: number;
    last_page: number;
  };
}

export interface DeliveryResponse {
  success: boolean;
  data: Delivery;
  message?: string;
}

export interface DeliveryFilters {
  status?: DeliveryStatus | string;
  driverId?: number;
  dateFrom?: string;
  dateTo?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export interface AvailableDriversResponse {
  success: boolean;
  data: DeliveryDriver[];
}

/**
 * Service de gestion des livraisons
 * 
 * Ce service est le point central de communication entre l'application Angular
 * et l'API Laravel pour tout ce qui concerne les livraisons. Il gère :
 * - La récupération des listes de livraisons avec filtres et pagination
 * - Les détails d'une livraison spécifique
 * - L'assignation de livreurs (manuelle et automatique)
 * - La mise à jour des statuts de livraison
 * - Le tracking GPS en temps réel
 * 
 * Toutes les méthodes retournent des Observables RxJS pour permettre
 * une programmation réactive et une gestion élégante des erreurs.
 */
@Injectable({
  providedIn: 'root'
})
export class DeliveriesService {
  
  /**
   * URL de base pour les endpoints de livraisons admin
   * Construit automatiquement à partir de l'environnement
   */
  private apiUrl = `${environment.apiUrl}/admin/deliveries`;

  constructor(private http: HttpClient) {}

  // ============================================
  // RÉCUPÉRATION DES LIVRAISONS
  // ============================================

  /**
   * Récupérer la liste paginée des livraisons avec filtres optionnels
   * 
   * Cette méthode est le point d'entrée principal pour afficher la liste
   * des livraisons dans l'interface admin. Elle supporte :
   * - La pagination (page, per_page)
   * - Le filtrage par statut (pending, assigned, in_progress, etc.)
   * - Le filtrage par livreur
   * - Le filtrage par période
   * - La recherche textuelle
   * 
   * @param filters - Objet contenant les critères de filtrage
   * @returns Observable contenant la liste paginée des livraisons
   * 
   * @example
   * ```typescript
   * this.deliveriesService.getDeliveries({
   *   status: DeliveryStatus.IN_PROGRESS,
   *   page: 1,
   *   per_page: 20
   * }).subscribe(response => {
   *   this.deliveries = response.data;
   *   this.totalItems = response.meta.total;
   * });
   * ```
   */
  getDeliveries(filters?: DeliveryFilters): Observable<DeliveriesListResponse> {
    let params = new HttpParams();

    // Construire les paramètres de requête
    if (filters) {
      Object.keys(filters).forEach(key => {
        const value = (filters as any)[key];
        if (value !== undefined && value !== null && value !== '') {
          params = params.set(key, value.toString());
        }
      });
    }

    return this.http.get<DeliveriesListResponse>(this.apiUrl, { params })
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Récupérer les détails complets d'une livraison spécifique
   * 
   * Cette méthode charge toutes les informations détaillées d'une livraison :
   * - Les informations client
   * - L'adresse de livraison avec coordonnées GPS
   * - Le livreur assigné (si applicable) avec sa position actuelle
   * - L'historique des statuts
   * - Les preuves de livraison
   * 
   * @param id - L'identifiant unique de la livraison
   * @returns Observable contenant les détails complets de la livraison
   * 
   * @example
   * ```typescript
   * this.deliveriesService.getDelivery(123).subscribe(delivery => {
   *   this.delivery = delivery;
   *   if (delivery.driver?.currentLocation) {
   *     this.initializeMap(delivery.driver.currentLocation);
   *   }
   * });
   * ```
   */
  getDelivery(id: number): Observable<Delivery> {
    return this.http.get<DeliveryResponse>(`${this.apiUrl}/${id}`)
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  /**
   * Récupérer uniquement les livraisons en attente d'assignation
   * 
   * Méthode utilitaire qui filtre automatiquement pour ne retourner
   * que les livraisons avec le statut PENDING. Pratique pour afficher
   * la liste des livraisons qui ont besoin d'être assignées à un livreur.
   * 
   * @returns Observable contenant uniquement les livraisons en attente
   */
  getPendingDeliveries(): Observable<Delivery[]> {
    return this.getDeliveries({ status: DeliveryStatus.PENDING })
      .pipe(
        map(response => response.data)
      );
  }

  // ============================================
  // GESTION DES LIVREURS
  // ============================================

  /**
   * Récupérer la liste des livreurs disponibles pour assignation
   * 
   * Cette méthode retourne tous les livreurs qui sont actuellement
   * disponibles pour accepter de nouvelles livraisons. Les critères
   * de disponibilité sont gérés côté serveur et incluent :
   * - is_available = true
   * - Pas trop de livraisons en cours
   * - Statut actif
   * 
   * @returns Observable contenant la liste des livreurs disponibles
   * 
   * @example
   * ```typescript
   * this.deliveriesService.getAvailableDrivers().subscribe(drivers => {
   *   this.availableDrivers = drivers;
   *   // Afficher dans une modal de sélection
   *   this.showDriverSelectionModal();
   * });
   * ```
   */
  getAvailableDrivers(): Observable<DeliveryDriver[]> {
    return this.http.get<AvailableDriversResponse>(`${environment.apiUrl}/admin/delivery-persons`)
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  // ============================================
  // ASSIGNATION DE LIVREURS
  // ============================================

  /**
   * Assigner manuellement un livreur à une livraison
   * 
   * Cette méthode crée une assignation manuelle entre une livraison
   * et un livreur spécifique choisi par l'administrateur. Après
   * l'assignation réussie :
   * - Le statut de la livraison passe à ASSIGNED
   * - Une notification est envoyée au livreur
   * - La date d'assignation est enregistrée
   * 
   * @param deliveryId - L'ID de la livraison à assigner
   * @param driverId - L'ID du livreur à qui assigner la livraison
   * @returns Observable contenant la livraison mise à jour
   * 
   * @example
   * ```typescript
   * this.deliveriesService.assignDriver(delivery.id, selectedDriver.id)
   *   .subscribe({
   *     next: (updatedDelivery) => {
   *       this.delivery = updatedDelivery;
   *       alert('Livreur assigné avec succès !');
   *       this.closeModal();
   *     },
   *     error: (error) => {
   *       alert('Erreur lors de l\'assignation');
   *     }
   *   });
   * ```
   */
  assignDriver(deliveryId: number, driverId: number): Observable<Delivery> {
    return this.http.post<DeliveryResponse>(
      `${this.apiUrl}/${deliveryId}/manualassign`,
      { delivery_person_id: driverId }
    ).pipe(
      map(response => response.data),
      catchError(this.handleError)
    );
  }

  /**
   * Assigner automatiquement le meilleur livreur disponible
   * 
   * Cette méthode déclenche un algorithme côté serveur qui sélectionne
   * automatiquement le livreur le plus approprié pour la livraison.
   * Les critères de sélection incluent :
   * - Proximité géographique avec l'adresse de livraison
   * - Charge de travail actuelle du livreur
   * - Performance historique (taux de réussite, rapidité)
   * - Type de véhicule adapté au colis
   * 
   * @param deliveryId - L'ID de la livraison à assigner automatiquement
   * @returns Observable contenant la livraison avec le livreur assigné
   * 
   * @example
   * ```typescript
   * this.deliveriesService.autoAssignDriver(delivery.id)
   *   .subscribe({
   *     next: (updatedDelivery) => {
   *       alert(`Assigné automatiquement à ${updatedDelivery.driver.name}`);
   *       this.refresh();
   *     },
   *     error: (error) => {
   *       alert('Aucun livreur disponible pour le moment');
   *     }
   *   });
   * ```
   */
  autoAssignDriver(deliveryId: number): Observable<Delivery> {
    return this.http.post<DeliveryResponse>(
      `${this.apiUrl}/${deliveryId}/autoassign`,
      {}
    ).pipe(
      map(response => response.data),
      catchError(this.handleError)
    );
  }

  /**
   * Retirer le livreur actuellement assigné à une livraison
   * 
   * Cette méthode annule l'assignation actuelle et remet la livraison
   * en statut PENDING. Utilisée quand :
   * - L'administrateur s'est trompé dans l'assignation
   * - Le livreur ne peut finalement pas effectuer la livraison
   * - Il faut réassigner à un autre livreur
   * 
   * @param deliveryId - L'ID de la livraison dont on veut retirer le livreur
   * @returns Observable contenant la livraison mise à jour sans livreur
   * 
   * @example
   * ```typescript
   * const confirmed = confirm('Retirer le livreur de cette livraison ?');
   * if (confirmed) {
   *   this.deliveriesService.removeDriver(delivery.id)
   *     .subscribe({
   *       next: (updatedDelivery) => {
   *         this.delivery = updatedDelivery;
   *         alert('Livreur retiré avec succès');
   *       }
   *     });
   * }
   * ```
   */
  removeDriver(deliveryId: number): Observable<Delivery> {
    return this.http.delete<DeliveryResponse>(
      `${this.apiUrl}/${deliveryId}/driver`
    ).pipe(
      map(response => response.data),
      catchError(this.handleError)
    );
  }

  /**
   * Réassigner une livraison à un nouveau livreur
   * 
   * Cette méthode permet de changer le livreur assigné sans passer
   * par l'état PENDING. Utile quand un livreur rencontre un problème
   * et qu'il faut rapidement le remplacer par un autre.
   * 
   * @param deliveryId - L'ID de la livraison à réassigner
   * @param newDriverId - L'ID du nouveau livreur
   * @param reason - La raison de la réassignation (optionnel)
   * @returns Observable contenant la livraison avec le nouveau livreur
   */
  reassignDriver(deliveryId: number, newDriverId: number, reason?: string): Observable<Delivery> {
    return this.http.patch<DeliveryResponse>(
      `${this.apiUrl}/${deliveryId}/reassign`,
      { delivery_person_id: newDriverId, reason }
    ).pipe(
      map(response => response.data),
      catchError(this.handleError)
    );
  }

  // ============================================
  // MISE À JOUR DES STATUTS
  // ============================================

  /**
   * Mettre à jour le statut d'une livraison
   * 
   * Permet de changer manuellement le statut d'une livraison.
   * Normalement, les changements de statut sont effectués par le livreur
   * via l'app mobile, mais cette méthode permet à l'admin d'intervenir
   * manuellement si nécessaire.
   * 
   * @param id - L'ID de la livraison
   * @param status - Le nouveau statut à appliquer
   * @param notes - Notes explicatives optionnelles
   * @returns Observable contenant la livraison mise à jour
   */
  updateDeliveryStatus(
    id: number,
    status: DeliveryStatus,
    notes?: string
  ): Observable<Delivery> {
    return this.http.patch<DeliveryResponse>(
      `${this.apiUrl}/${id}/status`,
      { status, notes }
    ).pipe(
      map(response => response.data),
      catchError(this.handleError)
    );
  }

  /**
   * Annuler une livraison
   * 
   * Passe le statut de la livraison à CANCELLED et enregistre
   * la raison de l'annulation. Cette action est généralement
   * irréversible et peut déclencher des processus de remboursement.
   * 
   * @param id - L'ID de la livraison à annuler
   * @param reason - La raison de l'annulation (obligatoire)
   * @returns Observable contenant la livraison annulée
   */
  cancelDelivery(id: number, reason: string): Observable<Delivery> {
    return this.http.post<DeliveryResponse>(
      `${this.apiUrl}/${id}/cancel`,
      { reason }
    ).pipe(
      map(response => response.data),
      catchError(this.handleError)
    );
  }

  // ============================================
  // TRACKING ET CARTOGRAPHIE
  // ============================================

  /**
   * Obtenir l'itinéraire optimisé pour une livraison
   * 
   * Calcule l'itinéraire optimal entre la position actuelle du livreur
   * et l'adresse de livraison en utilisant OSRM (Open Source Routing Machine).
   * Retourne les coordonnées GPS pour tracer la route sur une carte.
   * 
   * @param id - L'ID de la livraison
   * @returns Observable contenant les données de l'itinéraire
   */
  getDeliveryRoute(id: number): Observable<any> {
    return this.http.get(`${environment.apiUrl}/delivery-person/deliveries/${id}/route`)
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Obtenir toutes les livraisons actives avec leurs positions GPS
   * 
   * Retourne une vue d'ensemble de toutes les livraisons en cours
   * avec les positions GPS des livreurs. Utilisé pour afficher
   * une carte globale de toutes les livraisons actives.
   * 
   * @returns Observable contenant un tableau de positions GPS
   */
  getActiveDeliveriesMap(): Observable<any[]> {
    return this.http.get<any>(`${this.apiUrl}/active-map`)
      .pipe(
        map(response => response.data || []),
        catchError(this.handleError)
      );
  }

  // ============================================
  // PREUVES DE LIVRAISON
  // ============================================

  /**
   * Récupérer les preuves de livraison pour une livraison spécifique
   * 
   * Une livraison peut avoir plusieurs types de preuves :
   * - Signature électronique du client
   * - Photo du colis livré
   * - Scan du QR code de confirmation
   * 
   * @param deliveryId - L'ID de la livraison
   * @returns Observable contenant le tableau des preuves
   */
  getDeliveryProofs(deliveryId: number): Observable<any[]> {
    return this.http.get<any>(`${this.apiUrl}/${deliveryId}/proofs`)
      .pipe(
        map(response => response.data || []),
        catchError(this.handleError)
      );
  }

  /**
   * Valider une preuve de livraison
   * 
   * Marque une preuve comme validée après vérification par l'admin.
   * Cela confirme que la livraison a bien été effectuée correctement.
   * 
   * @param deliveryId - L'ID de la livraison
   * @param proofId - L'ID de la preuve à valider
   * @returns Observable de confirmation
   */
  validateProof(deliveryId: number, proofId: number): Observable<void> {
    return this.http.post<void>(
      `${this.apiUrl}/${deliveryId}/proofs/${proofId}/validate`,
      {}
    ).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Rejeter une preuve de livraison
   * 
   * Marque une preuve comme rejetée si elle est invalide ou frauduleuse.
   * Nécessite de fournir une raison du rejet.
   * 
   * @param deliveryId - L'ID de la livraison
   * @param proofId - L'ID de la preuve à rejeter
   * @param reason - La raison du rejet (obligatoire)
   * @returns Observable de confirmation
   */
  rejectProof(deliveryId: number, proofId: number, reason: string): Observable<void> {
    return this.http.post<void>(
      `${this.apiUrl}/${deliveryId}/proofs/${proofId}/reject`,
      { reason }
    ).pipe(
      catchError(this.handleError)
    );
  }

  // ============================================
  // GESTION DES ERREURS
  // ============================================

  /**
   * Gestionnaire d'erreurs HTTP centralisé
   * 
   * Cette méthode traite toutes les erreurs HTTP de manière cohérente
   * dans l'ensemble du service. Elle :
   * - Log l'erreur dans la console pour le debugging
   * - Extrait le message d'erreur de la réponse API
   * - Crée des messages d'erreur user-friendly
   * - Retourne un Observable d'erreur pour que les composants puissent réagir
   * 
   * @param error - L'objet HttpErrorResponse contenant les détails de l'erreur
   * @returns Observable d'erreur avec un message approprié
   */
  private handleError(error: HttpErrorResponse): Observable<never> {
    let errorMessage = 'Une erreur est survenue';

    if (error.error instanceof ErrorEvent) {
      // Erreur côté client ou réseau
      errorMessage = `Erreur réseau: ${error.error.message}`;
    } else {
      // Erreur côté serveur
      const serverMessage = error.error?.message || error.error?.error;
      errorMessage = serverMessage || `Erreur serveur (${error.status})`;

      // Messages spécifiques selon le code d'erreur
      switch (error.status) {
        case 400:
          errorMessage = 'Requête invalide: ' + serverMessage;
          break;
        case 401:
          errorMessage = 'Non autorisé - Veuillez vous reconnecter';
          break;
        case 403:
          errorMessage = 'Accès refusé - Droits insuffisants';
          break;
        case 404:
          errorMessage = 'Ressource non trouvée';
          break;
        case 422:
          errorMessage = 'Données invalides: ' + serverMessage;
          break;
        case 500:
          errorMessage = 'Erreur serveur interne';
          break;
      }
    }

    console.error('❌ Erreur DeliveriesService:', errorMessage, error);
    return throwError(() => new Error(errorMessage));
  }
}