import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';

import {
  ReportsService,
  DeliveriesByStatus,
  DeliveryReportStats,
  DeliveryPersonPerformance
} from '../../core/services/reports.service';

@Component({
  selector: 'app-reports',
  templateUrl: './reports.component.html',
  styleUrls: ['./reports.component.scss'],
  standalone: true,
  imports: [CommonModule],
})
export class ReportsComponent implements OnInit {
  stats: DeliveryReportStats | null = null;
  deliveryPersons: DeliveryPersonPerformance[] = [];
  loading = false;

  exportingPdf = false;
  exportingExcel = false;

  // Classes CSS pour les statuts
  statusClasses: Record<keyof DeliveriesByStatus, string> = {
    delivered: 'success',
    inProgress: 'primary',
    pending: 'warning',
    failed: 'error',
  };

  statusLabels: Record<keyof DeliveriesByStatus, string> = {
    delivered: 'Livrées',
    inProgress: 'En cours',
    pending: 'En attente',
    failed: 'Échecs',
  };

  statusKeys: Array<keyof DeliveriesByStatus> = ['delivered', 'inProgress', 'pending', 'failed'];


  constructor(private reportsService: ReportsService) { }

  ngOnInit(): void {
    this.loadStats();
    this.loadDeliveryPersons();
  }

  private loadStats(): void {
    this.loading = true;
    this.reportsService.getDeliveriesReport().subscribe({
      next: data => {
        this.stats = data;
        this.loading = false;
      },
      error: err => {
        console.error(err);
        this.loading = false;
      },
    });
  }

  private loadDeliveryPersons(): void {
    this.reportsService.getDeliveryPersonsReport().subscribe({
      next: data => (this.deliveryPersons = data),
      error: err => console.error(err),
    });
  }

  formatTime(minutes?: number): string {
    if (!minutes) return '0 min';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${h > 0 ? h + 'h ' : ''}${m} min`;
  }

  exportPDF(): void {
    this.exportingPdf = true;
    if (this.stats) {
      this.reportsService.exportDeliveriesPdf().subscribe({
        next: blob => this.downloadBlob(blob, 'deliveries-report.pdf'),
        complete: () => (this.exportingPdf = false),
      });
    } else {
      this.reportsService.exportDeliveryPersonsPdf().subscribe({
        next: blob => this.downloadBlob(blob, 'delivery-persons-report.pdf'),
        complete: () => (this.exportingPdf = false),
      });
    }
  }

  exportExcel(): void {
    this.exportingExcel = true;
    if (this.stats) {
      this.reportsService.exportDeliveriesExcel().subscribe({
        next: blob => this.downloadBlob(blob, 'deliveries-report.xlsx'),
        complete: () => (this.exportingExcel = false),
      });
    } else {
      this.reportsService.exportDeliveryPersonsExcel().subscribe({
        next: blob => this.downloadBlob(blob, 'delivery-persons-report.xlsx'),
        complete: () => (this.exportingExcel = false),
      });
    }
  }

  private downloadBlob(blob: Blob, filename: string) {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
  }
}
