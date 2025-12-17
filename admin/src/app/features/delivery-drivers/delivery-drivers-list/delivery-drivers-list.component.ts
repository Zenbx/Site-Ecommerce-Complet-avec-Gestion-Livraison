// src/app/features/delivery-drivers/delivery-drivers-list/delivery-drivers-list.component.ts
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { DeliveryDriversService } from '../../../core/services/delivery-drivers.service';
import { DeliveryDriver } from '../../../core/models/delivery.model';

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
  filterVehicleType: string = 'all';
  sortBy: 'name' | 'availability' | 'vehicle' = 'name';
  sortDirection: 'asc' | 'desc' = 'asc';

  constructor(
    private driversService: DeliveryDriversService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadDrivers();
  }

  /**
   * Charge la liste des livreurs
   */
  loadDrivers(): void {
    this.loading = true;
    const filters: any = {};

    if (this.searchTerm) {
      filters.search = this.searchTerm;
    }

    if (this.filterAvailability !== 'all') {
      filters.is_available = this.filterAvailability === 'available';
    }

    if (this.filterVehicleType !== 'all') {
      filters.vehicleType = this.filterVehicleType;
    }

    this.driversService.getDrivers(this.currentPage, this.perPage, filters)
      .subscribe({
        next: (response) => {
          // Adapt API shape: API may return drivers with 'name', 'email', 'is_available'
          this.drivers = this.normalizeDrivers(response.data);
          this.drivers = this.sortDrivers(this.drivers);
          this.totalDrivers = response.total;
          this.loading = false;
        },
        error: (error) => {
          console.error('❌ Error loading drivers:', error);
          this.loading = false;
        }
      });
  }

  /**
   * Tri des livreurs
   */
  private sortDrivers(drivers: DeliveryDriver[]): DeliveryDriver[] {
    return drivers.sort((a, b) => {
      let comparison = 0;

      switch (this.sortBy) {
        case 'name':
          comparison = this.getDriverDisplayName(a).localeCompare(this.getDriverDisplayName(b));
          break;
        case 'availability':
          comparison = (a.isAvailable === b.isAvailable) ? 0 : a.isAvailable ? -1 : 1;
          break;
      }

      return this.sortDirection === 'asc' ? comparison : -comparison;
    });
  }

  /**
   * Normalize drivers coming from API (supporting both legacy and new API shape)
   */
  private normalizeDrivers(payload: any[]): DeliveryDriver[] {
    return payload.map(p => {
      // If API already sends fields matching DeliveryDriver interface, keep them.
      if ((p as any).firstName || (p as any).lastName) {
        return p as DeliveryDriver;
      }

      // New API shape: { id, name, email, is_available, statistics }
      const name = (p as any).name || '';
      const [firstName, ...rest] = name.split(' ');
      const lastName = rest.join(' ') || '';

      const driver: any = {
        id: p.id,
        name: p.name,
        phone: (p as any).phone || '',
        isAvailable: (p as any).is_available ?? (p as any).isAvailable ?? false,
        currentLocation: (p as any).currentLocation,
        email: (p as any).email
      } as DeliveryDriver & { email?: string };

      return driver;
    });
  }

  getDriverDisplayName(d: DeliveryDriver): string {
    // Prefer name if present on payload
    const name = (d as any).name as string | undefined;
    if (name) return name;
    return `${d.name || ''}`.trim() || 'N/A';
  }

  /** Initiales pour avatar */
  getDriverInitials(d: DeliveryDriver): string {
    const name = (d as any).name as string | undefined;
    if (name) {
      return name
        .split(' ')
        .map(n => n.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase();
    }
    return (d.name?.charAt(0) || '').toUpperCase();
  }

  /** Email si présent */
  getDriverEmail(d: DeliveryDriver): string {
    return (d as any).email ?? '';
  }

  /** Disponibilité unifiée (supporte is_available ou isAvailable) */
  isDriverAvailable(d: DeliveryDriver): boolean {
    if ((d as any).is_available !== undefined) return !!(d as any).is_available;
    return !!d.isAvailable;
  }

  /** Libellé de disponibilité */
  getDriverAvailabilityLabel(d: DeliveryDriver): string {
    return this.isDriverAvailable(d) ? 'Disponible' : 'Indisponible';
  }

  /**
   * Change le tri
   */
  changeSortBy(field: 'name' | 'availability' | 'vehicle'): void {
    if (this.sortBy === field) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortBy = field;
      this.sortDirection = 'asc';
    }
    this.drivers = this.sortDrivers(this.drivers);
  }

  /**
   * Recherche
   */// Dans delivery-drivers-list.component.ts
onSearch(event: any): void {
  this.searchTerm = event.target.value;
  this.currentPage = 1; // Reset à la première page
  this.loadDrivers();   // Recharge avec le filtre
}



  /**
   * Changement de filtre
   */
  onFilterChange(): void {
    this.currentPage = 1;
    this.loadDrivers();
  }

  /**
   * Changement de page
   */
  onPageChange(page: number): void {
    this.currentPage = page;
    this.loadDrivers();
  }

  /**
   * Créer un nouveau livreur
   */
  createDriver(): void {
    this.router.navigate(['/drivers/new']);
  }

  /**
   * Éditer un livreur
   */
  editDriver(driver: DeliveryDriver): void {
    this.router.navigate(['/drivers/edit', driver.id]);
  }

  /**
   * Toggle la disponibilité d'un livreur
   */
  toggleAvailability(driver: DeliveryDriver): void {
    this.driversService.toggleAvailability(driver.id, !driver.isAvailable)
      .subscribe({
        next: (updatedDriver) => {
          driver.isAvailable = updatedDriver.isAvailable;
          console.log(`✅ Disponibilité mise à jour pour ${driver.name}`);
        },
        error: (error) => {
          console.error('❌ Error toggling availability:', error);
          alert('Erreur lors de la modification de la disponibilité');
        }
      });
  }

  /**
   * Supprimer un livreur
   */
  deleteDriver(driver: DeliveryDriver): void {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${driver.name} ?`)) {
      this.driversService.deleteDriver(driver.id)
        .subscribe({
          next: () => {
            console.log(`✅ Livreur supprimé: ${driver.name}`);
            this.loadDrivers();
          },
          error: (error) => {
            console.error('❌ Error deleting driver:', error);
            alert('Erreur lors de la suppression du livreur');
          }
        });
    }
  }

  /**
   * Voir les statistiques d'un livreur
   */
  viewStatistics(driver: DeliveryDriver): void {
    this.router.navigate(['/drivers', driver.id, 'statistics']);
  }

  /**
   * 📊 Export CSV
   */
  exportToPdf(): void {
    console.log('📥 Export PDF en cours...');
    this.driversService.exportToPdf().subscribe({
      next: (blob) => this.downloadBlob(blob, `livreurs_${new Date().toISOString().split('T')[0]}.pdf`),
      error: (error) => { console.error('❌ Error exporting PDF:', error); alert('Erreur lors de l\'export PDF'); }
    });
  }

  exportToExcel(): void {
    console.log('📥 Export Excel en cours...');
    this.driversService.exportToExcel().subscribe({
      next: (blob) => this.downloadBlob(blob, `livreurs_${new Date().toISOString().split('T')[0]}.xlsx`),
      error: (error) => { console.error('❌ Error exporting Excel:', error); alert('Erreur lors de l\'export Excel'); }
    });
  }

  private downloadBlob(blob: Blob, filename: string) {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    window.URL.revokeObjectURL(url);
    console.log('✅ Export réussi', filename);
  }

  /**
   * 🔄 Rafraîchir la liste
   */
  refresh(): void {
    console.log('🔄 Rafraîchissement...');
    this.loadDrivers();
  }

  /**
   * Réinitialiser les filtres
   */
  resetFilters(): void {
    this.searchTerm = '';
    this.filterAvailability = 'all';
    this.filterVehicleType = 'all';
    this.currentPage = 1;
    this.loadDrivers();
  }

  /**
   * Nombre total de pages
   */
  getTotalPages(): number {
    return Math.ceil(this.totalDrivers / this.perPage);
  }

  /**
   * Pages à afficher dans la pagination
   */
  getPageNumbers(): number[] {
    const total = this.getTotalPages();
    const current = this.currentPage;
    const pages: number[] = [];

    if (total <= 7) {
      for (let i = 1; i <= total; i++) {
        pages.push(i);
      }
    } else {
      if (current <= 3) {
        pages.push(1, 2, 3, 4, -1, total);
      } else if (current >= total - 2) {
        pages.push(1, -1, total - 3, total - 2, total - 1, total);
      } else {
        pages.push(1, -1, current - 1, current, current + 1, -1, total);
      }
    }

    return pages;
  }

  /**
   * Obtenir le statut du livreur
   */
  getDriverStatus(driver: DeliveryDriver): string {
    if (!driver.isAvailable) {
      return 'Indisponible';
    }
    // Vous pouvez ajouter plus de logique ici
    // Par exemple: en livraison, en pause, etc.
    return 'Disponible';
  }

  /**
   * Icône de tri
   */
  getSortIcon(field: 'name' | 'availability' | 'vehicle'): string {
    if (this.sortBy !== field) return '↕️';
    return this.sortDirection === 'asc' ? '↑' : '↓';
  }

  get availableDriversCount(): number {
    return this.drivers.filter(d => d.isAvailable).length;
  }

  get hasAvailableDrivers(): boolean {
    return this.availableDriversCount > 0;
  }
}