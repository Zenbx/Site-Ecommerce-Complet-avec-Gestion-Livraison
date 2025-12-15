// src/app/features/delivery-drivers/delivery-drivers-list/delivery-drivers-list.component.ts

import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { DeliveryDriversService } from '../delivery-drivers.service';
import { DeliveryDriver } from '../../deliveries/models/delivery.model';

@Component({
  selector: 'app-delivery-drivers-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './delivery-drivers-list.component.html',
  styleUrls: ['./delivery-drivers-list.component.scss']
})
export class DeliveryDriversListComponent implements OnInit {
  drivers: DeliveryDriver[] = [];
  loading = true;
  currentPage = 1;
  perPage = 10;
  totalDrivers = 0;
  searchTerm = '';
  filterAvailability: string = 'all';

  constructor(
    private driversService: DeliveryDriversService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadDrivers();
  }

  loadDrivers(): void {
    this.loading = true;
    const filters: any = {};
    
    if (this.searchTerm) {
      filters.search = this.searchTerm;
    }
    
    if (this.filterAvailability !== 'all') {
      filters.is_available = this.filterAvailability === 'available';
    }

    this.driversService.getDrivers(this.currentPage, this.perPage, filters)
      .subscribe({
        next: (response) => {
          this.drivers = response.data;
          this.totalDrivers = response.total;
          this.loading = false;
        },
        error: (error) => {
          console.error('Error loading drivers:', error);
          this.loading = false;
        }
      });
  }

  onSearch(): void {
    this.currentPage = 1;
    this.loadDrivers();
  }

  onFilterChange(): void {
    this.currentPage = 1;
    this.loadDrivers();
  }

  onPageChange(page: number): void {
    this.currentPage = page;
    this.loadDrivers();
  }

  createDriver(): void {
    this.router.navigate(['/drivers/new']);
  }

  editDriver(driver: DeliveryDriver): void {
    this.router.navigate(['/drivers/edit', driver.id]);
  }

  toggleAvailability(driver: DeliveryDriver): void {
    this.driversService.toggleAvailability(driver.id, !driver.isAvailable)
      .subscribe({
        next: (updatedDriver) => {
          driver.isAvailable = updatedDriver.isAvailable;
        },
        error: (error) => {
          console.error('Error toggling availability:', error);
          alert('Erreur lors de la modification de la disponibilité');
        }
      });
  }

  deleteDriver(driver: DeliveryDriver): void {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${driver.firstName} ${driver.lastName} ?`)) {
      this.driversService.deleteDriver(driver.id)
        .subscribe({
          next: () => {
            this.loadDrivers();
          },
          error: (error) => {
            console.error('Error deleting driver:', error);
            alert('Erreur lors de la suppression du livreur');
          }
        });
    }
  }

  viewStatistics(driver: DeliveryDriver): void {
    this.router.navigate(['/drivers', driver.id, 'statistics']);
  }

  getTotalPages(): number {
    return Math.ceil(this.totalDrivers / this.perPage);
  }
}