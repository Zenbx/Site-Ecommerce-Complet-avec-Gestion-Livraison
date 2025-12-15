import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent {
  currentTime = new Date(); // L'heure actuelle
  showNotifications = false; // Affichage des notifications
  notificationsCount = 0; // Compte des notifications non lues

  // Liste des notifications
  notifications = [
    { 
      id: 1, 
      type: 'success', 
      message: 'Livraison #CMD-001 terminée',
      time: '5 min'
    },
    { 
      id: 2, 
      type: 'warning', 
      message: 'Livreur en retard - #CMD-045',
      time: '12 min'
    },
    { 
      id: 3, 
      type: 'info', 
      message: 'Nouvelle commande assignée',
      time: '25 min'
    }
  ];

  constructor(public authService: AuthService) {
    // Mise à jour de l'heure toutes les secondes
    setInterval(() => {
      this.currentTime = new Date();
    }, 1000);
    
    // Compte des notifications non lues
    this.notificationsCount = this.notifications.length;
  }

  // Fonction pour basculer l'affichage des notifications
  toggleNotifications(): void {
    this.showNotifications = !this.showNotifications;
  }

  // Fonction pour effacer toutes les notifications
  clearNotifications(): void {
    this.notifications = [];
    this.notificationsCount = 0;
  }

  // Fonction de déconnexion
  logout(): void {
    this.authService.logout();
  }
}
