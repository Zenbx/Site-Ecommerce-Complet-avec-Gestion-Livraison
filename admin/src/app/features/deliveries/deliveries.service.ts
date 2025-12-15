// src/app/features/deliveries/deliveries.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, of, delay, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { 
  Delivery, 
  DeliveryAssignmentRequest,
  DeliveryStatus,
  DeliveryDriver
} from './models/delivery.model';

export interface DeliveriesListResponse {
  data: Delivery[];
  total: number;
  page: number;
  perPage: number;
}

export interface DeliveryFilters {
  status?: DeliveryStatus;
  driverId?: number;
  dateFrom?: string;
  dateTo?: string;
  search?: string;
}

export interface AutoAssignmentResult {
  delivery: Delivery;
  driver: DeliveryDriver;
  reason: string;
  score: number;
}

@Injectable({
  providedIn: 'root'
})
export class DeliveriesService {
  private apiUrl = `${environment.apiUrl}/deliveries`;
  
  // MODE TEST
  private TEST_MODE = true;

  constructor(private http: HttpClient) {}

  getDeliveries(
    page: number = 1, 
    perPage: number = 10, 
    filters?: DeliveryFilters
  ): Observable<DeliveriesListResponse> {
    if (this.TEST_MODE) {
      return this.getMockDeliveries(page, perPage, filters);
    }

    let params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', perPage.toString());

    if (filters) {
      Object.keys(filters).forEach(key => {
        const value = (filters as any)[key];
        if (value !== undefined && value !== null && value !== '') {
          params = params.set(key, value.toString());
        }
      });
    }

    return this.http.get<DeliveriesListResponse>(this.apiUrl, { params });
  }

  getDelivery(id: number): Observable<Delivery> {
    if (this.TEST_MODE) {
      const mockDelivery = this.createMockDelivery(id);
      return of(mockDelivery).pipe(delay(300));
    }

    return this.http.get<Delivery>(`${this.apiUrl}/${id}`);
  }

  getPendingDeliveries(): Observable<Delivery[]> {
    if (this.TEST_MODE) {
      const mockDeliveries = this.createMockDeliveriesList()
        .filter(d => d.status === DeliveryStatus.PENDING);
      return of(mockDeliveries).pipe(delay(200));
    }

    return this.http.get<Delivery[]>(`${this.apiUrl}/pending`);
  }

  /**
   * ASSIGNATION MANUELLE
   * Assigne une livraison à un livreur spécifique
   */
  assignDeliveryManually(deliveryId: number, driverId: number): Observable<Delivery> {
    if (this.TEST_MODE) {
      console.log(`🎯 Assignation manuelle - Livraison #${deliveryId} → Livreur #${driverId}`);
      
      const delivery = this.createMockDelivery(deliveryId);
      delivery.status = DeliveryStatus.ASSIGNED;
      delivery.driver = this.getMockDriver(driverId);
      delivery.assignedAt = new Date().toISOString();
      
      // Simuler la notification push
      this.notifyDriverPush(driverId, deliveryId, 'manual').subscribe();
      
      return of(delivery).pipe(delay(500));
    }

    const assignment: DeliveryAssignmentRequest = { deliveryId, driverId };
    return this.http.post<Delivery>(`${this.apiUrl}/assign`, assignment).pipe(
      tap(delivery => {
        // Notifier l'app mobile après assignation
        this.notifyDriverPush(driverId, deliveryId, 'manual').subscribe();
      })
    );
  }

  /**
   * ASSIGNATION AUTOMATIQUE
   * Trouve et assigne automatiquement le meilleur livreur disponible
   */
  assignDeliveryAutomatically(deliveryId: number): Observable<AutoAssignmentResult> {
    if (this.TEST_MODE) {
      console.log(`🤖 Assignation automatique - Livraison #${deliveryId}`);
      
      return this.findBestDriverMock(deliveryId).pipe(
        delay(800),
        tap(result => {
          console.log(`✅ Meilleur livreur: ${result.driver.firstName} ${result.driver.lastName}`);
          console.log(`📊 Score: ${result.score} - Raison: ${result.reason}`);
          
          // Notifier l'app mobile
          this.notifyDriverPush(result.driver.id, deliveryId, 'auto').subscribe();
        })
      );
    }

    return this.http.post<AutoAssignmentResult>(
      `${this.apiUrl}/${deliveryId}/auto-assign`, 
      {}
    ).pipe(
      tap(result => {
        this.notifyDriverPush(result.driver.id, deliveryId, 'auto').subscribe();
      })
    );
  }

  /**
   * DÉSASSIGNER UNE LIVRAISON
   * Retire l'assignation d'une livraison
   */
  unassignDelivery(deliveryId: number): Observable<Delivery> {
    if (this.TEST_MODE) {
      console.log(`❌ Désassignation - Livraison #${deliveryId}`);
      
      const delivery = this.createMockDelivery(deliveryId);
      delivery.status = DeliveryStatus.PENDING;
      delivery.driver = undefined;
      delivery.assignedAt = undefined;
      
      return of(delivery).pipe(delay(300));
    }

    return this.http.post<Delivery>(`${this.apiUrl}/${deliveryId}/unassign`, {});
  }

  /**
   * NOTIFICATION PUSH VERS L'APP MOBILE
   * Envoie une notification au livreur sur son app React Native
   */
  private notifyDriverPush(
    driverId: number, 
    deliveryId: number, 
    assignmentType: 'manual' | 'auto'
  ): Observable<any> {
    if (this.TEST_MODE) {
      console.log(`📱 Notification push envoyée au livreur #${driverId}`);
      console.log(`   Type: ${assignmentType === 'manual' ? 'Manuelle' : 'Automatique'}`);
      console.log(`   Livraison: #${deliveryId}`);
      
      return of({ 
        success: true, 
        message: 'Notification envoyée',
        timestamp: new Date().toISOString()
      }).pipe(delay(200));
    }

    // En production, appeler votre service de notifications push (Firebase, OneSignal, etc.)
    return this.http.post(`${environment.apiUrl}/notifications/driver`, {
      driverId,
      deliveryId,
      type: 'NEW_DELIVERY_ASSIGNED',
      title: assignmentType === 'manual' 
        ? 'Nouvelle livraison assignée' 
        : 'Livraison automatiquement assignée',
      body: `Livraison #${deliveryId} vous a été attribuée`,
      data: { deliveryId, assignmentType }
    });
  }

  /**
   * ALGORITHME D'ASSIGNATION AUTOMATIQUE (Mock)
   * Sélectionne le meilleur livreur basé sur plusieurs critères
   */
  private findBestDriverMock(deliveryId: number): Observable<AutoAssignmentResult> {
    const delivery = this.createMockDelivery(deliveryId);
    const availableDrivers = this.getAvailableDriversMock();

    // Calcul du score pour chaque livreur
    const scoredDrivers = availableDrivers.map(driver => {
      let score = 100;
      let reasons: string[] = [];

      // Critère 1: Proximité (simulée)
      const distance = Math.random() * 10; // 0-10 km
      const proximityScore = Math.max(0, 30 - distance * 3);
      score += proximityScore;
      if (distance < 2) reasons.push('Très proche');

      // Critère 2: Nombre de livraisons en cours (simulé)
      const currentDeliveries = Math.floor(Math.random() * 4);
      const workloadScore = Math.max(0, 20 - currentDeliveries * 5);
      score += workloadScore;
      if (currentDeliveries === 0) reasons.push('Disponible immédiatement');

      // Critère 3: Taux de réussite (simulé)
      const successRate = 90 + Math.random() * 10; // 90-100%
      const successScore = (successRate - 90) * 2;
      score += successScore;
      if (successRate > 98) reasons.push('Excellent historique');

      // Critère 4: Temps moyen de livraison (simulé)
      const avgTime = 20 + Math.random() * 20; // 20-40 min
      const speedScore = Math.max(0, 20 - (avgTime - 20));
      score += speedScore;
      if (avgTime < 25) reasons.push('Très rapide');

      return {
        driver,
        score,
        reason: reasons.length > 0 ? reasons.join(', ') : 'Bon choix général',
        distance,
        currentDeliveries
      };
    });

    // Trier par score décroissant
    scoredDrivers.sort((a, b) => b.score - a.score);
    const best = scoredDrivers[0];

    // Créer le résultat
    const result: AutoAssignmentResult = {
      delivery: {
        ...delivery,
        status: DeliveryStatus.ASSIGNED,
        driver: best.driver,
        assignedAt: new Date().toISOString()
      },
      driver: best.driver,
      reason: best.reason,
      score: Math.round(best.score)
    };

    return of(result);
  }

  // Méthodes existantes...

  updateDeliveryStatus(id: number, status: DeliveryStatus): Observable<Delivery> {
    if (this.TEST_MODE) {
      const delivery = this.createMockDelivery(id);
      delivery.status = status;
      return of(delivery).pipe(delay(300));
    }

    return this.http.patch<Delivery>(`${this.apiUrl}/${id}/status`, { status });
  }

  cancelDelivery(id: number, reason: string): Observable<Delivery> {
    if (this.TEST_MODE) {
      const delivery = this.createMockDelivery(id);
      delivery.status = DeliveryStatus.CANCELLED;
      return of(delivery).pipe(delay(300));
    }

    return this.http.post<Delivery>(`${this.apiUrl}/${id}/cancel`, { reason });
  }

  validateDeliveryProof(id: number): Observable<Delivery> {
    if (this.TEST_MODE) {
      const delivery = this.createMockDelivery(id);
      delivery.status = DeliveryStatus.DELIVERED;
      return of(delivery).pipe(delay(500));
    }

    return this.http.post<Delivery>(`${this.apiUrl}/${id}/validate-proof`, {});
  }

  getDeliveryRoute(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/${id}/route`);
  }

  getActiveDeliveriesMap(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/active-map`);
  }

  // ========== MÉTHODES MOCK ==========

  private getMockDeliveries(page: number, perPage: number, filters?: DeliveryFilters): Observable<DeliveriesListResponse> {
    let deliveries = this.createMockDeliveriesList();

    // Appliquer les filtres
    if (filters?.status) {
      deliveries = deliveries.filter(d => d.status === filters.status);
    }
    if (filters?.search) {
      const search = filters.search.toLowerCase();
      deliveries = deliveries.filter(d => 
        d.orderNumber.toLowerCase().includes(search) ||
        d.customer.name.toLowerCase().includes(search)
      );
    }

    const total = deliveries.length;
    const start = (page - 1) * perPage;
    const paginatedDeliveries = deliveries.slice(start, start + perPage);

    return of({
      data: paginatedDeliveries,
      total,
      page,
      perPage
    }).pipe(delay(400));
  }

  private createMockDeliveriesList(): Delivery[] {
    const statuses = [
      DeliveryStatus.PENDING,
      DeliveryStatus.PENDING,
      DeliveryStatus.PENDING,
      DeliveryStatus.ASSIGNED,
      DeliveryStatus.ASSIGNED,
      DeliveryStatus.IN_PROGRESS,
      DeliveryStatus.IN_PROGRESS,
      DeliveryStatus.DELIVERED,
      DeliveryStatus.DELIVERED,
      DeliveryStatus.FAILED
    ];

    return statuses.map((status, i) => this.createMockDelivery(i + 1, status));
  }

  private createMockDelivery(id: number, status?: DeliveryStatus): Delivery {
    const mockStatus = status || DeliveryStatus.PENDING;
    const hasDriver = [DeliveryStatus.ASSIGNED, DeliveryStatus.IN_PROGRESS, DeliveryStatus.DELIVERED].includes(mockStatus);

    return {
      id,
      orderNumber: `CMD-2024-${String(id).padStart(4, '0')}`,
      customer: {
        name: ['Jean Dupont', 'Marie Martin', 'Paul Moreau', 'Sophie Laurent'][id % 4],
        phone: `+237 6${Math.floor(Math.random() * 90000000 + 10000000)}`,
        email: `client${id}@example.com`
      },
      address: {
        street: `${id} Rue de la Paix`,
        city: ['Douala', 'Yaoundé', 'Bafoussam', 'Garoua'][id % 4],
        postalCode: `${10000 + id}`,
        country: 'Cameroun',
        latitude: 4.0511 + (Math.random() - 0.5) * 0.1,
        longitude: 9.7679 + (Math.random() - 0.5) * 0.1,
        instructions: 'Sonner 2 fois'
      },
      status: mockStatus,
      driver: hasDriver ? this.getMockDriver(id % 3 + 1) : undefined,
      assignedAt: hasDriver ? new Date(Date.now() - 3600000).toISOString() : undefined,
      pickedUpAt: mockStatus === DeliveryStatus.IN_PROGRESS ? new Date(Date.now() - 1800000).toISOString() : undefined,
      deliveredAt: mockStatus === DeliveryStatus.DELIVERED ? new Date(Date.now() - 900000).toISOString() : undefined,
      priority: ['low', 'medium', 'high'][id % 3] as any,
      createdAt: new Date(Date.now() - 7200000).toISOString(),
      updatedAt: new Date().toISOString()
    };
  }

  private getMockDriver(id: number): DeliveryDriver {
    const drivers = [
      { firstName: 'Pierre', lastName: 'Martin' },
      { firstName: 'Sophie', lastName: 'Laurent' },
      { firstName: 'Luc', lastName: 'Dubois' },
      { firstName: 'Antoine', lastName: 'Petit' },
      { firstName: 'Thomas', lastName: 'Blanc' }
    ];

    const driver = drivers[id % drivers.length];

    return {
      id,
      firstName: driver.firstName,
      lastName: driver.lastName,
      phone: `+237 6${Math.floor(Math.random() * 90000000 + 10000000)}`,
      vehicleType: ['Moto', 'Voiture', 'Camionnette'][id % 3],
      vehiclePlate: `ABC-${id}${id}${id}`,
      isAvailable: true,
      currentLocation: {
        latitude: 4.0511 + (Math.random() - 0.5) * 0.05,
        longitude: 9.7679 + (Math.random() - 0.5) * 0.05,
        updatedAt: new Date().toISOString()
      }
    };
  }

  private getAvailableDriversMock(): DeliveryDriver[] {
    return [1, 2, 3, 4, 5].map(id => this.getMockDriver(id));
  }
}