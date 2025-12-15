// src/app/features/delivery-drivers/delivery-drivers.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { DeliveryDriver } from '../deliveries/models/delivery.model';

export interface DriverCreateRequest {
  firstName: string;
  lastName: string;
  phone: string;
  email?: string;
  vehicleType: string;
  vehiclePlate: string;
  licenseNumber: string;
}

export interface DriverUpdateRequest extends Partial<DriverCreateRequest> {
  isAvailable?: boolean;
  isActive?: boolean;
}

export interface DriversListResponse {
  data: DeliveryDriver[];
  total: number;
  page: number;
  perPage: number;
}

@Injectable({
  providedIn: 'root'
})
export class DeliveryDriversService {
  private apiUrl = `${environment.apiUrl}/drivers`;

  constructor(private http: HttpClient) {}

  getDrivers(page: number = 1, perPage: number = 10, filters?: any): Observable<DriversListResponse> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', perPage.toString());

    if (filters) {
      Object.keys(filters).forEach(key => {
        if (filters[key]) {
          params = params.set(key, filters[key]);
        }
      });
    }

    return this.http.get<DriversListResponse>(this.apiUrl, { params });
  }

  getDriver(id: number): Observable<DeliveryDriver> {
    return this.http.get<DeliveryDriver>(`${this.apiUrl}/${id}`);
  }

  createDriver(driver: DriverCreateRequest): Observable<DeliveryDriver> {
    return this.http.post<DeliveryDriver>(this.apiUrl, driver);
  }

  updateDriver(id: number, driver: DriverUpdateRequest): Observable<DeliveryDriver> {
    return this.http.put<DeliveryDriver>(`${this.apiUrl}/${id}`, driver);
  }

  deleteDriver(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }

  toggleAvailability(id: number, isAvailable: boolean): Observable<DeliveryDriver> {
    return this.http.patch<DeliveryDriver>(`${this.apiUrl}/${id}/availability`, { isAvailable });
  }

  getAvailableDrivers(): Observable<DeliveryDriver[]> {
    return this.http.get<DeliveryDriver[]>(`${this.apiUrl}/available`);
  }

  getDriverStatistics(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/${id}/statistics`);
  }
}