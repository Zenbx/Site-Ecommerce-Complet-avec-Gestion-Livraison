import {
  Component,
  Input,
  AfterViewInit,
  OnChanges,
  SimpleChanges
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { MapService } from '../../../core/services/map.service';
import { OsrmService } from '../../../core/services/orsm.service';
import { Delivery } from '../models/delivery.model';

@Component({
  selector: 'app-delivery-map',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './delivery-map.component.html',
  styleUrls: ['./delivery-map.component.scss']
})
export class DeliveryMapComponent implements AfterViewInit, OnChanges {

  @Input() delivery: Delivery | null = null;
  @Input() driverLocation: { latitude: number; longitude: number } | null = null;
  @Input() loading = false;
  @Input() error: string | null = null;

  private mapReady = false;

  constructor(
    private mapService: MapService,
    private osrmService: OsrmService
  ) {}

  /* ==============================
     INIT CARTE (UNE SEULE FOIS)
     ============================== */
  ngAfterViewInit(): void {
    // Initialisation Leaflet
    this.mapService.initMap('map');

    // CORRECTION DU PROBLÈME DES BLOCS
    setTimeout(() => {
      this.mapService.invalidateSize();
      this.mapReady = true;

      // Si les données sont déjà là
      this.tryRender();
    }, 0);
  }

  /* ==============================
     MISE À JOUR DES DONNÉES
     ============================== */
  ngOnChanges(changes: SimpleChanges): void {
    this.tryRender();
  }

  /* ==============================
     RENDU SÉCURISÉ
     ============================== */
  private tryRender(): void {
    if (!this.mapReady || !this.delivery) {
      return;
    }

    // Cas 1 : livreur + destination
    if (
      this.driverLocation &&
      this.delivery.address?.latitude != null &&
      this.delivery.address?.longitude != null
    ) {
      this.renderRoute();
      return;
    }

    // Cas 2 : seulement destination → zoom local (3 km)
    if (
      this.delivery.address?.latitude != null &&
      this.delivery.address?.longitude != null
    ) {
      this.mapService.clear();
      this.mapService.addMarker(
        this.delivery.address.latitude,
        this.delivery.address.longitude,
        'Destination'
      );

      // Zoom local contrôlé
      this.mapService.fitAroundPoint(
        this.delivery.address.latitude,
        this.delivery.address.longitude,
        3 // km
      );
    }
  }

  /* ==============================
     ROUTE LIVREUR → DESTINATION
     ============================== */
  private renderRoute(): void {
    if (!this.delivery || !this.driverLocation) return;

    const start: [number, number] = [
      this.driverLocation.latitude,
      this.driverLocation.longitude
    ];

    const end: [number, number] = [
      this.delivery.address!.latitude!,
      this.delivery.address!.longitude!
    ];

    this.mapService.clear();

    this.mapService.addMarker(start[0], start[1], 'Livreur');
    this.mapService.addMarker(end[0], end[1], 'Destination');

    this.osrmService.getRoute(start, end).subscribe(coords => {
      this.mapService.drawRoute(coords);

      // Fit intelligent sur le trajet
      this.mapService.fitToBounds(coords);
    });
  }
}
