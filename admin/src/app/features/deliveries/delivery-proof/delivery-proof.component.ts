// src/app/features/deliveries/delivery-proof/delivery-proof.component.ts

import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { DeliveriesService } from '../deliveries.service';
import { Delivery, ProofType } from '../models/delivery.model';

@Component({
  selector: 'app-delivery-proof',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './delivery-proof.component.html',
  styleUrls: ['./delivery-proof.component.scss']
})
export class DeliveryProofComponent implements OnInit {
  delivery: Delivery | null = null;
  loading = true;
  validating = false;
  error = '';
  ProofType = ProofType;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private deliveriesService: DeliveriesService
  ) {}

  ngOnInit(): void {
    const deliveryId = +this.route.snapshot.params['id'];
    this.loadDelivery(deliveryId);
  }

  private loadDelivery(deliveryId: number): void {
    this.deliveriesService.getDelivery(deliveryId).subscribe({
      next: (delivery) => {
        this.delivery = delivery;
        this.loading = false;
      },
      error: (error) => {
        console.error('Error loading delivery:', error);
        this.error = 'Erreur lors du chargement de la livraison';
        this.loading = false;
      }
    });
  }

  validateProof(): void {
    if (!this.delivery) return;
    
    this.validating = true;
    this.error = '';
    
    this.deliveriesService.validateDeliveryProof(this.delivery.id).subscribe({
      next: () => {
        alert('Livraison validée avec succès !');
        this.router.navigate(['/deliveries']);
      },
      error: (error) => {
        this.error = error.message || 'Erreur lors de la validation';
        this.validating = false;
      }
    });
  }

  rejectProof(): void {
    if (!this.delivery) return;
    
    if (confirm('Êtes-vous sûr de vouloir rejeter cette preuve de livraison ?')) {
      alert('Preuve rejetée');
      this.router.navigate(['/deliveries']);
    }
  }

  getProofTypeLabel(type: ProofType): string {
    const labels = {
      [ProofType.SIGNATURE]: 'Signature',
      [ProofType.QR_CODE]: 'QR Code',
      [ProofType.PHOTO]: 'Photo'
    };
    return labels[type];
  }

  downloadProof(): void {
    if (!this.delivery?.proof) return;
    
    const link = document.createElement('a');
    link.href = this.delivery.proof.data;
    link.download = `proof_${this.delivery.orderNumber}.${
      this.delivery.proof.type === ProofType.PHOTO ? 'jpg' : 'png'
    }`;
    link.click();
  }

  formatTimestamp(timestamp: string): string {
    return new Date(timestamp).toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
}