// src/app/core/services/driver.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, map, switchMap } from 'rxjs/operators';
import { 
  Driver, 
  DriverCreateRequest, 
  DriverUpdateRequest, 
  PhotoUploadResponse 
} from '../models/driver.model';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class DriverService {
  private readonly API_URL = `${environment.apiUrl}/delivery-persons`; // Adjust to your API base URL
  private readonly PHOTO_UPLOAD_URL = `${environment.apiUrl}/api/upload/driver-photo`; // Photo upload endpoint

  constructor(private http: HttpClient) {}

  /**
   * Get all drivers
   */
  getDrivers(): Observable<Driver[]> {
    return this.http.get<Driver[]>(this.API_URL).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Get driver by ID
   */
  getDriver(id: number): Observable<Driver> {
    return this.http.get<Driver>(`${this.API_URL}/${id}`).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Create a new driver
   */
  createDriver(request: DriverCreateRequest): Observable<Driver> {
    return this.http.post<Driver>(this.API_URL, request).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Update an existing driver
   */
  updateDriver(id: number, request: DriverUpdateRequest): Observable<Driver> {
    return this.http.put<Driver>(`${this.API_URL}/${id}`, request).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Delete a driver
   */
  deleteDriver(id: number): Observable<void> {
    return this.http.delete<void>(`${this.API_URL}/${id}`).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Upload driver photo and get URL
   */
  uploadPhoto(file: File): Observable<string> {
    const formData = new FormData();
    formData.append('photo', file);

    return this.http.post<PhotoUploadResponse>(this.PHOTO_UPLOAD_URL, formData).pipe(
      map(response => response.photo_url),
      catchError(this.handleError)
    );
  }

  /**
   * Create driver with photo upload
   * If file is provided, uploads it first, then creates driver with the returned URL
   */
  createDriverWithPhoto(request: DriverCreateRequest, photoFile?: File): Observable<Driver> {
    if (photoFile) {
      return this.uploadPhoto(photoFile).pipe(
        switchMap(photoUrl => {
          const requestWithPhoto: DriverCreateRequest = {
            ...request,
            photo_url: photoUrl
          };
          return this.createDriver(requestWithPhoto);
        })
      );
    }
    return this.createDriver(request);
  }

  /**
   * Update driver with photo upload
   * If file is provided, uploads it first, then updates driver with the returned URL
   */
  updateDriverWithPhoto(id: number, request: DriverUpdateRequest, photoFile?: File): Observable<Driver> {
    if (photoFile) {
      return this.uploadPhoto(photoFile).pipe(
        switchMap(photoUrl => {
          const requestWithPhoto: DriverUpdateRequest = {
            ...request,
            photo_url: photoUrl
          };
          return this.updateDriver(id, requestWithPhoto);
        })
      );
    }
    return this.updateDriver(id, request);
  }

  /**
   * Validate photo file
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
   * Handle HTTP errors
   */
  private handleError(error: any): Observable<never> {
    console.error('API Error:', error);
    
    let errorMessage = 'Une erreur est survenue';
    
    if (error.error instanceof ErrorEvent) {
      // Client-side error
      errorMessage = `Erreur: ${error.error.message}`;
    } else {
      // Server-side error
      errorMessage = `Erreur ${error.status}: ${error.error?.message || error.message}`;
    }
    
    return throwError(() => new Error(errorMessage));
  }
}