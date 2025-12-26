// src/app/features/deliveries/delivery-details/delivery-details.component.ts

import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import { DeliveriesService } from '../../../core/services/deliveries.service';
import { Delivery, DeliveryDriver } from '../../../core/models/delivery.model';

/**
 * DeliveryDetailsComponent
 * 
 * Ce composant affiche tous les détails d'une livraison spécifique et permet
 * aux administrateurs d'effectuer diverses actions comme :
 * - Assigner un livreur manuellement
 * - Déclencher une assignation automatique
 * - Retirer un livreur assigné
 * - Voir le tracking en temps réel
 * - Visualiser les preuves de livraison
 * 
 * Le composant gère également une modal pour la sélection de livreurs lors
 * de l'assignation manuelle, avec affichage de leurs statistiques et disponibilité.
 */
@Component({
  selector: 'app-delivery-details',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './delivery-details.component.html',
  styleUrls: ['./delivery-details.component.scss']
})
export class DeliveryDetailsComponent implements OnInit, OnDestroy {
  
  // ============================================
  // PROPRIÉTÉS D'ÉTAT PRINCIPAL
  // ============================================
  
  /**
   * La livraison actuellement affichée
   * Null pendant le chargement ou en cas d'erreur
   */
  delivery: Delivery | null = null;
  
  /**
   * Indicateur de chargement initial
   * True pendant la récupération des données depuis l'API
   */
  loading = true;
  
  /**
   * Message d'erreur éventuel
   * Affiché si le chargement échoue ou si une action échoue
   */
  error: string | null = null;
  
  /**
   * Indicateur de traitement d'une action
   * True pendant l'exécution d'une action (assignation, retrait, etc.)
   * Utilisé pour désactiver les boutons et empêcher les double-clics
   */
  processing = false;
  
  // ============================================
  // PROPRIÉTÉS POUR LA MODAL D'ASSIGNATION
  // ============================================
  
  /**
   * Contrôle l'affichage de la modal de sélection de livreur
   * True = modal visible, False = modal cachée
   */
  showAssignModal = false;
  
  /**
   * Liste des livreurs disponibles pour assignation
   * Chargée depuis l'API quand l'utilisateur ouvre la modal
   */
  availableDrivers: DeliveryDriver[] = [];
  
  /**
   * L'ID du livreur actuellement sélectionné dans la modal
   * Null = aucune sélection, Number = livreur sélectionné
   */
  selectedDriverId: number | null = null;
  
  /**
   * Indicateur de chargement de la liste des livreurs
   * True pendant la récupération de la liste depuis l'API
   */
  loadingDrivers = false;
  
  // ============================================
  // RXJS
  // ============================================
  
  /**
   * Subject pour gérer la désinscription des observables
   * Permet de nettoyer automatiquement toutes les souscriptions
   * quand le composant est détruit, évitant ainsi les fuites mémoire
   */
  private destroy$ = new Subject<void>();

  // ============================================
  // CONSTRUCTEUR
  // ============================================

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private deliveriesService: DeliveriesService
  ) {}

  // ============================================
  // HOOKS DU CYCLE DE VIE ANGULAR
  // ============================================

  /**
   * Initialisation du composant
   * 
   * Cette méthode est appelée automatiquement par Angular après la création
   * du composant. Elle écoute les changements de paramètres dans l'URL pour
   * récupérer l'ID de la livraison et charger ses données.
   * 
   * L'utilisation de takeUntil(this.destroy$) garantit que la souscription
   * sera automatiquement annulée quand le composant sera détruit.
   */
  ngOnInit(): void {
    this.route.params
      .pipe(takeUntil(this.destroy$))
      .subscribe(params => {
        const deliveryId = +params['id']; // Le + convertit la string en number
        if (deliveryId && !isNaN(deliveryId)) {
          this.loadDelivery(deliveryId);
        } else {
          this.error = 'ID de livraison invalide';
          this.loading = false;
        }
      });
  }

  /**
   * Nettoyage avant la destruction du composant
   * 
   * Cette méthode est appelée juste avant qu'Angular ne détruise le composant
   * (par exemple, quand l'utilisateur navigue vers une autre page).
   * Elle déclenche le Subject destroy$ qui annule automatiquement toutes
   * les souscriptions RxJS en cours, évitant ainsi les fuites mémoire.
   */
  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  // ============================================
  // CHARGEMENT DES DONNÉES
  // ============================================

  /**
   * Charger les détails complets de la livraison depuis l'API
   * 
   * Cette méthode effectue un appel HTTP GET vers le backend Laravel
   * pour récupérer toutes les informations de la livraison :
   * - Données client (nom, téléphone, email)
   * - Adresse de livraison avec coordonnées GPS
   * - Statut actuel et historique
   * - Livreur assigné (si applicable) avec sa position GPS
   * - Preuves de livraison (si disponibles)
   * 
   * La méthode gère également les états de chargement et d'erreur
   * pour offrir un bon feedback visuel à l'utilisateur.
   * 
   * @param deliveryId - L'identifiant unique de la livraison à charger
   */
  private loadDelivery(deliveryId: number): void {
    this.loading = true;
    this.error = null;

    this.deliveriesService.getDelivery(deliveryId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (delivery) => {
          console.log('📦 Livraison chargée avec succès:', delivery);
          this.delivery = delivery;
          this.loading = false;
        },
        error: (error) => {
          console.error('❌ Erreur lors du chargement de la livraison:', error);
          this.error = error.message || 'Impossible de charger les détails de la livraison';
          this.loading = false;
        }
      });
  }

  /**
   * Actualiser les données de la livraison
   * 
   * Cette méthode permet à l'utilisateur de recharger manuellement les données
   * de la livraison. C'est utile si :
   * - Des changements ont été effectués ailleurs (par le livreur via l'app mobile)
   * - L'utilisateur veut s'assurer d'avoir les dernières informations
   * - Une erreur s'est produite et l'utilisateur veut réessayer
   */
  refresh(): void {
    if (this.delivery) {
      console.log('🔄 Actualisation de la livraison...');
      this.loadDelivery(this.delivery.id);
    }
  }

  // ============================================
  // NAVIGATION
  // ============================================

  /**
   * Retourner à la liste des livraisons
   * 
   * Navigue vers la page principale qui affiche toutes les livraisons.
   * Utilise le Router Angular pour une navigation côté client fluide
   * sans rechargement de page.
   */
  goBack(): void {
    this.router.navigate(['/deliveries']);
  }

  /**
   * Naviguer vers le suivi en temps réel de la livraison
   * 
   * Cette méthode redirige l'utilisateur vers la page de tracking GPS
   * où il pourra voir la position du livreur sur une carte interactive
   * en temps réel, avec mise à jour automatique via WebSocket.
   * 
   * Le bouton n'est affiché que si la livraison est en cours (IN_PROGRESS).
   */
  viewTracking(): void {
    if (this.delivery?.id) {
      this.router.navigate(['/deliveries/tracking', this.delivery.id]);
    }
  }

  /**
   * Naviguer vers les preuves de livraison
   * 
   * Redirige vers la page qui affiche les preuves de livraison
   * (signatures électroniques, photos, codes QR scannés).
   * 
   * Le bouton n'est affiché que si la livraison est terminée (DELIVERED).
   */
  viewProof(): void {
    if (this.delivery?.id) {
      this.router.navigate(['/deliveries/proof', this.delivery.id]);
    }
  }

  // ============================================
  // ASSIGNATION DE LIVREUR - MODAL
  // ============================================

  /**
   * Ouvrir la modal d'assignation manuelle de livreur
   * 
   * Cette méthode est appelée quand l'utilisateur clique sur le bouton
   * "Assigner un livreur". Elle :
   * 1. Affiche la modal de sélection
   * 2. Réinitialise toute sélection précédente
   * 3. Charge la liste des livreurs disponibles depuis l'API
   * 
   * La modal affiche ensuite la liste des livreurs avec leurs informations :
   * - Nom complet
   * - Type de véhicule et plaque d'immatriculation
   * - Disponibilité actuelle
   * - Statistiques de performance (taux de réussite, nombre de livraisons)
   * - Position géographique actuelle (si disponible)
   * 
   * L'utilisateur peut ensuite sélectionner un livreur et confirmer l'assignation.
   */
  assignDriver(): void {
    console.log('🚚 Ouverture de la modal d\'assignation de livreur');
    
    // Afficher la modal
    this.showAssignModal = true;
    
    // Réinitialiser la sélection précédente
    this.selectedDriverId = null;
    
    // Charger la liste des livreurs disponibles
    this.loadAvailableDrivers();
  }

  /**
   * Charger la liste des livreurs disponibles depuis l'API
   * 
   * Cette méthode privée fait un appel HTTP GET vers le backend Laravel
   * pour récupérer tous les livreurs qui sont actuellement disponibles
   * pour accepter de nouvelles livraisons.
   * 
   * Les livreurs sont filtrés côté serveur selon plusieurs critères :
   * - is_available = true (marqués comme disponibles)
   * - Pas trop de livraisons en cours (charge de travail raisonnable)
   * - Statut actif (compte non suspendu)
   * 
   * En cas d'erreur, un message d'alerte est affiché à l'utilisateur
   * et l'indicateur de chargement est désactivé.
   */
  private loadAvailableDrivers(): void {
    this.loadingDrivers = true;

    this.deliveriesService.getAvailableDrivers()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (drivers) => {
          console.log(`📋 ${drivers.length} livreurs disponibles chargés`);
          this.availableDrivers = drivers;
          this.loadingDrivers = false;
        },
        error: (error) => {
          console.error('❌ Erreur lors du chargement des livreurs:', error);
          this.loadingDrivers = false;
          alert('Impossible de charger la liste des livreurs disponibles. Veuillez réessayer.');
        }
      });
  }

  /**
   * Sélectionner un livreur dans la modal
   * 
   * Cette méthode est appelée quand l'utilisateur clique sur une carte
   * de livreur dans la liste de la modal. Elle met simplement à jour
   * la propriété selectedDriverId pour mémoriser le choix de l'utilisateur.
   * 
   * L'interface visuelle met en évidence le livreur sélectionné avec
   * une bordure ou un fond coloré différent.
   * 
   * @param driverId - L'identifiant unique du livreur sélectionné
   */
  selectDriver(driverId: number): void {
    this.selectedDriverId = driverId;
    console.log('👤 Livreur sélectionné:', driverId);
  }

  /**
   * Confirmer l'assignation du livreur sélectionné
   * 
   * Cette méthode est appelée quand l'utilisateur clique sur le bouton
   * "Confirmer" dans la modal d'assignation. Elle effectue plusieurs étapes :
   * 
   * 1. **Validation** : Vérifie qu'un livreur a bien été sélectionné
   * 2. **Appel API** : Envoie une requête HTTP POST au backend Laravel
   * 3. **Mise à jour** : Actualise les données de la livraison localement
   * 4. **Notification** : Informe l'utilisateur du succès de l'opération
   * 5. **Fermeture** : Ferme automatiquement la modal
   * 
   * En production, cette assignation déclenche également :
   * - Une notification push vers l'application mobile du livreur
   * - Un événement WebSocket pour mettre à jour les autres interfaces en temps réel
   * - Un enregistrement dans les logs d'audit pour la traçabilité
   * 
   * En cas d'erreur (livreur indisponible, problème réseau, etc.),
   * un message d'erreur est affiché et la modal reste ouverte pour
   * permettre une nouvelle tentative.
   */
  confirmAssignment(): void {
    // Validation : vérifier qu'un livreur a été sélectionné
    if (!this.selectedDriverId) {
      alert('Veuillez sélectionner un livreur avant de confirmer');
      return;
    }

    // Validation : vérifier que nous avons une livraison
    if (!this.delivery) {
      console.error('❌ Aucune livraison à assigner');
      return;
    }

    console.log(`💾 Confirmation de l'assignation - Livraison #${this.delivery.id} → Livreur #${this.selectedDriverId}`);
    
    this.processing = true;

    // Appel API pour créer l'assignation
    this.deliveriesService.assignDriver(this.delivery.id, this.selectedDriverId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (updatedDelivery) => {
          console.log('✅ Livreur assigné avec succès');
          
          // Mettre à jour la livraison localement avec les nouvelles données
          this.delivery = updatedDelivery;
          
          // Fermer la modal
          this.closeAssignModal();
          
          // Afficher un message de succès
          alert(`Livreur assigné avec succès ! ${updatedDelivery.delivery_person?.name || 'Le livreur'} a été notifié.`);
          
          this.processing = false;
        },
        error: (error) => {
          console.error('❌ Erreur lors de l\'assignation du livreur:', error);
          alert(error.message || 'Impossible d\'assigner le livreur. Veuillez réessayer.');
          this.processing = false;
        }
      });
  }

  /**
   * Fermer la modal d'assignation
   * 
   * Réinitialise tous les états liés à la modal pour un prochain usage.
   * Cette méthode est appelée soit :
   * - Quand l'utilisateur clique sur "Annuler"
   * - Automatiquement après une assignation réussie
   * - Quand l'utilisateur clique en dehors de la modal (si implémenté)
   * 
   * La réinitialisation complète garantit qu'il n'y a pas de données
   * résiduelles qui pourraient causer des bugs lors de la prochaine ouverture.
   */
  closeAssignModal(): void {
    this.showAssignModal = false;
    this.selectedDriverId = null;
    this.availableDrivers = [];
    this.loadingDrivers = false;
  }

  // ============================================
  // ASSIGNATION AUTOMATIQUE
  // ============================================

  /**
   * Déclencher une assignation automatique intelligente
   * 
   * Cette fonctionnalité avancée permet au système de sélectionner
   * automatiquement le meilleur livreur pour une livraison donnée.
   * 
   * L'algorithme d'assignation automatique (implémenté côté serveur)
   * évalue chaque livreur disponible selon plusieurs critères pondérés :
   * 
   * **1. Proximité géographique (poids: 40%)**
   * - Calcule la distance entre la position actuelle du livreur
   *   et l'adresse de livraison
   * - Plus le livreur est proche, plus son score est élevé
   * - Utilise la formule de Haversine pour les calculs GPS
   * 
   * **2. Charge de travail (poids: 30%)**
   * - Compte le nombre de livraisons actuellement en cours
   * - Favorise les livreurs avec moins de livraisons assignées
   * - Évite de surcharger un même livreur
   * 
   * **3. Performance historique (poids: 20%)**
   * - Taux de réussite (% de livraisons complétées avec succès)
   * - Temps moyen de livraison
   * - Note moyenne des clients
   * 
   * **4. Disponibilité et expérience (poids: 10%)**
   * - Nombre total de livraisons effectuées
   * - Ancienneté dans le système
   * - Activité récente
   * 
   * Le système calcule un score total pour chaque livreur et sélectionne
   * celui avec le score le plus élevé. L'utilisateur reçoit ensuite un
   * résumé de la décision avec le nom du livreur choisi et les raisons
   * de ce choix.
   * 
   * Cette méthode demande d'abord une confirmation à l'utilisateur avant
   * de procéder, car l'action est irréversible sans passer par une
   * réassignation manuelle.
   */
  autoAssignDriver(): void {
    if (!this.delivery) {
      return;
    }

    // Demander confirmation à l'utilisateur
    const confirmed = confirm(
      'Le système va automatiquement sélectionner le meilleur livreur disponible selon plusieurs critères (proximité, charge de travail, performance). Continuer ?'
    );
    
    if (!confirmed) {
      return;
    }

    console.log(`🤖 Assignation automatique - Livraison #${this.delivery.id}`);
    
    this.processing = true;

    this.deliveriesService.autoAssignDriver(this.delivery.id)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (updatedDelivery) => {
          console.log('✅ Livreur assigné automatiquement avec succès');
          this.delivery = updatedDelivery;
          
          // Construire un message de succès détaillé
          const driverName = updatedDelivery.delivery_person?.name ||
                            `${updatedDelivery.delivery_person?.name}`;

          alert(
            `Assignation automatique réussie !\n\n` +
            `Livreur sélectionné : ${driverName}\n` +
            `Le livreur a été notifié et peut maintenant voir cette livraison dans son application mobile.`
          );
          
          this.processing = false;
        },
        error: (error) => {
          console.error('❌ Erreur lors de l\'assignation automatique:', error);
          alert(
            error.message ||
            'Aucun livreur disponible pour le moment. Veuillez réessayer plus tard ou effectuer une assignation manuelle.'
          );
          this.processing = false;
        }
      });
  }

  // ============================================
  // RETRAIT DE LIVREUR
  // ============================================

  /**
   * Retirer le livreur actuellement assigné à la livraison
   * 
   * Cette action annule l'assignation actuelle et remet la livraison
   * en statut PENDING (en attente d'assignation). Le livreur redevient
   * disponible pour d'autres livraisons.
   * 
   * Cette fonctionnalité est utile dans plusieurs cas :
   * - L'administrateur s'est trompé dans l'assignation initiale
   * - Le livreur a rencontré un problème et ne peut plus effectuer la livraison
   * - Le livreur a demandé à être retiré de la livraison
   * - Une réorganisation des tournées est nécessaire
   * 
   * **Restrictions de sécurité :**
   * - Impossible de retirer un livreur si la livraison est déjà en cours (IN_TRANSIT)
   * - Impossible de retirer un livreur si la livraison est terminée (DELIVERED)
   * 
   * Ces restrictions sont également appliquées côté serveur pour garantir
   * la cohérence des données même en cas de manipulation directe de l'API.
   * 
   * La méthode demande une confirmation avant d'agir, car c'est une action
   * importante qui affecte directement le planning du livreur.
   */
  removeDriver(): void {
    if (!this.delivery || !this.delivery.delivery_person) {
      return;
    }

    // Construire un message de confirmation personnalisé avec le nom du livreur
    const driverName = this.delivery.delivery_person.name ||
                      `${this.delivery.delivery_person.name}`;
    
    const confirmed = confirm(
      `Voulez-vous vraiment retirer ${driverName} de cette livraison ?\n\n` +
      `La livraison sera remise en attente d'assignation et devra être assignée à un nouveau livreur.`
    );
    
    if (!confirmed) {
      return;
    }

    console.log(`❌ Retrait du livreur - Livraison #${this.delivery.id}`);
    
    this.processing = true;

    this.deliveriesService.removeDriver(this.delivery.id)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (updatedDelivery) => {
          console.log('✅ Livreur retiré avec succès');
          this.delivery = updatedDelivery;
          
          alert(
            `Le livreur a été retiré de cette livraison avec succès.\n\n` +
            `La livraison est maintenant en attente d'assignation à un nouveau livreur.`
          );
          
          this.processing = false;
        },
        error: (error) => {
          console.error('❌ Erreur lors du retrait du livreur:', error);
          alert(error.message || 'Impossible de retirer le livreur. Veuillez réessayer.');
          this.processing = false;
        }
      });
  }

  // ============================================
  // MÉTHODES UTILITAIRES - STATUTS
  // ============================================

  /**
   * Obtenir le libellé français d'un statut de livraison
   * 
   * Transforme les codes techniques de statut (comme "pending")
   * en texte compréhensible pour l'utilisateur français.
   */
  getStatusLabel(status: string): string {
    const labels: { [key: string]: string } = {
      'PENDING': 'EN ATTENTE',
      'ASSIGNED': 'ASSIGNÉE',
      'IN_TRNASIT': 'EN COURS',
      'DELIVERED': 'LIVRÉE',
      'FAILED': 'ÉCHOUÉE',
      'CANCELLED': 'ANNULÉE'
    };
    return labels[status] || status;
  }

  /**
   * Obtenir la classe CSS appropriée pour un statut
   * 
   * Chaque statut a sa propre classe CSS pour afficher
   * une couleur différente (vert = livrée, rouge = échouée, etc.)
   */
  getStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'pending': 'status-pending',
      'assigned': 'status-assigned',
      'in_progress': 'status-in-progress',
      'delivered': 'status-delivered',
      'failed': 'status-failed',
      'cancelled': 'status-cancelled'
    };
    return classes[status] || '';
  }

  /**
   * Obtenir l'icône Material appropriée pour un statut
   */
  getStatusIcon(status: string): string {
    const icons: { [key: string]: string } = {
      'pending': 'schedule',
      'assigned': 'assignment_ind',
      'in_progress': 'local_shipping',
      'delivered': 'check_circle',
      'failed': 'cancel',
      'cancelled': 'block'
    };
    return icons[status] || 'help_outline';
  }

  // ============================================
  // MÉTHODES UTILITAIRES - PRIORITÉS
  // ============================================

  /**
   * Obtenir l'icône Material appropriée pour une priorité
   */
  getPriorityIcon(priority: string): string {
    const icons: { [key: string]: string } = {
      'high': 'priority_high',
      'medium': 'remove',
      'low': 'arrow_downward'
    };
    return icons[priority] || 'remove';
  }

  /**
   * Obtenir le libellé français d'une priorité
   */
  getPriorityLabel(priority: string): string {
    const labels: { [key: string]: string } = {
      'high': 'Urgente',
      'medium': 'Normale',
      'low': 'Basse'
    };
    return labels[priority] || priority;
  }

  // ============================================
  // MÉTHODES UTILITAIRES - FORMATAGE
  // ============================================

  /**
   * Formater une date en format français lisible
   * 
   * Transforme les dates ISO 8601 (2024-12-17T10:30:00Z)
   * en format français (17/12/2024, 10:30)
   */
  formatDate(date: string | undefined): string {
    if (!date) return 'N/A';
    
    return new Date(date).toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  // ============================================
  // MÉTHODES UTILITAIRES - AFFICHAGE CONDITIONNEL
  // ============================================

  /**
   * Vérifier si le bouton de tracking doit être affiché
   * Le tracking n'est disponible que si la livraison est en cours
   */
  canShowTracking(): boolean {
    return this.delivery?.status === 'in_progress';
  }

  /**
   * Vérifier si le bouton de preuve doit être affiché
   * La preuve n'est disponible que si la livraison est terminée
   */
  canShowProof(): boolean {
    return this.delivery?.status === 'delivered';
  }

  /**
   * Vérifier si le bouton d'assignation doit être affiché
   * On peut assigner un livreur seulement si la livraison est en attente
   * et qu'aucun livreur n'est encore assigné
   */
  canAssignDriver(): boolean {
    return this.delivery?.status === 'pending' && !this.delivery?.delivery_person;
  }

  /**
   * Vérifier si le bouton de retrait de livreur doit être affiché
   * On peut retirer un livreur seulement si :
   * - Un livreur est assigné
   * - La livraison n'est pas encore en cours (pas IN_PROGRESS)
   * - La livraison n'est pas terminée (pas DELIVERED)
   */
  canRemoveDriver(): boolean {
    return !!(
      this.delivery?.delivery_person &&
      (this.delivery.status === 'pending' ||
       this.delivery.status === 'assigned')
    );
  }
}