// src/app/core/models/driver.model.ts

/**
 * Interface for driver entity from backend
 */
export interface Driver {
  id: number;
  name: string;
  email: string;
  id_card_number: string;
  address: string;
  photo_url: string;
  is_available: boolean;
  created_at?: string;
  updated_at?: string;
  is_online: boolean;
  current_longitude: string;
  current_latitude: string;
  last_location_update: string;
  current_address: string;
}

/**
 * Request payload for creating a new driver
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
 * Request payload for updating an existing driver
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
 * Response from photo upload endpoint
 */
export interface PhotoUploadResponse {
  photo_url: string;
  message?: string;
}

/**
 * Class to handle driver form data with file upload
 */
export class DriverFormData {
  name: string = '';
  email: string = '';
  idCardNumber: string = '';
  address: string = '';
  photoUrl: string = '';
  photoFile: File | null = null;
  isAvailable: boolean = true;

  /**
   * Convert form data to create request
   */
  toCreateRequest(): DriverCreateRequest {
    return {
      name: this.name,
      email: this.email,
      id_card_number: this.idCardNumber,
      address: this.address,
      photo_url: this.photoUrl,
      is_available: this.isAvailable
    };
  }

  /**
   * Convert form data to update request
   */
  toUpdateRequest(): DriverUpdateRequest {
    const request: DriverUpdateRequest = {};
    
    if (this.name) request.name = this.name;
    if (this.email) request.email = this.email;
    if (this.idCardNumber) request.id_card_number = this.idCardNumber;
    if (this.address) request.address = this.address;
    if (this.photoUrl) request.photo_url = this.photoUrl;
    if (this.isAvailable !== undefined) request.is_available = this.isAvailable;
    
    return request;
  }

  /**
   * Check if a photo file is selected
   */
  hasPhotoFile(): boolean {
    return this.photoFile !== null;
  }

  /**
   * Get FormData for multipart upload
   */
  getPhotoFormData(): FormData {
    const formData = new FormData();
    if (this.photoFile) {
      formData.append('photo', this.photoFile);
    }
    return formData;
  }

  /**
   * Load data from existing driver
   */
  static fromDriver(driver: Driver): DriverFormData {
    const formData = new DriverFormData();
    formData.name = driver.name;
    formData.email = driver.email;
    formData.idCardNumber = driver.id_card_number;
    formData.address = driver.address;
    formData.photoUrl = driver.photo_url;
    formData.isAvailable = driver.is_available;
    return formData;
  }
}

/**
 * Enum for photo source type
 */
export enum PhotoSourceType {
  FILE = 'FILE',
  URL = 'URL',
  NONE = 'NONE'
}

/**
 * Interface for photo selection state
 */
export interface PhotoSelectionState {
  sourceType: PhotoSourceType;
  file: File | null;
  url: string;
  previewUrl: string;
  objectUrl?: string;
}

/**
 * Validator helper class for driver data
 */
export class DriverValidator {
  static isValidEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  static isValidIdCardNumber(idCard: string): boolean {
    return idCard.length >= 6;
  }

  static isValidPhotoUrl(url: string): boolean {
    try {
      new URL(url);
      return true;
    } catch {
      return false;
    }
  }

  static isValidName(name: string): boolean {
    return name.trim().length >= 2;
  }

  static isValidAddress(address: string): boolean {
    return address.trim().length >= 5;
  }
}