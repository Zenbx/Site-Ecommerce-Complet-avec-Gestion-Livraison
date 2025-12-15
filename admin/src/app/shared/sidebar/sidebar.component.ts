// src/app/shared/sidebar/sidebar.component.ts
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent {
  menuItems = [
    { 
      icon: '📊', 
      label: 'Dashboard', 
      route: '/dashboard'
    },
    { 
      icon: '🚚', 
      label: 'Livraisons', 
      route: '/deliveries',
      children: [
        { label: 'Liste', route: '/deliveries' },
        { label: 'En cours', route: '/deliveries/active' },
        { label: 'Historique', route: '/deliveries/history' }
      ]
    },
    { 
      icon: '👥', 
      label: 'Livreurs', 
      route: '/drivers' 
    },
    { 
      icon: '📈', 
      label: 'Rapports', 
      route: '/reports' 
    },
    { 
      icon: '⚙️', 
      label: 'Paramètres', 
      route: '/settings' 
    }
  ];

  constructor(public authService: AuthService) {}

  logout(): void {
    if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
      this.authService.logout();
    }
  }
}