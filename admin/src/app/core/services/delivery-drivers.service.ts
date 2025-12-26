// src/app/features/delivery-drivers/delivery-drivers.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map, switchMap } from 'rxjs';
import { environment } from '../../../environments/environment';

/**
 * Driver model matching API response
 */
export interface DeliveryDriver {
  id: number;
  name: string;
  email: string;
  id_card_number: string;
  address: string;
  photo_url: string;
  is_available: boolean;
  availability_status: string;
  statistics: {
    total_deliveries: number;
    completed_deliveries: number;
    pending_deliveries: number;
  };
  created_at: string;
  updated_at: string;
  member_since: string;
}

/**
 * API Response wrapper
 */
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

/**
 * Request to create a driver
 */
export interface DriverCreateRequest {
  name: string;
  email: string;
  id_card_number: string;
  address: string;
  photo_url: string;
  is_available: boolean;
}

/**
 * Request to update a driver
 */
export interface DriverUpdateRequest {
  name?: string;
  email?: string;
  id_card_number?: string;
  address?: string;
  photo_url?: string;
  is_available?: boolean;
}

/**
 * Drivers list response with pagination
 */
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
  private apiUrl = `${environment.apiUrl}/admin/delivery-persons`;
  private apiExportUrl = `${environment.apiUrl}/admin/reports/delivery-persons/export`;

  constructor(private http: HttpClient) {}

  /**
   * Get all drivers with optional pagination and filters
   */
  getDrivers(page: number = 1, perPage: number = 10, filters?: any): Observable<DriversListResponse> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', perPage.toString());

    if (filters) {
      Object.keys(filters).forEach(key => {
        if (filters[key] !== null && filters[key] !== undefined) {
          params = params.set(key, filters[key]);
        }
      });
    }

    return this.http.get<ApiResponse<DeliveryDriver[]>>(this.apiUrl, { params }).pipe(
      map(response => ({
        data: response.data,
        total: response.data.length,
        page: page,
        perPage: perPage
      }))
    );
  }

  /**
   * Get driver by ID
   */
  getDriver(id: number): Observable<DeliveryDriver> {
    return this.http.get<ApiResponse<DeliveryDriver>>(`${this.apiUrl}/${id}`).pipe(
      map(response => response.data)
    );
  }

  /**
   * Create a new driver
   */
  createDriver(driver: DriverCreateRequest): Observable<DeliveryDriver> {
    return this.http.post<ApiResponse<DeliveryDriver>>(this.apiUrl, driver).pipe(
      map(response => response.data)
    );
  }

  /**
   * Update a driver
   */
  updateDriver(id: number, driver: DriverUpdateRequest): Observable<DeliveryDriver> {
    return this.http.put<ApiResponse<DeliveryDriver>>(`${this.apiUrl}/${id}`, driver).pipe(
      map(response => response.data)
    );
  }

  /**
   * Delete a driver
   */
  deleteDriver(id: number): Observable<void> {
    return this.http.delete<ApiResponse<void>>(`${this.apiUrl}/${id}`).pipe(
      map(() => undefined)
    );
  }

  /**
   * Toggle driver availability
   */
  toggleAvailability(id: number, isAvailable: boolean): Observable<DeliveryDriver> {
    return this.http.patch<ApiResponse<DeliveryDriver>>(
      `${this.apiUrl}/${id}/availability`, 
      { is_available: isAvailable }
    ).pipe(
      map(response => response.data)
    );
  }

  /**
   * Get available drivers only
   */
  getAvailableDrivers(): Observable<DeliveryDriver[]> {
    return this.http.get<ApiResponse<DeliveryDriver[]>>(`${this.apiUrl}?available=1`).pipe(
      map(response => response.data.filter(d => d.is_available))
    );
  }

  /**
   * Get driver statistics
   */
  getDriverStatistics(id: number): Observable<DeliveryDriver['statistics']> {
    return this.getDriver(id).pipe(
      map(driver => driver.statistics)
    );
  }

  /**
   * Find best driver for auto-assignment (closest available driver)
   */
  findBestDriver(deliveryLocation?: { latitude: number; longitude: number }): Observable<DeliveryDriver | null> {
    return this.getAvailableDrivers().pipe(
      map(drivers => {
        if (drivers.length === 0) return null;

        // Sort by least pending deliveries (or random if no location provided)
        drivers.sort((a, b) => {
          const aPending = a.statistics.pending_deliveries;
          const bPending = b.statistics.pending_deliveries;
          return aPending - bPending;
        });

        console.log('🚚 Auto-assignment - Best driver:', {
          name: drivers[0].name,
          pending: drivers[0].statistics.pending_deliveries,
          completed: drivers[0].statistics.completed_deliveries
        });

        return drivers[0];
      })
    );
  }

  /**
   * Export drivers to PDF
   */
  exportToPdf(): Observable<Blob> {
    return this.http.get(`${this.apiExportUrl}/pdf`, { responseType: 'blob' });
  }

  /**
   * Export drivers to Excel
   */
  exportToExcel(): Observable<Blob> {
    return this.http.get(`${this.apiExportUrl}/excel`, { responseType: 'blob' });
  }

  /**
   * Search drivers by name or email
   */
  searchDrivers(searchTerm: string): Observable<DeliveryDriver[]> {
    return this.http.get<ApiResponse<DeliveryDriver[]>>(
      `${this.apiUrl}?search=${encodeURIComponent(searchTerm)}`
    ).pipe(
      map(response => response.data)
    );
  }

  /**
   * Get drivers statistics summary
   */
  getDriversSummary(): Observable<{
    total: number;
    available: number;
    busy: number;
    totalDeliveries: number;
  }> {
    return this.getDrivers(1, 1000).pipe(
      map(response => {
        const drivers = response.data;
        return {
          total: drivers.length,
          available: drivers.filter(d => d.is_available).length,
          busy: drivers.filter(d => !d.is_available).length,
          totalDeliveries: drivers.reduce((sum, d) => sum + d.statistics.total_deliveries, 0)
        };
      })
    );
  }

  /**
   * Validate photo file before upload
   */
  validatePhotoFile(file: File): { valid: boolean; error?: string } {
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    if (!allowedTypes.includes(file.type)) {
      return {
        valid: false,
        error: 'Format non supporté. Utilisez JPG, PNG ou WebP.'
      };
    }

    if (file.size > maxSize) {
      return {
        valid: false,
        error: 'La taille du fichier ne doit pas dépasser 5MB.'
      };
    }

    return { valid: true };
  }

  /**
   * Upload photo and return URL
   * Note: Adjust endpoint based on your API
   */
  uploadPhoto(file: File): Observable<string> {
    const formData = new FormData();
    formData.append('photo', file);

    return this.http.post<ApiResponse<{ photo_url: string }>>(
      `${environment.apiUrl}/upload/driver-photo`,
      formData
    ).pipe(
      map(response => response.data.photo_url)
    );
  }

  /**
   * Create driver with photo upload
   */
  createDriverWithPhoto(request: DriverCreateRequest, photoFile?: File): Observable<DeliveryDriver> {
    if (photoFile) {
      return this.uploadPhoto(photoFile).pipe(
        switchMap(photoUrl => {
          const updatedRequest = { ...request, photo_url: photoUrl };
          return this.createDriver(updatedRequest);
        })
      );
    }
    return this.createDriver(request);
  }

  /**
   * Update driver with photo upload
   */
  updateDriverWithPhoto(id: number, request: DriverUpdateRequest, photoFile?: File): Observable<DeliveryDriver> {
    if (photoFile) {
      return this.uploadPhoto(photoFile).pipe(
        switchMap(photoUrl => {
          const updatedRequest = { ...request, photo_url: photoUrl };
          return this.updateDriver(id, updatedRequest);
        })
      );
    }
    return this.updateDriver(id, request);
  }
}