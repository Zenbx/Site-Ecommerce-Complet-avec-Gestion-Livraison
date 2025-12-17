// src/app/features/delivery-drivers/driver-form/driver-form.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import { DriverService } from '../../../core/services/driver.service';
import { 
  Driver, 
  DriverCreateRequest, 
  DriverUpdateRequest,
  DriverFormData,
  PhotoSourceType,
  PhotoSelectionState,
  DriverValidator
} from '../../../core/models/driver.model';

@Component({
  selector: 'app-driver-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './driver-form.component.html',
  styleUrls: ['./driver-form.component.scss']
})
export class DriverFormComponent implements OnInit, OnDestroy {
  form!: FormGroup;
  driver: Driver | null = null;
  loading = false;
  submitting = false;
  uploadingPhoto = false;
  error: string | null = null;
  isEditMode = false;
  
  // Photo management
  photoState: PhotoSelectionState = {
    sourceType: PhotoSourceType.NONE,
    file: null,
    url: '',
    previewUrl: ''
  };
  
  photoError: string | null = null;
  readonly PhotoSourceType = PhotoSourceType;
  
  private destroy$ = new Subject<void>();

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private driverService: DriverService
  ) {
    this.initializeForm();
  }

  ngOnInit(): void {
    this.route.params
      .pipe(takeUntil(this.destroy$))
      .subscribe(params => {
        const driverId = params['id'];
        if (driverId && driverId !== 'new') {
          this.isEditMode = true;
          this.loadDriver(+driverId);
        }
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    this.revokePreviewUrl();
  }

  /**
   * Initialize form with validators
   */
  private initializeForm(): void {
    this.form = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      idCardNumber: ['', [Validators.required, Validators.minLength(6)]],
      address: ['', [Validators.required, Validators.minLength(5)]],
      photoUrl: [''],
      isAvailable: [true]
    });
  }

  /**
   * Load driver details
   */
  private loadDriver(driverId: number): void {
    this.loading = true;
    this.driverService.getDriver(driverId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (driver) => {
          console.log('📦 Driver loaded:', driver);
          this.driver = driver;
          this.form.patchValue({
            name: driver.name,
            email: driver.email,
            idCardNumber: driver.id_card_number,
            address: driver.address,
            photoUrl: driver.photo_url,
            isAvailable: driver.is_available
          });
          
          // Set existing photo as URL type
          if (driver.photo_url) {
            this.photoState = {
              sourceType: PhotoSourceType.URL,
              file: null,
              url: driver.photo_url,
              previewUrl: driver.photo_url
            };
          }
          
          this.loading = false;
        },
        error: (error) => {
          console.error('❌ Error loading driver:', error);
          this.error = 'Impossible de charger les détails du livreur';
          this.loading = false;
        }
      });
  }

  /**
   * Handle photo file selection
   */
  onPhotoFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (!input.files || input.files.length === 0) return;

    const file = input.files[0];
    this.photoError = null;

    // Validate file
    const validation = this.driverService.validatePhotoFile(file);
    if (!validation.valid) {
      this.photoError = validation.error || 'Fichier invalide';
      input.value = '';
      return;
    }

    // Revoke previous preview URL
    this.revokePreviewUrl();

    // Create preview URL
    const previewUrl = URL.createObjectURL(file);

    this.photoState = {
      sourceType: PhotoSourceType.FILE,
      file: file,
      url: '',
      previewUrl: previewUrl
    };

    console.log('📸 Photo file selected:', file.name);
  }

  /**
   * Handle photo URL input
   */
  onPhotoUrlChanged(event: Event): void {
    const input = event.target as HTMLInputElement;
    const url = input.value;
    
    this.photoError = null;
    
    if (!url.trim()) {
      this.photoState = {
        sourceType: PhotoSourceType.NONE,
        file: null,
        url: '',
        previewUrl: ''
      };
      return;
    }

    if (!DriverValidator.isValidPhotoUrl(url)) {
      this.photoError = 'URL invalide';
      return;
    }

    this.revokePreviewUrl();

    this.photoState = {
      sourceType: PhotoSourceType.URL,
      file: null,
      url: url,
      previewUrl: url
    };

    this.form.patchValue({ photoUrl: url });
  }

  /**
   * Clear photo selection
   */
  clearPhoto(): void {
    this.revokePreviewUrl();
    this.photoState = {
      sourceType: PhotoSourceType.NONE,
      file: null,
      url: '',
      previewUrl: ''
    };
    this.form.patchValue({ photoUrl: '' });
    this.photoError = null;
  }

  /**
   * Revoke object URL to free memory
   */
  private revokePreviewUrl(): void {
    if (this.photoState.previewUrl && this.photoState.sourceType === PhotoSourceType.FILE) {
      URL.revokeObjectURL(this.photoState.previewUrl);
    }
  }

  /**
   * Submit form
   */
  onSubmit(): void {
    if (this.form.invalid) {
      this.markFormGroupTouched(this.form);
      return;
    }

    this.submitting = true;
    this.error = null;

    const formValue = this.form.value;

    if (this.isEditMode && this.driver) {
      this.updateDriver(formValue);
    } else {
      this.createDriver(formValue);
    }
  }

  /**
   * Create new driver
   */
  private createDriver(formValue: any): void {
    const request: DriverCreateRequest = {
      name: formValue.name,
      email: formValue.email,
      id_card_number: formValue.idCardNumber,
      address: formValue.address,
      photo_url: this.photoState.url || formValue.photoUrl || '',
      is_available: formValue.isAvailable
    };

    // If file is selected, upload it first
    const observable = this.photoState.sourceType === PhotoSourceType.FILE && this.photoState.file
      ? this.driverService.createDriverWithPhoto(request, this.photoState.file)
      : this.driverService.createDriver(request);

    observable
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (newDriver) => {
          console.log('✅ Driver created successfully:', newDriver);
          alert('Livreur créé avec succès');
          this.router.navigate(['/drivers']);
        },
        error: (error) => {
          console.error('❌ Error creating driver:', error);
          this.error = error.message || 'Erreur lors de la création du livreur';
          this.submitting = false;
        }
      });
  }

  /**
   * Update existing driver
   */
  private updateDriver(formValue: any): void {
    if (!this.driver) return;

    const request: DriverUpdateRequest = {
      name: formValue.name,
      email: formValue.email,
      id_card_number: formValue.idCardNumber,
      address: formValue.address,
      is_available: formValue.isAvailable
    };

    // Only update photo if changed
    if (this.photoState.sourceType === PhotoSourceType.URL) {
      request.photo_url = this.photoState.url;
    }

    // If file is selected, upload it first
    const observable = this.photoState.sourceType === PhotoSourceType.FILE && this.photoState.file
      ? this.driverService.updateDriverWithPhoto(this.driver.id, request, this.photoState.file)
      : this.driverService.updateDriver(this.driver.id, request);

    observable
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (updatedDriver) => {
          console.log('✅ Driver updated successfully:', updatedDriver);
          alert('Livreur mis à jour avec succès');
          this.router.navigate(['/drivers']);
        },
        error: (error) => {
          console.error('❌ Error updating driver:', error);
          this.error = error.message || 'Erreur lors de la mise à jour du livreur';
          this.submitting = false;
        }
      });
  }

  /**
   * Navigate back to list
   */
  goBack(): void {
    this.router.navigate(['/drivers']);
  }

  /**
   * Mark all form fields as touched
   */
  private markFormGroupTouched(formGroup: FormGroup): void {
    Object.keys(formGroup.controls).forEach(key => {
      const control = formGroup.get(key);
      control?.markAsTouched();
    });
  }

  /**
   * Check if field has error
   */
  hasError(fieldName: string, errorType: string): boolean {
    const control = this.form.get(fieldName);
    return !!(control && control.hasError(errorType) && (control.dirty || control.touched));
  }

  /**
   * Get error message for field
   */
  getErrorMessage(fieldName: string): string {
    const control = this.form.get(fieldName);
    if (!control || !control.errors) return '';

    const errors = control.errors;
    if (errors['required']) return 'Ce champ est obligatoire';
    if (errors['minlength']) return `Minimum ${errors['minlength'].requiredLength} caractères`;
    if (errors['email']) return 'Email invalide';
    if (errors['pattern']) return 'Format invalide';

    return 'Ce champ est invalide';
  }

  /**
   * Check if form is valid
   */
  isFormValid(): boolean {
    return this.form.valid && this.photoState.sourceType !== PhotoSourceType.NONE;
  }
}