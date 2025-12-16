// src/app/features/deliveries/delivery-proof/delivery-proof.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';

import { DeliveriesService } from '../deliveries.service';
import { Delivery, DeliveryProof, ProofStatus, ProofType } from '../models/delivery.model';

@Component({
  selector: 'app-delivery-proof',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './delivery-proof.component.html',
  styleUrls: ['./delivery-proof.component.scss']
})
export class DeliveryProofComponent implements OnInit, OnDestroy {

  delivery!: Delivery;
  proofs: DeliveryProof[] = [];

  loading = true;
  error: string | null = null;

  private destroy$ = new Subject<void>();

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private deliveriesService: DeliveriesService
  ) {}

  ngOnInit(): void {
    const deliveryId = Number(this.route.snapshot.paramMap.get('id'));
    this.loadDelivery(deliveryId);
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  // ===================== DATA =====================

  private loadDelivery(id: number): void {
    this.loading = true;

    this.deliveriesService.getDelivery(id)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: delivery => {
          this.delivery = delivery;
          this.loadProofs(id);
        },
        error: () => {
          this.error = 'Impossible de charger la livraison';
          this.loading = false;
        }
      });
  }

  private loadProofs(deliveryId: number): void {
    this.deliveriesService.getDeliveryProofs(deliveryId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: proofs => {
          this.proofs = proofs;
          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger les preuves';
          this.loading = false;
        }
      });
  }

  // ===================== ACTIONS =====================
  getProofLabel(type: ProofType): string {
  switch (type) {
    case ProofType.SIGNATURE: return '✍️ Signature';
    case ProofType.QR_CODE:   return '📱 QR Code';
    case ProofType.PHOTO:     return '📸 Photo';
    default: return '—';
  }
}

  validateProof(proof: DeliveryProof): void {
    this.deliveriesService.validateProof(proof.id)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          proof.status = ProofStatus.VALIDATED;
          proof.validatedAt = new Date().toISOString();
        },
        error: () => {
          this.error = 'Erreur lors de la validation';
        }
      });
  }

  rejectProof(proof: DeliveryProof): void {
    const reason = prompt('Motif du rejet ?');
    if (!reason) return;

    this.deliveriesService.rejectProof(proof.id, reason)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          proof.status = ProofStatus.REJECTED;
          proof.rejectionReason = reason;
        },
        error: () => {
          this.error = 'Erreur lors du rejet';
        }
      });
  }

  goBack(): void {
    this.router.navigate(['/deliveries', this.delivery.id]);
  }
}
