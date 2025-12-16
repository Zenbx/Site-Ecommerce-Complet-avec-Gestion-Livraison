// src/app/features/delivery-drivers/delivery-drivers.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, of, delay, map } from 'rxjs';
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
  private apiUrl = `${environment.apiUrl}/drivers`;
  
  // 🧪 MODE MOCK - Changez à false pour utiliser la vraie API
  private MOCK_MODE = true;

  // Données mock persistantes
  private mockDrivers: DeliveryDriver[] = [
    {
      id: 1,
      firstName: 'Ahmed',
      lastName: 'Hassan',
      phone: '+237612345678',
      vehicleType: 'Voiture',
      vehiclePlate: 'ABC-1234',
      isAvailable: true,
      currentLocation: {
        latitude: 3.8480,
        longitude: 11.5021,
        updatedAt: new Date().toISOString()
      }
    },
    {
      id: 2,
      firstName: 'Marie',
      lastName: 'Dupont',
      phone: '+237698765432',
      vehicleType: 'Moto',
      vehiclePlate: 'XYZ-5678',
      isAvailable: true,
      currentLocation: {
        latitude: 3.8470,
        longitude: 11.5025,
        updatedAt: new Date().toISOString()
      }
    },
    {
      id: 3,
      firstName: 'Carlos',
      lastName: 'Rodriguez',
      phone: '+237650123456',
      vehicleType: 'Camion',
      vehiclePlate: 'DEF-9012',
      isAvailable: true,
      currentLocation: {
        latitude: 3.8490,
        longitude: 11.5018,
        updatedAt: new Date().toISOString()
      }
    },
    {
      id: 4,
      firstName: 'Fatima',
      lastName: 'Benali',
      phone: '+237610111213',
      vehicleType: 'Voiture',
      vehiclePlate: 'GHI-3456',
      isAvailable: true,
      currentLocation: {
        latitude: 3.8485,
        longitude: 11.5022,
        updatedAt: new Date().toISOString()
      }
    },
    {
      id: 5,
      firstName: 'Jean',
      lastName: 'Martin',
      phone: '+237671415161',
      vehicleType: 'Fourgon',
      vehiclePlate: 'JKL-7890',
      isAvailable: false,
      currentLocation: {
        latitude: 3.8475,
        longitude: 11.5028,
        updatedAt: new Date().toISOString()
      }
    },
    {
      id: 6,
      firstName: 'Sofia',
      lastName: 'Garcia',
      phone: '+237680192021',
      vehicleType: 'Voiture',
      vehiclePlate: 'MNO-2345',
      isAvailable: false,
      currentLocation: {
        latitude: 3.8495,
        longitude: 11.5015,
        updatedAt: new Date().toISOString()
      }
    },
    {
      id: 7,
      firstName: 'Moussa',
      lastName: 'Traore',
      phone: '+237670222324',
      vehicleType: 'Moto',
      vehiclePlate: 'PQR-6789',
      isAvailable: true,
      currentLocation: {
        latitude: 3.8465,
        longitude: 11.5030,
        updatedAt: new Date().toISOString()
      }
    }
  ];

  constructor(private http: HttpClient) {}

  /**
   * Récupère la liste des livreurs avec pagination et filtres
   */
  getDrivers(page: number = 1, perPage: number = 10, filters?: any): Observable<DriversListResponse> {
    if (this.MOCK_MODE) {
      return this.getMockDrivers(page, perPage, filters);
    }

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
    if (this.MOCK_MODE) {
      const driver = this.mockDrivers.find(d => d.id === id);
      return of(driver!).pipe(delay(300));
    }

    return this.http.get<DeliveryDriver>(`${this.apiUrl}/${id}`);
  }

  /**
   * Crée un nouveau livreur
   */
  createDriver(driver: DriverCreateRequest): Observable<DeliveryDriver> {
    if (this.MOCK_MODE) {
      const newDriver: DeliveryDriver = {
        id: Math.max(...this.mockDrivers.map(d => d.id)) + 1,
        ...driver,
        isAvailable: true,
        currentLocation: {
          latitude: 3.8480 + (Math.random() - 0.5) * 0.01,
          longitude: 11.5021 + (Math.random() - 0.5) * 0.01,
          updatedAt: new Date().toISOString()
        }
      };
      this.mockDrivers.push(newDriver);
      return of(newDriver).pipe(delay(500));
    }

    return this.http.post<DeliveryDriver>(this.apiUrl, driver);
  }

  /**
   * Met à jour un livreur
   */
  updateDriver(id: number, driver: DriverUpdateRequest): Observable<DeliveryDriver> {
    if (this.MOCK_MODE) {
      const index = this.mockDrivers.findIndex(d => d.id === id);
      if (index !== -1) {
        this.mockDrivers[index] = { ...this.mockDrivers[index], ...driver };
        return of(this.mockDrivers[index]).pipe(delay(500));
      }
      throw new Error('Driver not found');
    }

    return this.http.put<DeliveryDriver>(`${this.apiUrl}/${id}`, driver);
  }

  /**
   * Supprime un livreur
   */
  deleteDriver(id: number): Observable<void> {
    if (this.MOCK_MODE) {
      const index = this.mockDrivers.findIndex(d => d.id === id);
      if (index !== -1) {
        this.mockDrivers.splice(index, 1);
      }
      return of(void 0).pipe(delay(500));
    }

    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }

  /**
   * Change la disponibilité d'un livreur
   */
  toggleAvailability(id: number, isAvailable: boolean): Observable<DeliveryDriver> {
    if (this.MOCK_MODE) {
      const driver = this.mockDrivers.find(d => d.id === id);
      if (driver) {
        driver.isAvailable = isAvailable;
        return of(driver).pipe(delay(300));
      }
      throw new Error('Driver not found');
    }

    return this.http.patch<DeliveryDriver>(`${this.apiUrl}/${id}/availability`, { isAvailable });
  }

  /**
   * Récupère les livreurs disponibles
   */
  getAvailableDrivers(): Observable<DeliveryDriver[]> {
    if (this.MOCK_MODE) {
      const available = this.mockDrivers.filter(d => d.isAvailable);
      return of(available).pipe(delay(300));
    }

    return this.http.get<DeliveryDriver[]>(`${this.apiUrl}/available`);
  }

  /**
   * Récupère les statistiques d'un livreur
   */
  getDriverStatistics(id: number): Observable<DriverStatistics> {
    if (this.MOCK_MODE) {
      const mockStats: DriverStatistics = {
        totalDeliveries: Math.floor(Math.random() * 100) + 50,
        completedDeliveries: Math.floor(Math.random() * 80) + 40,
        failedDeliveries: Math.floor(Math.random() * 5) + 1,
        averageDeliveryTime: Math.floor(Math.random() * 15) + 25,
        successRate: Math.floor(Math.random() * 10 + 90),
        totalDistance: Math.floor(Math.random() * 500) + 200,
        rating: Math.floor(Math.random() * 15 + 40) / 10,
        lastDelivery: new Date(Date.now() - Math.random() * 86400000).toISOString()
      };
      return of(mockStats).pipe(delay(400));
    }

    return this.http.get<DriverStatistics>(`${this.apiUrl}/${id}/statistics`);
  }

  /**
   * 🎯 ASSIGNATION AUTOMATIQUE - Trouve le meilleur livreur pour une livraison
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
        
        console.log('🎯 Auto-assignment:', {
          total: drivers.length,
          closest: driversWithDistance[0].driver.firstName + ' ' + driversWithDistance[0].driver.lastName,
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
    if (this.MOCK_MODE) {
      const csv = this.generateCsv(this.mockDrivers);
      const blob = new Blob([csv], { type: 'text/csv' });
      return of(blob).pipe(delay(300));
    }

    return this.http.get(`${this.apiUrl}/export/csv`, { responseType: 'blob' });
  }

  /**
   * Export des livreurs en PDF
   */
  exportToPdf(): Observable<Blob> {
    if (this.MOCK_MODE) {
      // Simple mock PDF as text blob (good enough for dev). Replace with real PDF generation on backend.
      const content = `Liste des livreurs - ${new Date().toLocaleString()}\n\n` +
        this.mockDrivers.map(d => {
          const name = (d as any).name ?? ((d.firstName && d.lastName) ? `${d.firstName} ${d.lastName}` : 'N/A');
          const email = (d as any).email ?? '';
          return `${d.id} - ${name} - ${email} - ${d.isAvailable ? 'Disponible' : 'Indisponible'}`;
        }).join('\n');
      const blob = new Blob([content], { type: 'application/pdf' });
      return of(blob).pipe(delay(300));
    }

    return this.http.get(`${this.apiUrl}/export/pdf`, { responseType: 'blob' });
  }

  /**
   * Export des livreurs en Excel (XLSX/CSV fallback)
   */
  exportToExcel(): Observable<Blob> {
    if (this.MOCK_MODE) {
      // Use CSV content but return as Excel MIME type for compatibility in development
      const csv = this.generateCsv(this.mockDrivers);
      const blob = new Blob([csv], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      return of(blob).pipe(delay(300));
    }

    return this.http.get(`${this.apiUrl}/export/excel`, { responseType: 'blob' });
  }

  private generateCsv(drivers: DeliveryDriver[]): string {
    const headers = ['ID', 'Prénom', 'Nom', 'Téléphone', 'Véhicule', 'Plaque', 'Disponible'];
    const rows = drivers.map(d => [
      d.id,
      d.firstName,
      d.lastName,
      d.phone,
      d.vehicleType,
      d.vehiclePlate,
      d.isAvailable ? 'Oui' : 'Non'
    ]);

    return [
      headers.join(','),
      ...rows.map(row => row.join(','))
    ].join('\n');
  }

  // ========================================
  // MÉTHODES MOCK PRIVÉES
  // ========================================

  private getMockDrivers(page: number, perPage: number, filters?: any): Observable<DriversListResponse> {
    let filteredDrivers = [...this.mockDrivers];

    // Filtre par recherche
    if (filters?.search) {
      const search = filters.search.toLowerCase();
      filteredDrivers = filteredDrivers.filter(d =>
        (d.firstName?.toLowerCase().includes(search) ?? false) ||
        (d.lastName?.toLowerCase().includes(search) ?? false) ||
        (d.phone?.includes(search) ?? false) ||
        (d.vehiclePlate?.toLowerCase().includes(search) ?? false)
      );
    }

    // Filtre par disponibilité
    if (filters?.is_available !== undefined) {
      filteredDrivers = filteredDrivers.filter(d => d.isAvailable === filters.is_available);
    }

    // Filtre par type de véhicule
    if (filters?.vehicleType) {
      filteredDrivers = filteredDrivers.filter(d =>
        (d.vehicleType?.toLowerCase() ?? '') === filters.vehicleType.toLowerCase()
      );
    }

    // Pagination
    const total = filteredDrivers.length;
    const startIndex = (page - 1) * perPage;
    const endIndex = startIndex + perPage;
    const paginatedDrivers = filteredDrivers.slice(startIndex, endIndex);

    const response: DriversListResponse = {
      data: paginatedDrivers,
      total: total,
      page: page,
      perPage: perPage
    };

    return of(response).pipe(delay(500));
  }
}