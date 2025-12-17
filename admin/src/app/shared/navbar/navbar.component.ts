import { Component, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../core/services/auth.service';

// Définition d'une interface pour la sécurité du type
interface Notification {
  id: number;
  type: string;
  message: string;
  time: string;
  read: boolean; // Ajout de la propriété manquante
}

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnDestroy {
  currentTime = new Date();
  showNotifications = false;
  private timer: any;

  // Liste des notifications mise à jour
  notifications: Notification[] = [
    { 
      id: 1, 
      type: 'success', 
      message: 'Livraison #CMD-001 terminée',
      time: '5 min',
      read: false // Par défaut non lu
    },
    { 
      id: 2, 
      type: 'warning', 
      message: 'Livreur en retard - #CMD-045',
      time: '12 min',
      read: false
    },
    { 
      id: 3, 
      type: 'info', 
      message: 'Nouvelle commande assignée',
      time: '25 min',
      read: true // Exemple d'une déjà lue
    }
  ];

  constructor(public authService: AuthService) {
    // Mise à jour de l'heure
    this.timer = setInterval(() => {
      this.currentTime = new Date();
    }, 1000);
  }

  // Nettoyage du timer quand on quitte le composant
  ngOnDestroy(): void {
    if (this.timer) clearInterval(this.timer);
  }

  toggleNotifications(): void {
    this.showNotifications = !this.showNotifications;
  }

  // Marquer tout comme lu (utilisé par le bouton dans le HTML)
  markAllAsRead(): void {
    this.notifications.forEach(n => n.read = true);
  }

  clearNotifications(): void {
    this.notifications = [];
  }

  logout(): void {
    this.authService.logout();
  }
}