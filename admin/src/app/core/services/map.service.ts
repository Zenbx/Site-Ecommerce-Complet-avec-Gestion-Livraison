import { Injectable } from '@angular/core';
import * as L from 'leaflet';

@Injectable({
    providedIn: 'root'
})
export class MapService {

    private map!: L.Map;
    private markers: L.Marker[] = [];
    private route?: L.Polyline;

    initMap(containerId: string): void {
        if (this.map) return;

        this.map = L.map(containerId).setView([3.848, 11.502], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(this.map);
    }

    setView(lat: number, lng: number, zoom = 13): void {
        this.map.setView([lat, lng], zoom);
    }

    addMarker(lat: number, lng: number, label?: string): void {
        const marker = L.marker([lat, lng]);
        if (label) marker.bindPopup(label);
        marker.addTo(this.map);
        this.markers.push(marker);
    }

    drawRoute(coords: [number, number][]): void {
        this.route = L.polyline(coords, {
            color: '#3b82f6',
            weight: 4
        }).addTo(this.map);
    }

    fitToBounds(coords: [number, number][]): void {
        const bounds = L.latLngBounds(coords);
        this.map.fitBounds(bounds, { padding: [50, 50] });
    }

    invalidateSize(): void {
        this.map.invalidateSize(true);
    }

    fitAroundPoint(
        lat: number,
        lng: number,
        radiusKm = 3
    ): void {
        const latOffset = radiusKm / 111;
        const lngOffset = radiusKm / (111 * Math.cos(lat * Math.PI / 180));

        const bounds = [
            [lat - latOffset, lng - lngOffset],
            [lat + latOffset, lng + lngOffset]
        ] as L.LatLngBoundsExpression;

        this.map.fitBounds(bounds);
    }


    clear(): void {
        this.markers.forEach(m => this.map.removeLayer(m));
        this.markers = [];

        if (this.route) {
            this.map.removeLayer(this.route);
            this.route = undefined;
        }
    }
}
