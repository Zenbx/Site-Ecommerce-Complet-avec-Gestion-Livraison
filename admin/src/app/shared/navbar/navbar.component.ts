// src/app/shared/navbar/navbar.component.ts
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
  currentTime = new Date();
  showNotifications = false;

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
    setInterval(() => {
      this.currentTime = new Date();
    }, 1000);
  }

  toggleNotifications(): void {
    this.showNotifications = !this.showNotifications;
  }


  logout(): void {
    this.authService.logout();
  }
}