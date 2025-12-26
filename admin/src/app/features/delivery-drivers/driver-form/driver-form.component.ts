// src/app/features/delivery-drivers/driver-form/driver-form.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import { DriverService } from '../../../core/services/driver.service';
import { DomSanitizer } from '@angular/platform-browser';

import {
  Driver,
  PhotoSourceType,
  PhotoSelectionState,
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
    private driverService: DriverService,
    private sanitizer: DomSanitizer
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
          this.driver = driver;

          this.form.patchValue({
            name: driver.name,
            email: driver.email,
            idCardNumber: driver.id_card_number,
            address: driver.address,
            isAvailable: driver.is_available
          });

          if (driver.photo_url) {
            this.photoState.previewUrl = driver.photo_url;
          }

          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger les détails du livreur';
          this.loading = false;
        }
      });
  }

  /**
   * Handle photo file selection
   */
  onPhotoFileSelected(event: Event): void {
    console.log('🔥 onPhotoFileSelected CALLED!', event);

    const input = event.target as HTMLInputElement;
    console.log('📂 Input element:', input);
    console.log('📂 Files:', input.files);

    if (!input.files || input.files.length === 0) {
      console.log('❌ No files selected');
      return;
    }

    const file = input.files[0];
    console.log('✅ File selected:', {
      name: file.name,
      size: file.size,
      type: file.type
    });

    // Validation simple
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    if (!allowedTypes.includes(file.type)) {
      console.log('❌ Invalid file type');
      this.photoError = 'Format non supporté. Utilisez JPG, PNG ou WebP.';
      return;
    }

    if (file.size > maxSize) {
      console.log('❌ File too large');
      this.photoError = 'La taille du fichier ne doit pas dépasser 5MB.';
      return;
    }

    console.log('✅ File validation passed');
    this.photoError = null;

    // Revoke previous URL
    if (this.photoState.previewUrl && this.photoState.sourceType === PhotoSourceType.FILE) {
      URL.revokeObjectURL(this.photoState.previewUrl);
      console.log('🗑️ Previous URL revoked');
    }

    // Create new preview URL
    const previewUrl = URL.createObjectURL(file);
    console.log('🎨 Preview URL created:', previewUrl);

    // Update state
    this.photoState = {
      sourceType: PhotoSourceType.FILE,
      file: file,
      url: '',
      previewUrl: previewUrl
    };

    console.log('📦 Updated photoState:', this.photoState);
    console.log('🖼️ Preview should show:', this.photoState.previewUrl);
  }


  /**
   * Clear photo selection
   */
  clearPhoto(): void {
    console.log('🗑️ Clearing photo');

    if (this.photoState.previewUrl && this.photoState.sourceType === PhotoSourceType.FILE) {
      URL.revokeObjectURL(this.photoState.previewUrl);
    }

    this.photoState = {
      sourceType: PhotoSourceType.NONE,
      file: null,
      url: '',
      previewUrl: ''
    };

    this.photoError = null;
    console.log('✅ Photo cleared');
  }


  /**
   * Revoke object URL to free memory
   */
  private revokePreviewUrl(): void {
    if (this.photoState.previewUrl && this.photoState.sourceType === PhotoSourceType.FILE) {
      URL.revokeObjectURL(this.photoState.previewUrl);
      console.log('🗑️ Previous preview URL revoked');
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

    const formData = new FormData();
    formData.append('name', formValue.name);
    formData.append('email', formValue.email);
    formData.append('id_card_number', formValue.idCardNumber);
    formData.append('address', formValue.address);
    formData.append('is_available', String(formValue.isAvailable));
    if (this.photoState.file)
      formData.append('photo', this.photoState.file);

    this.driverService.createDriver(formData)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          alert('Livreur créé avec succès');
          this.router.navigate(['/drivers']);
        },
        error: (err) => {
          this.error = err.message || 'Erreur lors de la création';
          this.submitting = false;
        }
      });
  }


  /**
   * Update existing driver
   */
  private updateDriver(formValue: any): void {
    if (!this.driver) return;

    const formData = new FormData();
    formData.append('name', formValue.name);
    formData.append('email', formValue.email);
    formData.append('id_card_number', formValue.idCardNumber);
    formData.append('address', formValue.address);
    formData.append('is_available', String(formValue.isAvailable));

    if (this.photoState.file) {
      formData.append('photo', this.photoState.file);
    }

    this.driverService.updateDriver(this.driver.id, formData)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          alert('Livreur mis à jour avec succès');
          this.router.navigate(['/drivers']);
        },
        error: (err) => {
          this.error = err.message || 'Erreur lors de la mise à jour';
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