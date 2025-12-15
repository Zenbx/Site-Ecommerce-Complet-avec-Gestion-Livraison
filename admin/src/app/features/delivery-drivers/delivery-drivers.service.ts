// src/app/features/delivery-drivers/delivery-drivers.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, of, delay } from 'rxjs';
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
    // 🚀 MOCK DATA - À remplacer par l'API Backend plus tard
    const mockAllDrivers: DeliveryDriver[] = [
      {
        id: 1,
        firstName: 'Ahmed',
        lastName: 'Hassan',
        phone: '+212612345678',
        vehicleType: 'Voiture',
        vehiclePlate: 'ABC-1234',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9716,
          longitude: -6.8498,
          updatedAt: new Date().toISOString()
        }
      },
      {
        id: 2,
        firstName: 'Marie',
        lastName: 'Dupont',
        phone: '+212698765432',
        vehicleType: 'Moto',
        vehiclePlate: 'XYZ-5678',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9714,
          longitude: -6.8500,
          updatedAt: new Date().toISOString()
        }
      },
      {
        id: 3,
        firstName: 'Carlos',
        lastName: 'Rodriguez',
        phone: '+212650123456',
        vehicleType: 'Camion',
        vehiclePlate: 'DEF-9012',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9715,
          longitude: -6.8497,
          updatedAt: new Date().toISOString()
        }
      },
      {
        id: 4,
        firstName: 'Fatima',
        lastName: 'Benali',
        phone: '+212610111213',
        vehicleType: 'Voiture',
        vehiclePlate: 'GHI-3456',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9717,
          longitude: -6.8499,
          updatedAt: new Date().toISOString()
        }
      },
      {
        id: 5,
        firstName: 'Jean',
        lastName: 'Martin',
        phone: '+212671415161',
        vehicleType: 'Fourgon',
        vehiclePlate: 'JKL-7890',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9713,
          longitude: -6.8501,
          updatedAt: new Date().toISOString()
        }
      },
      {
        id: 6,
        firstName: 'Sofia',
        lastName: 'Garcia',
        phone: '+212680192021',
        vehicleType: 'Voiture',
        vehiclePlate: 'MNO-2345',
        isAvailable: false,
        currentLocation: {
          latitude: 33.9718,
          longitude: -6.8496,
          updatedAt: new Date().toISOString()
        }
      },
      {
        id: 7,
        firstName: 'Moussa',
        lastName: 'Traore',
        phone: '+212670222324',
        vehicleType: 'Moto',
        vehiclePlate: 'PQR-6789',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9712,
          longitude: -6.8502,
          updatedAt: new Date().toISOString()
        }
      }
    ];

    // Simuler la pagination
    const startIndex = (page - 1) * perPage;
    const endIndex = startIndex + perPage;
    const paginatedDrivers = mockAllDrivers.slice(startIndex, endIndex);

    const response: DriversListResponse = {
      data: paginatedDrivers,
      total: mockAllDrivers.length,
      page: page,
      perPage: perPage
    };

    return of(response).pipe(delay(500));
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
    // 🚀 MOCK DATA - À remplacer par l'API Backend plus tard
    const mockDrivers: DeliveryDriver[] = [
      {
        id: 1,
        firstName: 'Ahmed',
        lastName: 'Hassan',
        phone: '+212612345678',
        vehicleType: 'Voiture',
        vehiclePlate: 'ABC-1234',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9716,
          longitude: -6.8498,
          updatedAt: new Date().toISOString()
        }
      },
      {
        id: 2,
        firstName: 'Marie',
        lastName: 'Dupont',
        phone: '+212698765432',
        vehicleType: 'Moto',
        vehiclePlate: 'XYZ-5678',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9714,
          longitude: -6.8500,
          updatedAt: new Date().toISOString()
        }
      },
      {
        id: 3,
        firstName: 'Carlos',
        lastName: 'Rodriguez',
        phone: '+212650123456',
        vehicleType: 'Camion',
        vehiclePlate: 'DEF-9012',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9715,
          longitude: -6.8497,
          updatedAt: new Date().toISOString()
        }
      },
      {
        id: 4,
        firstName: 'Fatima',
        lastName: 'Benali',
        phone: '+212610111213',
        vehicleType: 'Voiture',
        vehiclePlate: 'GHI-3456',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9717,
          longitude: -6.8499,
          updatedAt: new Date().toISOString()
        }
      },
      {
        id: 5,
        firstName: 'Jean',
        lastName: 'Martin',
        phone: '+212671415161',
        vehicleType: 'Fourgon',
        vehiclePlate: 'JKL-7890',
        isAvailable: true,
        currentLocation: {
          latitude: 33.9713,
          longitude: -6.8501,
          updatedAt: new Date().toISOString()
        }
      }
    ];

    // Retourner les données mockées avec un délai pour simuler une requête réseau
    return of(mockDrivers).pipe(delay(500));
  }

  getDriverStatistics(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/${id}/statistics`);
  }
}