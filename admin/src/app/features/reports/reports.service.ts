// src/app/features/reports/reports.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of, delay } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ReportsService {
  private api = environment.apiUrl;
  
  // MODE TEST - Changez à false pour utiliser la vraie API
  private TEST_MODE = true;

  constructor(private http: HttpClient) {}

  getGlobalStats(): Observable<any> {
    if (this.TEST_MODE) {
      // Données mockées
      const mockStats = {
        totalDeliveries: 1234,
        deliveredToday: 156,
        inProgress: 23,
        failed: 3,
        averageDeliveryTime: 32,
        successRate: 98.5,
        totalRevenue: 45680.50,
        topDrivers: [
          { name: 'Jean Dupont', deliveries: 45, rating: 4.8 },
          { name: 'Marie Martin', deliveries: 42, rating: 4.9 },
          { name: 'Pierre Durand', deliveries: 38, rating: 4.7 }
        ],
        deliveriesByDay: [
          { day: 'Lun', count: 156 },
          { day: 'Mar', count: 178 },
          { day: 'Mer', count: 145 },
          { day: 'Jeu', count: 198 },
          { day: 'Ven', count: 234 },
          { day: 'Sam', count: 189 },
          { day: 'Dim', count: 134 }
        ],
        deliveriesByStatus: {
          delivered: 85,
          inProgress: 10,
          pending: 3,
          failed: 2
        }
      };

      // Simule un délai réseau
      return of(mockStats).pipe(delay(500));
    } else {
      // Vraie API
      return this.http.get(`${this.api}/reports/stats`);
    }
  }

  exportPdf(): Observable<Blob> {
    if (this.TEST_MODE) {
      console.log('🧪 MODE TEST - Export PDF simulé');
      // Crée un faux blob PDF
      const mockPdf = new Blob(['Mock PDF Content'], { type: 'application/pdf' });
      return of(mockPdf).pipe(delay(800));
    } else {
      return this.http.get(`${this.api}/reports/export/pdf`, { responseType: 'blob' });
    }
  }

  exportExcel(): Observable<Blob> {
    if (this.TEST_MODE) {
      console.log('🧪 MODE TEST - Export Excel simulé');
      // Crée un faux blob Excel
      const mockExcel = new Blob(['Mock Excel Content'], { 
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
      });
      return of(mockExcel).pipe(delay(800));
    } else {
      return this.http.get(`${this.api}/reports/export/excel`, { responseType: 'blob' });
    }
  }
}