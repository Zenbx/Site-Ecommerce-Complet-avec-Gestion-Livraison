// src/app/features/reports/reports.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface DeliveriesByStatus {
  delivered: number;
  inProgress: number;
  pending: number;
  failed: number;
}

export interface DeliveryReportStats {
  totalDeliveries: number;
  todayDeliveries: number;
  successRate: number;
  averageTime: number;
  deliveriesByDay?: { day: string; count: number }[];
  deliveriesByStatus: DeliveriesByStatus; // <- plus optional
}

export interface DeliveryPersonPerformance {
  id: number;
  name: string;
  totalDeliveries: number;
  completedDeliveries: number;
  failedDeliveries: number;
  successRate: number;
  rating?: number;
}

@Injectable({ providedIn: 'root' })
export class ReportsService {
  private api = `${environment.apiUrl}/reports`;

  constructor(private http: HttpClient) { }

  getDeliveriesReport(): Observable<DeliveryReportStats> {
    return this.http.get<DeliveryReportStats>(`${this.api}/deliveries`);
  }

  exportDeliveriesPdf(): Observable<Blob> {
    return this.http.get(`${this.api}/deliveries/export/pdf`, { responseType: 'blob' });
  }

  exportDeliveriesExcel(): Observable<Blob> {
    return this.http.get(`${this.api}/deliveries/export/excel`, { responseType: 'blob' });
  }

  getDeliveryPersonsReport(): Observable<DeliveryPersonPerformance[]> {
    return this.http.get<DeliveryPersonPerformance[]>(`${this.api}/delivery-persons`);
  }

  exportDeliveryPersonsPdf(): Observable<Blob> {
    return this.http.get(`${this.api}/delivery-persons/export/pdf`, { responseType: 'blob' });
  }

  exportDeliveryPersonsExcel(): Observable<Blob> {
    return this.http.get(`${this.api}/delivery-persons/export/excel`, { responseType: 'blob' });
  }
}
