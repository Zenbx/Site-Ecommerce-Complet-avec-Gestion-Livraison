import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ReportsService } from '../../core/services/reports.service';
import { DeliveriesReportData, DeliveryPersonStat, ReportPeriod } from '../../core/models/reports.model';

@Component({
  selector: 'app-reports',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './reports.component.html',
  styleUrls: ['./reports.component.scss']
})
export class ReportsComponent implements OnInit {
  // États
  loading = false;
  exporting = false;
  
  // Filtres
  selectedPeriod: ReportPeriod = 'today';
  customStartDate: string = '';
  customEndDate: string = '';
  
  periodOptions = [
    { value: 'today', label: "Aujourd'hui" },
    { value: 'week', label: 'Cette semaine' },
    { value: 'month', label: 'Ce mois' },
    { value: 'year', label: 'Cette année' },
    { value: 'custom', label: 'Personnalisé' }
  ];

  // Données
  stats!: DeliveriesReportData;
  deliveryPersons: DeliveryPersonStat[] = [];

  constructor(private reportsService: ReportsService) {}

  ngOnInit(): void {
    // Initialisation des dates par défaut pour le custom si besoin
    const today = new Date().toISOString().split('T')[0];
    this.customStartDate = today;
    this.customEndDate = today;
    
    this.loadData();
  }

  loadData(): void {
    this.loading = true;
    const params = {
      period: this.selectedPeriod,
      start_date: this.customStartDate,
      end_date: this.customEndDate
    };

    // Chargement parallèle (optionnel: utiliser forkJoin)
    this.reportsService.getDeliveriesReport(params).subscribe({
      next: (data) => {
        this.stats = data;
        console.log('Stats loaded:', this.stats.by_day);
        
        this.loading = false; // On arrête le loading principal quand les stats arrivent
      },
      error: (err) => {
        console.error('Erreur stats', err);
        this.loading = false;
      }
    });

    this.reportsService.getDeliveryPersonsReport(params).subscribe({
      next: (data) => {
        this.deliveryPersons = data;
        console.log(this.deliveryPersons);
        
      },
      error: (err) => console.error('Erreur livreurs', err)
    });
  }

  onPeriodChange(): void {
    if (this.selectedPeriod !== 'custom') {
      this.loadData();
    }
  }

  onCustomDateChange(): void {
    if (this.selectedPeriod === 'custom' && this.customStartDate && this.customEndDate) {
      this.loadData();
    }
  }

  // Méthodes d'export
  downloadFile(blob: Blob, filename: string): void {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    window.URL.revokeObjectURL(url);
  }

  exportReport(type: 'pdf' | 'excel'): void {
    this.exporting = true;
    const params = { period: this.selectedPeriod, start_date: this.customStartDate, end_date: this.customEndDate };
    const request = type === 'pdf' 
      ? this.reportsService.exportDeliveriesPdf(params)
      : this.reportsService.exportDeliveriesExcel(params);

    request.subscribe({
      next: (blob) => {
        const ext = type === 'pdf' ? 'pdf' : 'xlsx';
        this.downloadFile(blob, `rapport_livraisons_${this.selectedPeriod}.${ext}`);
        this.exporting = false;
      },
      error: () => this.exporting = false
    });
  }

  exportDriversPdf(): void {
    this.exporting = true;
    const params = { period: this.selectedPeriod, start_date: this.customStartDate, end_date: this.customEndDate };
    this.reportsService.exportDeliveryPersonsPdf(params).subscribe({
      next: (blob) => {
        this.downloadFile(blob, `rapport_livreurs_${this.selectedPeriod}.pdf`);
        this.exporting = false;
      },
      error: () => this.exporting = false
    });
  }

  getBarHeight(count: number): number {
    if (!this.stats?.by_day) return 0;
    const max = Math.max(...this.stats.by_day.map(d => d.total), 1); // Évite division par 0
    return (count / max) * 100;
  }

  getDriverRatingStars(driver: DeliveryPersonStat): number {
    return Math.round(driver.statistics.success_rate / 20);
  }
}