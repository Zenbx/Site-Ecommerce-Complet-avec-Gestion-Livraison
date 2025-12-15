// src/app/shared/layout/layout.component.ts
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet } from '@angular/router';
import { NavbarComponent } from '../navbar/navbar.component';
import { SidebarComponent } from '../sidebar/sidebar.component';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [
    CommonModule,
    RouterOutlet,
    NavbarComponent,
    SidebarComponent
  ],
  // ✅ SUPPRIME ViewEncapsulation.None - C'EST LE PROBLÈME !
  template: `
    <div class="app-layout">
      <app-navbar></app-navbar>
      <div class="main-wrapper">
        <app-sidebar></app-sidebar>
        <main class="main-content">
          <router-outlet></router-outlet>
        </main>
      </div>
    </div>
  `,
  styles: [`
    :host {
      display: block;
      height: 100vh;
      overflow: hidden;
    }

    .app-layout {
      display: flex;
      flex-direction: column;
      height: 100vh;
      background: #f5f7fa;
    }

    .main-wrapper {
      display: flex;
      flex: 1;
      overflow: hidden;
    }

    .main-content {
      flex: 1;
      overflow-y: auto;
      background: #f5f7fa;
    }
  `]
})
export class LayoutComponent {}