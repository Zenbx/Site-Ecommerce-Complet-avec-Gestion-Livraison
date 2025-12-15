// src/app/features/deliveries/deliveries.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { 
  Delivery, 
  DeliveryAssignmentRequest,
  DeliveryStatus 
} from './models/delivery.model';

export interface DeliveriesListResponse {
  data: Delivery[];
  total: number;
  page: number;
  perPage: number;
}

export interface DeliveryFilters {
  status?: DeliveryStatus;
  driverId?: number;
  dateFrom?: string;
  dateTo?: string;
  search?: string;
}

@Injectable({
  providedIn: 'root'
})
export class DeliveriesService {
  private apiUrl = `${environment.apiUrl}/deliveries`;

  constructor(private http: HttpClient) {}

  getDeliveries(
    page: number = 1, 
    perPage: number = 10, 
    filters?: DeliveryFilters
  ): Observable<DeliveriesListResponse> {
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

    return this.http.get<DeliveriesListResponse>(this.apiUrl, { params });
  }

  getDelivery(id: number): Observable<Delivery> {
    return this.http.get<Delivery>(`${this.apiUrl}/${id}`);
  }

  getPendingDeliveries(): Observable<Delivery[]> {
    return this.http.get<Delivery[]>(`${this.apiUrl}/pending`);
  }

  assignDelivery(assignment: DeliveryAssignmentRequest): Observable<Delivery> {
    return this.http.post<Delivery>(`${this.apiUrl}/assign`, assignment);
  }

  autoAssignDelivery(deliveryId: number): Observable<Delivery> {
    return this.http.post<Delivery>(`${this.apiUrl}/${deliveryId}/auto-assign`, {});
  }

  updateDeliveryStatus(id: number, status: DeliveryStatus): Observable<Delivery> {
    return this.http.patch<Delivery>(`${this.apiUrl}/${id}/status`, { status });
  }

  cancelDelivery(id: number, reason: string): Observable<Delivery> {
    return this.http.post<Delivery>(`${this.apiUrl}/${id}/cancel`, { reason });
  }

  validateDeliveryProof(id: number): Observable<Delivery> {
    return this.http.post<Delivery>(`${this.apiUrl}/${id}/validate-proof`, {});
  }

  getDeliveryRoute(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/${id}/route`);
  }

  getActiveDeliveriesMap(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/active-map`);
  }
}