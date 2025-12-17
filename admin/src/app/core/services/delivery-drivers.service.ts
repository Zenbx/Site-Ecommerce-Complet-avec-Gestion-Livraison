// src/app/features/delivery-drivers/delivery-drivers.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, of, delay, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { DeliveryDriver } from '../models/delivery.model';

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

export interface DriverStatistics {
  totalDeliveries: number;
  completedDeliveries: number;
  failedDeliveries: number;
  averageDeliveryTime: number;
  successRate: number;
  totalDistance: number;
  rating: number;
  lastDelivery?: string;
}

@Injectable({
  providedIn: 'root'
})
export class DeliveryDriversService {
  private apiUrl = `${environment.apiUrl}/delivery-persons`;

  constructor(private http: HttpClient) {}

  /**
   * Récupère la liste des livreurs avec pagination et filtres
   */
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

  /**
   * Récupère un livreur par ID
   */
  getDriver(id: number): Observable<DeliveryDriver> {
    return this.http.get<DeliveryDriver>(`${this.apiUrl}/${id}`);
  }

  /**
   * Crée un nouveau livreur
   */
  createDriver(driver: DriverCreateRequest): Observable<DeliveryDriver> {
    return this.http.post<DeliveryDriver>(this.apiUrl, driver);
  }

  /**
   * Met à jour un livreur
   */
  updateDriver(id: number, driver: DriverUpdateRequest): Observable<DeliveryDriver> {
    return this.http.put<DeliveryDriver>(`${this.apiUrl}/${id}`, driver);
  }

  /**
   * Supprime un livreur
   */
  deleteDriver(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }

  /**
   * Change la disponibilité d'un livreur
   */
  toggleAvailability(id: number, isAvailable: boolean): Observable<DeliveryDriver> {
    return this.http.patch<DeliveryDriver>(`${this.apiUrl}/${id}/availability`, { isAvailable });
  }

  /**
   * Récupère les livreurs disponibles
   */
  getAvailableDrivers(): Observable<DeliveryDriver[]> {
    return this.http.get<DeliveryDriver[]>(`${this.apiUrl}/available`);
  }

  /**
   * Récupère les statistiques d'un livreur
   */
  getDriverStatistics(id: number): Observable<DriverStatistics> {
    return this.http.get<DriverStatistics>(`${this.apiUrl}/${id}/statistics`);
  }

  /**
   * ASSIGNATION AUTOMATIQUE - Trouve le meilleur livreur pour une livraison
   */
  findBestDriver(deliveryLocation: { latitude: number; longitude: number }): Observable<DeliveryDriver | null> {
    return this.getAvailableDrivers().pipe(
      map(drivers => {
        if (drivers.length === 0) return null;

        // Calcule la distance pour chaque livreur disponible
        const driversWithDistance = drivers.map(driver => ({
          driver,
          distance: this.calculateDistance(
            deliveryLocation.latitude,
            deliveryLocation.longitude,
            driver.currentLocation?.latitude || 0,
            driver.currentLocation?.longitude || 0
          )
        }));

        // Trie par distance et retourne le plus proche
        driversWithDistance.sort((a, b) => a.distance - b.distance);
        
        console.log('Auto-assignment:', {
          total: drivers.length,
          closest: driversWithDistance[0].driver.name,
          distance: driversWithDistance[0].distance.toFixed(2) + ' km'
        });

        return driversWithDistance[0].driver;
      })
    );
  }

  /**
   * Calcule la distance entre deux points GPS (formule de Haversine)
   */
  private calculateDistance(lat1: number, lon1: number, lat2: number, lon2: number): number {
    const R = 6371; // Rayon de la Terre en km
    const dLat = this.deg2rad(lat2 - lat1);
    const dLon = this.deg2rad(lon2 - lon1);
    
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(this.deg2rad(lat1)) * Math.cos(this.deg2rad(lat2)) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c;
    
    return distance;
  }

  private deg2rad(deg: number): number {
    return deg * (Math.PI / 180);
  }

  /**
   * Export des livreurs en CSV
   */
  exportToCsv(): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/export/csv`, { responseType: 'blob' });
  }

  /**
   * Export des livreurs en PDF
   */
  exportToPdf(): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/export/pdf`, { responseType: 'blob' });
  }

  /**
   * Export des livreurs en Excel (XLSX/CSV fallback)
   */
  exportToExcel(): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/export/excel`, { responseType: 'blob' });
  }

  private generateCsv(drivers: DeliveryDriver[]): string {
    const headers = ['ID', 'Prénom', 'Nom', 'Téléphone', 'Véhicule', 'Plaque', 'Disponible'];
    const rows = drivers.map(d => [
      d.id,
      d.name,
      d.phone,
      d.email,
      d.isAvailable ? 'Oui' : 'Non',
      d.rating,
      d.statistics,
      d.totalDeliveries
    ]);

    return [
      headers.join(','),
      ...rows.map(row => row.join(','))
    ].join('\n');
  }
}