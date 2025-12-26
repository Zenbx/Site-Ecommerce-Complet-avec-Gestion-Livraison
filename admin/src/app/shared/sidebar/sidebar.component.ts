import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { Admin, AuthService } from '../../core/services/auth.service';

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
      icon: 'dashboard',
      label: 'Dashboard',
      route: '/dashboard'
    },
    {
      icon: 'inventory_2',
      label: 'Produits',
      route: '/products'
    },
    {
      icon: 'shopping_cart',
      label: 'Commandes',
      route: '/orders'
    },
    {
      icon: 'local_shipping',
      label: 'Livraisons',
      route: '/deliveries'
    },
    {
      icon: 'group',
      label: 'Livreurs',
      route: '/drivers'
    },
    {
      icon: 'map',
      label: 'Carte',
      route: '/map'
    },
    {
      icon: 'analytics',
      label: 'Rapports',
      route: '/reports'
    }
  ];

  adminProfile?: Admin;

  constructor(private authService: AuthService) { }

  async ngOnInit(): Promise<void> {
    this.authService.getProfile().subscribe({
      next: (profile) => {
        this.adminProfile = profile;
      },
      error: (err) => {
        console.error('Failed to load profile', err);
      }
    });
  }

  logout(): void {
    if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
      this.authService.logout();
    }
  }
}