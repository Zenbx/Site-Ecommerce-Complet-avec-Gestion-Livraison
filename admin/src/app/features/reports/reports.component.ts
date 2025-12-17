// src/app/features/reports/reports.component.ts
import { Component, OnInit, ViewEncapsulation } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReportsService } from './reports.service';

@Component({
  selector: 'app-reports',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './reports.component.html',
  styleUrls: ['./reports.component.scss'],
  encapsulation: ViewEncapsulation.None  // ← Désactive l'encapsulation pour tester
})
export class ReportsComponent implements OnInit {
  stats: any = {};
  loading = false;
  exportingPdf = false;
  exportingExcel = false;

  constructor(private reportsService: ReportsService) {}

  ngOnInit(): void {
    this.loadStatistics();
  }

  loadStatistics(): void {
    this.loading = true;
    this.reportsService.getGlobalStats().subscribe({
      next: (data) => {
        this.stats = data;
        this.loading = false;
        console.log('📊 Stats chargées:', data);
      },
      error: (err) => {
        console.error('❌ Erreur chargement stats:', err);
        this.loading = false;
      }
    });
  }

  // À ajouter dans la classe ReportsComponent
formatTime(minutes: number): string {
  if (minutes === undefined || minutes === null) return '0 min';
  const h = Math.floor(minutes / 60);
  const m = Math.floor(minutes % 60);
  if (h === 0) return `${m} min`;
  return `${h}h ${m}min`;
}

  exportPDF(): void {
    this.exportingPdf = true;
    this.reportsService.exportPdf().subscribe({
      next: (blob) => {
        this.download(blob, 'rapport.pdf');
        this.exportingPdf = false;
        console.log('✅ PDF exporté');
      },
      error: (err) => {
        console.error('❌ Erreur export PDF:', err);
        this.exportingPdf = false;
      }
    });
  }

  exportExcel(): void {
    this.exportingExcel = true;
    this.reportsService.exportExcel().subscribe({
      next: (blob) => {
        this.download(blob, 'rapport.xlsx');
        this.exportingExcel = false;
        console.log('✅ Excel exporté');
      },
      error: (err) => {
        console.error('❌ Erreur export Excel:', err);
        this.exportingExcel = false;
      }
    });
  }

  private download(blob: Blob, filename: string): void {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
  }

  getSuccessRateColor(): string {
    if (!this.stats.successRate) return '#95a5a6';
    if (this.stats.successRate >= 95) return '#27ae60';
    if (this.stats.successRate >= 85) return '#f39c12';
    return '#e74c3c';
  }
}