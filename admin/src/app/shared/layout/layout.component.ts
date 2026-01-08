// src/app/shared/layout/layout.component.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { NavbarComponent } from '../navbar/navbar.component';
import { SidebarComponent } from '../sidebar/sidebar.component';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [
    CommonModule,
    RouterOutlet,
    RouterLink,
    RouterLinkActive,
    NavbarComponent,
    SidebarComponent
  ],
  templateUrl: './layout.component.html',
  styleUrls: ['./layout.component.scss']
})
export class LayoutComponent implements OnInit, OnDestroy {
  isSidebarOpen = false;
  isMobile = false;
  isTablet = false;
  showMoreMenu = false;

  // Menu items that are not present in the bottom-nav and shown via "Plus"
  extraMenuItems = [
    { icon: 'group', label: 'Livreurs', route: '/drivers' },
    { icon: 'map', label: 'Carte', route: '/map' },
    { icon: 'analytics', label: 'Rapports', route: '/reports' }
  ];

  private resizeListener?: () => void;

  ngOnInit() {
    this.checkScreenSize();
    
    // Créer la fonction de listener pour pouvoir la supprimer plus tard
    this.resizeListener = () => this.checkScreenSize();
    window.addEventListener('resize', this.resizeListener);
  }

  /**
   * Vérifie la taille de l'écran et met à jour les flags
   */
  checkScreenSize() {
    const width = window.innerWidth;
    this.isMobile = width < 768;
    this.isTablet = width >= 768 && width < 1024;
    
    // Fermer automatiquement la sidebar en passant en desktop
    if (width >= 1024) {
      this.isSidebarOpen = false;
    }
  }

  /**
   * Toggle l'ouverture/fermeture de la sidebar
   */
  toggleSidebar() {
    this.isSidebarOpen = !this.isSidebarOpen;
    
    // Empêcher le scroll du body quand la sidebar est ouverte sur mobile
    if (this.isSidebarOpen && this.isMobile) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  }

  toggleMoreMenu() {
    this.showMoreMenu = !this.showMoreMenu;
  }

  closeMoreMenu() {
    this.showMoreMenu = false;
  }

  /**
   * Ferme la sidebar
   */
  closeSidebar() {
    this.isSidebarOpen = false;
    document.body.style.overflow = '';
  }

  ngOnDestroy() {
    // Nettoyer l'event listener
    if (this.resizeListener) {
      window.removeEventListener('resize', this.resizeListener);
    }
    
    // Restaurer le scroll du body
    document.body.style.overflow = '';
  }

  
}