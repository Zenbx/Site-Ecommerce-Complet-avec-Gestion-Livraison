// src/app/features/reports/reports.component.ts

import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReportsService } from './reports.service';

@Component({
  selector: 'app-reports',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './reports.component.html',
  styleUrls: ['./reports.component.scss']
})
export class ReportsComponent implements OnInit {
  stats: any = {};
  loading = false;

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
      },
      error: () => this.loading = false
    });
  }

  exportPDF(): void {
    this.reportsService.exportPdf().subscribe(blob => {
      this.download(blob, 'report.pdf');
    });
  }

  exportExcel(): void {
    this.reportsService.exportExcel().subscribe(blob => {
      this.download(blob, 'report.xlsx');
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
}
