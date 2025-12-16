// src/app/features/delivery-drivers/driver-form/driver-form.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import { DeliveryDriversService, DriverCreateRequest, DriverUpdateRequest } from '../delivery-drivers.service';
import { DeliveryDriver } from '../../deliveries/models/delivery.model';

@Component({
  selector: 'app-driver-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './driver-form.component.html',
  styleUrls: ['./driver-form.component.scss']
})
export class DriverFormComponent implements OnInit, OnDestroy {
  form!: FormGroup;
  driver: DeliveryDriver | null = null;
  loading = false;
  submitting = false;
  error: string | null = null;
  isEditMode = false;
  private destroy$ = new Subject<void>();

  vehicleTypes = [
    { value: 'Voiture', label: 'Voiture' },
    { value: 'Moto', label: 'Moto' },
    { value: 'Camion', label: 'Camion' },
    { value: 'Fourgon', label: 'Fourgon' },
    { value: 'Vélo', label: 'Vélo' }
  ];

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private driversService: DeliveryDriversService
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
          this.loadDriver(driverId);
        }
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  /**
   * INITIALISER LE FORMULAIRE
   */
  private initializeForm(): void {
    this.form = this.fb.group({
      firstName: ['', [Validators.required, Validators.minLength(2)]],
      lastName: ['', [Validators.required, Validators.minLength(2)]],
      phone: ['', [Validators.required, Validators.pattern(/^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/)]],
      email: ['', [Validators.email]],
      vehicleType: ['', Validators.required],
      vehiclePlate: ['', [Validators.required, Validators.minLength(3)]],
      licenseNumber: ['', [Validators.required, Validators.minLength(5)]]
    });
  }

  /**
   * CHARGER LES DÉTAILS DU LIVREUR
   */
  private loadDriver(driverId: number): void {
    this.loading = true;
    this.driversService.getDriver(driverId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (driver) => {
          console.log('📦 Livreur chargé:', driver);
          this.driver = driver;
          this.form.patchValue({
            firstName: driver.firstName,
            lastName: driver.lastName,
            phone: driver.phone,
            vehicleType: driver.vehicleType,
            vehiclePlate: driver.vehiclePlate
          });
          this.loading = false;
        },
        error: (error) => {
          console.error('❌ Erreur lors du chargement du livreur:', error);
          this.error = 'Impossible de charger les détails du livreur';
          this.loading = false;
        }
      });
  }

  /**
   * SOUMETTRE LE FORMULAIRE
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
      // Mode édition
      const updateRequest: DriverUpdateRequest = {
        firstName: formValue.firstName,
        lastName: formValue.lastName,
        phone: formValue.phone,
        email: formValue.email,
        vehicleType: formValue.vehicleType,
        vehiclePlate: formValue.vehiclePlate
      };

      this.driversService.updateDriver(this.driver.id, updateRequest)
        .pipe(takeUntil(this.destroy$))
        .subscribe({
          next: (updatedDriver) => {
            console.log('✅ Livreur mis à jour avec succès');
            alert('Livreur mis à jour avec succès');
            this.router.navigate(['/drivers']);
          },
          error: (error) => {
            console.error('❌ Erreur lors de la mise à jour:', error);
            this.error = 'Erreur lors de la mise à jour du livreur';
            this.submitting = false;
          }
        });
    } else {
      // Mode création
      const createRequest: DriverCreateRequest = formValue;

      this.driversService.createDriver(createRequest)
        .pipe(takeUntil(this.destroy$))
        .subscribe({
          next: (newDriver) => {
            console.log('✅ Livreur créé avec succès');
            alert('Livreur créé avec succès');
            this.router.navigate(['/drivers']);
          },
          error: (error) => {
            console.error('❌ Erreur lors de la création:', error);
            this.error = 'Erreur lors de la création du livreur';
            this.submitting = false;
          }
        });
    }
  }

  /**
   * RETOURNER À LA LISTE
   */
  goBack(): void {
    this.router.navigate(['/drivers']);
  }

  /**
   * MARQUER TOUS LES CHAMPS COMME TOUCHÉS
   */
  private markFormGroupTouched(formGroup: FormGroup): void {
    Object.keys(formGroup.controls).forEach(key => {
      const control = formGroup.get(key);
      control?.markAsTouched();
    });
  }

  /**
   * VÉRIFIER SI UN CHAMP A UNE ERREUR
   */
  hasError(fieldName: string, errorType: string): boolean {
    const control = this.form.get(fieldName);
    return !!(control && control.hasError(errorType) && (control.dirty || control.touched));
  }

  /**
   * OBTENIR LE MESSAGE D'ERREUR
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
   * VÉRIFIER SI LE FORMULAIRE EST VALIDE
   */
  isFormValid(): boolean {
    return this.form.valid;
  }
}
