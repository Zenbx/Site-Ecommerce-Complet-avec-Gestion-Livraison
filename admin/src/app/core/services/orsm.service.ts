import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map } from 'rxjs/operators';

@Injectable({ providedIn: 'root' })
export class OsrmService {

  private baseUrl = 'https://router.project-osrm.org';

  constructor(private http: HttpClient) {}

  getRoute(
    start: [number, number],
    end: [number, number]
  ) {
    const url = `${this.baseUrl}/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=full&geometries=geojson`;

    return this.http.get<any>(url).pipe(
      map(res =>
        res.routes[0].geometry.coordinates.map(
          (c: number[]) => [c[1], c[0]]
        )
      )
    );
  }
}
