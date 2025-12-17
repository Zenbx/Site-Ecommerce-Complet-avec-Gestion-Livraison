// src/app/features/deliveries/delivery-proof/delivery-proof.component.ts

import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { trigger, transition, style, animate } from '@angular/animations';

/**
 * Interface représentant une preuve de livraison
 * 
 * Une interface TypeScript définit la structure d'un objet. Ici, nous décrivons
 * exactement à quoi ressemble une preuve de livraison avec tous ses champs.
 * Cela nous donne l'autocomplétion dans l'éditeur et la vérification de types.
 */
interface DeliveryProof {
  id: number;
  deliveryId: number;
  type: 'SIGNATURE' | 'PHOTO' | 'QR_CODE';
  url: string;
  status: 'PENDING' | 'VALIDATED' | 'REJECTED';
  recipientName?: string;
  driver?: {
    id: number;
    firstName: string;
    lastName: string;
  };
  createdAt: string;
  validatedAt?: string;
  rejectionReason?: string;
}

/**
 * Interface représentant une livraison (version simplifiée)
 */
interface Delivery {
  id: number;
  orderNumber: string;
  customer: {
    name: string;
  };
}

@Component({
  selector: 'app-delivery-proof',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './delivery-proof.component.html',
  styleUrls: ['./delivery-proof.component.scss'],
  animations: [
    // Animation pour l'apparition de la modal
    trigger('fadeIn', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('200ms ease-in', style({ opacity: 1 }))
      ]),
      transition(':leave', [
        animate('200ms ease-out', style({ opacity: 0 }))
      ])
    ])
  ]
})
export class DeliveryProofComponent implements OnInit {
  /**
   * PROPRIÉTÉS D'ÉTAT DU COMPOSANT
   * 
   * Ces propriétés stockent l'état actuel du composant. Elles sont toutes
   * initialisées avec des valeurs par défaut pour éviter les erreurs.
   */
  
  // Indique si les données sont en cours de chargement depuis l'API
  loading = false;
  
  // Stocke un éventuel message d'erreur à afficher à l'utilisateur
  error: string | null = null;
  
  // Indique si une opération de validation/rejet est en cours
  processing = false;
  
  // Tableau contenant toutes les preuves de livraison à afficher
  proofs: DeliveryProof[] = [];
  
  // Informations sur la livraison associée aux preuves
  delivery: Delivery | null = null;
  
  /**
   * PROPRIÉTÉS POUR LA MODAL D'IMAGE EN PLEIN ÉCRAN
   * 
   * Ces propriétés contrôlent l'affichage de l'image en plein écran.
   * C'est ici qu'était le problème - selectedProof était utilisée dans le HTML
   * mais n'était pas déclarée dans le TypeScript.
   */
  
  // Indique si la modal d'image en plein écran est visible
  showFullImage = false;
  
  // Stocke la preuve actuellement sélectionnée pour affichage en plein écran
  // Le type "| null" signifie qu'elle peut être soit une DeliveryProof soit null
  selectedProof: DeliveryProof | null = null;

  /**
   * CONSTRUCTEUR
   * 
   * Angular injecte automatiquement les dépendances nécessaires.
   * ActivatedRoute nous permet d'accéder aux paramètres de l'URL.
   * Router nous permet de naviguer vers d'autres pages.
   */
  constructor(
    private route: ActivatedRoute,
    private router: Router
    // Vous ajouterez ici votre service de livraison quand il sera créé
    // private deliveryService: DeliveryService
  ) {}

  /**
   * MÉTHODE DE CYCLE DE VIE ANGULAR
   * 
   * ngOnInit est appelée automatiquement par Angular après la création du composant.
   * C'est l'endroit idéal pour charger les données initiales.
   */
  ngOnInit(): void {
    // Récupérer l'ID de la livraison depuis l'URL
    const deliveryId = this.route.snapshot.params['id'];
    
    if (deliveryId) {
      this.loadProofs(deliveryId);
    } else {
      this.error = 'ID de livraison manquant';
    }
  }

  /**
   * Charger les preuves de livraison depuis l'API
   * 
   * Cette méthode fait l'appel API pour récupérer toutes les preuves
   * associées à une livraison spécifique. En production, vous remplacerez
   * les données mockées par un vrai appel à votre service.
   * 
   * @param deliveryId - L'identifiant de la livraison
   */
  loadProofs(deliveryId: number): void {
    this.loading = true;
    this.error = null;

    // SIMULATION D'APPEL API
    // En production, remplacez ceci par un vrai appel à votre service :
    // this.deliveryService.getProofs(deliveryId).subscribe({
    //   next: (response) => {
    //     this.proofs = response.data.proofs;
    //     this.delivery = response.data.delivery;
    //     this.loading = false;
    //   },
    //   error: (err) => {
    //     this.error = 'Erreur lors du chargement des preuves';
    //     this.loading = false;
    //   }
    // });

    // Simulation avec setTimeout pour imiter le délai réseau
    setTimeout(() => {
      // Données mockées pour le développement
      this.delivery = {
        id: deliveryId,
        orderNumber: 'ORD-2024-001',
        customer: {
          name: 'Jean Dupont'
        }
      };

      this.proofs = [
        {
          id: 1,
          deliveryId: deliveryId,
          type: 'SIGNATURE',
          url: 'https://via.placeholder.com/400x300/4285f4/ffffff?text=Signature',
          status: 'PENDING',
          recipientName: 'Jean Dupont',
          driver: {
            id: 1,
            firstName: 'Pierre',
            lastName: 'Livreur'
          },
          createdAt: new Date().toISOString()
        },
        {
          id: 2,
          deliveryId: deliveryId,
          type: 'PHOTO',
          url: 'https://via.placeholder.com/400x300/34a853/ffffff?text=Photo+Colis',
          status: 'VALIDATED',
          recipientName: 'Jean Dupont',
          driver: {
            id: 1,
            firstName: 'Pierre',
            lastName: 'Livreur'
          },
          createdAt: new Date(Date.now() - 3600000).toISOString(),
          validatedAt: new Date().toISOString()
        }
      ];

      this.loading = false;
    }, 1000);
  }

  /**
   * Actualiser les données
   * 
   * Cette méthode recharge les preuves depuis l'API. Elle est appelée
   * quand l'utilisateur clique sur le bouton d'actualisation.
   */
  refresh(): void {
    if (this.delivery) {
      this.loadProofs(this.delivery.id);
    }
  }

  /**
   * Retourner à la page précédente
   * 
   * Utilise l'API de navigation du navigateur pour revenir en arrière.
   * C'est comme si l'utilisateur cliquait sur le bouton "Précédent" du navigateur.
   */
  goBack(): void {
    window.history.back();
  }

  /**
   * Obtenir le libellé français d'un type de preuve
   * 
   * Convertit les codes techniques en texte lisible pour l'utilisateur.
   * 
   * @param type - Le type de preuve (SIGNATURE, PHOTO, QR_CODE)
   * @returns Le libellé en français
   */
  getProofLabel(type: string): string {
    const labels: Record<string, string> = {
      'SIGNATURE': 'Signature',
      'PHOTO': 'Photo',
      'QR_CODE': 'Code QR'
    };
    return labels[type] || type;
  }

  /**
   * Obtenir l'icône Material correspondant au type de preuve
   * 
   * @param type - Le type de preuve
   * @returns Le nom de l'icône Material
   */
  getProofTypeIcon(type: string): string {
    const icons: Record<string, string> = {
      'SIGNATURE': 'draw',
      'PHOTO': 'photo_camera',
      'QR_CODE': 'qr_code'
    };
    return icons[type] || 'image';
  }

  /**
   * Obtenir le libellé français d'un statut de preuve
   * 
   * @param status - Le statut de la preuve
   * @returns Le libellé en français
   */
  getProofStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      'PENDING': 'En attente',
      'VALIDATED': 'Validée',
      'REJECTED': 'Rejetée'
    };
    return labels[status] || status;
  }

  /**
   * Afficher une image en plein écran
   * 
   * Cette méthode est appelée quand l'utilisateur clique sur une image.
   * Elle stocke la preuve sélectionnée et affiche la modal en plein écran.
   * 
   * @param proof - La preuve de livraison à afficher
   */
  viewFullImage(proof: DeliveryProof): void {
    this.selectedProof = proof;
    this.showFullImage = true;
  }

  /**
   * Fermer la modal d'image en plein écran
   * 
   * Réinitialise les propriétés de la modal pour la masquer.
   */
  closeFullImage(): void {
    this.showFullImage = false;
    // Petit délai avant de réinitialiser selectedProof pour permettre
    // à l'animation de sortie de se terminer correctement
    setTimeout(() => {
      this.selectedProof = null;
    }, 300);
  }

  /**
   * Valider une preuve de livraison
   * 
   * Cette méthode envoie une requête à l'API pour marquer la preuve comme validée.
   * Une fois validée, la preuve ne peut plus être modifiée.
   * 
   * @param proof - La preuve à valider
   */
  validateProof(proof: DeliveryProof): void {
    // Demander confirmation à l'utilisateur
    const confirmed = confirm('Êtes-vous sûr de vouloir valider cette preuve ?');
    
    if (!confirmed) {
      return;
    }

    this.processing = true;

    // SIMULATION D'APPEL API
    // En production, remplacez ceci par :
    // this.deliveryService.validateProof(proof.id).subscribe({
    //   next: () => {
    //     proof.status = 'VALIDATED';
    //     proof.validatedAt = new Date().toISOString();
    //     this.processing = false;
    //   },
    //   error: (err) => {
    //     alert('Erreur lors de la validation');
    //     this.processing = false;
    //   }
    // });

    setTimeout(() => {
      // Mettre à jour localement
      proof.status = 'VALIDATED';
      proof.validatedAt = new Date().toISOString();
      this.processing = false;
      
      // Afficher un message de succès
      console.log('✅ Preuve validée avec succès');
    }, 500);
  }

  /**
   * Rejeter une preuve de livraison
   * 
   * Cette méthode demande une raison du rejet puis envoie la requête à l'API.
   * 
   * @param proof - La preuve à rejeter
   */
  rejectProof(proof: DeliveryProof): void {
    // Demander la raison du rejet
    const reason = prompt('Veuillez indiquer la raison du rejet :');
    
    // Si l'utilisateur annule ou ne fournit pas de raison
    if (!reason || reason.trim() === '') {
      return;
    }

    this.processing = true;

    // SIMULATION D'APPEL API
    // En production, remplacez ceci par :
    // this.deliveryService.rejectProof(proof.id, reason).subscribe({
    //   next: () => {
    //     proof.status = 'REJECTED';
    //     proof.rejectionReason = reason;
    //     this.processing = false;
    //   },
    //   error: (err) => {
    //     alert('Erreur lors du rejet');
    //     this.processing = false;
    //   }
    // });

    setTimeout(() => {
      // Mettre à jour localement
      proof.status = 'REJECTED';
      proof.rejectionReason = reason;
      this.processing = false;
      
      // Afficher un message
      console.log('❌ Preuve rejetée :', reason);
    }, 500);
  }
}