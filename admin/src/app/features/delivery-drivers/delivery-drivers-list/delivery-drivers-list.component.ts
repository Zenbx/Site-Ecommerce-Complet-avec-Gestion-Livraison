// src/app/features/delivery-drivers/delivery-drivers-list/delivery-drivers-list.component.ts
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { DeliveryDriversService, DeliveryDriver } from '../../../core/services/delivery-drivers.service';

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
  searchTerm = '';
  filter: 'all' | 'available' | 'busy' = 'all';

  constructor(
    private driversService: DeliveryDriversService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.loadDrivers();
  }

  /**
   * Charge les livreurs via le service
   */
  loadDrivers(): void {
    this.loading = true;
    // On récupère une large plage pour la gestion locale des filtres style "Classroom"
    this.driversService.getDrivers(1, 100, { search: this.searchTerm })
      .subscribe({
        next: (response) => {
          this.drivers = response.data;
          this.loading = false;
        },
        error: (error) => {
          console.error('❌ Error loading drivers:', error);
          this.loading = false;
        }
      });
  }

  /**
   * Retourne la liste filtrée pour le template
   */
  filteredDrivers(): DeliveryDriver[] {
    if (!this.drivers) return [];
    
    let list = this.drivers;

    // Filtre de statut (Tabs)
    if (this.filter === 'available') {
      list = list.filter(d => d.is_available);
    } else if (this.filter === 'busy') {
      list = list.filter(d => !d.is_available);
    }

    return list;
  }

  /**
   * Gestion de la recherche
   */
  onSearch(event: any): void {
    this.searchTerm = event.target.value;
    this.loadDrivers();
  }

  /**
   * Change le filtre actif (Tabs)
   */
  setFilter(filterType: 'all' | 'available' | 'busy'): void {
    this.filter = filterType;
  }

  /**
   * Navigation vers création
   */
  createDriver(): void {
    this.router.navigate(['/drivers/new']);
  }

  /**
   * Navigation vers édition
   */
  editDriver(driver: DeliveryDriver): void {
    this.router.navigate(['/drivers/edit', driver.id]);
  }

  /**
   * Suppression d'un livreur
   */
  deleteDriver(driver: DeliveryDriver): void {
    if (confirm(`Supprimer définitivement le livreur ${driver.name} ?`)) {
      this.driversService.deleteDriver(driver.id).subscribe({
        next: () => this.loadDrivers(),
        error: (err) => alert('Erreur lors de la suppression')
      });
    }
  }

  /**
   * Actions de la carte
   */
  viewStats(driver: DeliveryDriver): void {
    this.router.navigate(['/drivers', driver.id, 'statistics']);
  }

  assignTask(driver: DeliveryDriver): void {
    if (!driver.is_available) {
      alert('Ce livreur est actuellement occupé.');
      return;
    }
    // Logique d'assignation ou redirection
    console.log('Assignation pour:', driver.id);
  }

  /**
   * Refresh manuel
   */
  refresh(): void {
    this.loadDrivers();
  }

  /**
   * Exports
   */
  exportToPdf(): void {
    this.driversService.exportToPdf().subscribe(blob => this.downloadFile(blob, 'pdf'));
  }

  exportToExcel(): void {
    this.driversService.exportToExcel().subscribe(blob => this.downloadFile(blob, 'xlsx'));
  }

  private downloadFile(blob: Blob, ext: string): void {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `livreurs_${new Date().getTime()}.${ext}`;
    link.click();
  }
}