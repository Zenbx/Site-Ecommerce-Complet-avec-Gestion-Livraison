// src/app/features/orders/orders.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

import { 
  Order, 
  OrdersListResponse, 
  OrderStatus, 
  AssignDeliveryRequest 
} from '../models/order.model';
import { DeliveryDriver } from '../models/delivery.model';

export interface OrderFilters {
  status?: OrderStatus;
  payment_status?: string;
  search?: string;
  date_from?: string;
  date_to?: string;
}

@Injectable({
  providedIn: 'root'
})
export class OrdersService {
  private apiUrl = `${environment.apiUrl}/admin/orders`;

  constructor(private http: HttpClient) {}

  /**
   * Récupérer la liste des commandes
   */
  getOrders(page: number = 1, perPage: number = 15, filters?: OrderFilters): Observable<OrdersListResponse> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', perPage.toString());

    if (filters) {
      Object.keys(filters).forEach(key => {
        const value = (filters as any)[key];
        if (value !== undefined && value !== null && value !== '') {
          params = params.set(key, value.toString());
        }
      });
    }

    return this.http.get<OrdersListResponse>(this.apiUrl, { params });
  }

  /**
   * Récupérer les détails d'une commande
   */
  getOrder(orderId: number): Observable<Order> {
    return this.http.get<Order>(`${this.apiUrl}/${orderId}`);
  }

  /**
   * Récupérer uniquement les commandes non assignées (sans livreur)
   */
  getUnassignedOrders(): Observable<OrdersListResponse> {
    const params = new HttpParams().set('unassigned', 'true');
    return this.http.get<OrdersListResponse>(this.apiUrl, { params });
  }

  /**
   * Mettre à jour le statut d'une commande
   */
  updateOrderStatus(orderId: number, status: OrderStatus): Observable<Order> {
    return this.http.patch<Order>(
      `${this.apiUrl}/${orderId}/status`,
      { status }
    );
  }

  /**
   * Assigner un livreur à une commande
   */
  assignDeliveryPerson(orderId: number, request: AssignDeliveryRequest): Observable<Order> {
    return this.http.post<Order>(
      `${this.apiUrl}/${orderId}/assign-delivery`,
      request
    );
  }

  /**
   * Récupérer les livreurs disponibles pour l'assignation
   */
  getAvailableDeliveryPersons(): Observable<DeliveryDriver[]> {
  const endpoint = `${environment.apiUrl}/admin/delivery-persons`;

  return this.http.get<any>(endpoint).pipe(
    map(response => {
      let drivers: DeliveryDriver[] = [];

      // Normalisation de la réponse
      if (response?.data && Array.isArray(response.data)) {
        drivers = response.data;
      } else if (Array.isArray(response)) {
        drivers = response;
      } else {
        console.warn('⚠️ Format inattendu pour les livreurs:', response);
        return [];
      }

      // 🔎 FILTRAGE DES LIVREURS DISPONIBLES
      return drivers.filter(driver => {
        // Cas 1 : champ explicite
        if ('is_available' in driver) {
          return driver.is_available === true;
        }

        // Cas 2 : statut
        if ('status' in driver) {
          return driver.status === 'AVAILABLE';
        }

        // Cas 3 : pas de livraison en cours
        if ('current_delivery' in driver) {
          return !driver.current_delivery;
        }

        // Par défaut, on considère le livreur comme disponible
        return true;
      });
    }),
    catchError(error => {
      console.error('❌ Erreur chargement livreurs:', error);
      return of([]);
    })
  );
}


  /**
   * Annuler une commande
   */
  cancelOrder(orderId: number, reason: string): Observable<Order> {
    return this.http.patch<Order>(
      `${this.apiUrl}/${orderId}/status`,
      { status: OrderStatus.CANCELLED, reason }
    );
  }

  /**
   * Formater le prix (convertir string en number avec formatage)
   */
  formatPrice(price: string | number): string {
    const numPrice = typeof price === 'string' ? parseFloat(price) : price;
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XAF',
      minimumFractionDigits: 0
    }).format(numPrice);
  }

  /**
   * Obtenir la classe CSS selon le statut
   */
  getStatusClass(status: OrderStatus): string {
    const classes: { [key: string]: string } = {
      [OrderStatus.PENDING]: 'status-pending',
      [OrderStatus.CONFIRMED]: 'status-confirmed',
      [OrderStatus.PROCESSING]: 'status-processing',
      [OrderStatus.SHIPPED]: 'status-shipped',
      [OrderStatus.DELIVERED]: 'status-delivered',
      [OrderStatus.CANCELLED]: 'status-cancelled'
    };
    return classes[status] || '';
  }

  /**
   * Obtenir la classe CSS selon le statut de paiement
   */
  getPaymentStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'PENDING': 'payment-pending',
      'PAID': 'payment-paid',
      'FAILED': 'payment-failed',
      'REFUNDED': 'payment-refunded'
    };
    return classes[status] || '';
  }
}