import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { ApiResponse, DeliveriesReportData, DeliveryPersonStat, ReportParams } from '../models/reports.model';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ReportsService {
  private apiUrl = `${environment.apiUrl}/admin/reports`;

  constructor(private http: HttpClient) {}

  // Construit les query params
  private buildParams(params: ReportParams): HttpParams {
    let httpParams = new HttpParams().set('period', params.period);
    if (params.period === 'custom' && params.start_date && params.end_date) {
      httpParams = httpParams
        .set('start_date', params.start_date)
        .set('end_date', params.end_date);
    }
    return httpParams;
  }

  getDeliveriesReport(params: ReportParams): Observable<DeliveriesReportData> {
    return this.http.get<ApiResponse<DeliveriesReportData>>(`${this.apiUrl}/deliveries`, {
      params: this.buildParams(params)
    }).pipe(map(res => res.data));
  }

  getDeliveryPersonsReport(params: ReportParams): Observable<DeliveryPersonStat[]> {
    return this.http.get<ApiResponse<DeliveryPersonStat[]>>(`${this.apiUrl}/delivery-persons`, {
      params: this.buildParams(params)
    }).pipe(map(res => res.data));
  }

  // Gestion des exports (Blob pour téléchargement fichier)
  exportDeliveriesPdf(params: ReportParams): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/deliveries/export/pdf`, {
      params: this.buildParams(params),
      responseType: 'blob'
    });
  }

  exportDeliveriesExcel(params: ReportParams): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/deliveries/export/excel`, {
      params: this.buildParams(params),
      responseType: 'blob'
    });
  }

  exportDeliveryPersonsPdf(params: ReportParams): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/delivery-persons/export/pdf`, {
      params: this.buildParams(params),
      responseType: 'blob'
    });
  }
}