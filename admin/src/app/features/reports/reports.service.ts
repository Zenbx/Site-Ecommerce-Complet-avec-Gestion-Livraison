// src/app/features/reports/reports.service.ts

import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ReportsService {

  private api = environment.apiUrl;

  constructor(private http: HttpClient) {}

  getGlobalStats() {
    return this.http.get(`${this.api}/reports/stats`);
  }

  exportPdf() {
    return this.http.get(`${this.api}/reports/export/pdf`, { responseType: 'blob' });
  }

  exportExcel() {
    return this.http.get(`${this.api}/reports/export/excel`, { responseType: 'blob' });
  }
}
