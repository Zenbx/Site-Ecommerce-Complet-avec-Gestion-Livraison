// src/app/app.routes.ts
import { Routes } from '@angular/router';
import { LoginComponent } from './features/auth/login/login.component';
import { AccessDeniedComponent } from './shared/access-denied/access-denied.component';
import { LayoutComponent } from './shared/layout/layout.component';
import { DashboardComponent } from './features/dashboard/dashboard.component';
import { DeliveryDriversListComponent } from './features/delivery-drivers/delivery-drivers-list/delivery-drivers-list.component';
import { DeliveriesListComponent } from './features/deliveries/deliveries-list/deliveries-list.component'
import { DeliveryTrackingComponent } from './features/deliveries/delivery-tracking/delivery-tracking.component';
import { ReportsComponent } from './features/reports/reports.component';
import { AuthGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  { 
    path: 'login', 
    component: LoginComponent 
  },
  {
    path: 'access-denied',
    component: AccessDeniedComponent
  },

  // Routes protégées (avec layout)
  {
    path: '',
    component: LayoutComponent,
    canActivate: [AuthGuard], // Décommente pour activer la protection
    children: [
      { 
        path: '', 
        redirectTo: 'dashboard', 
        pathMatch: 'full' 
      },
      { 
        path: 'dashboard', 
        component: DashboardComponent 
      },
      { 
        path: 'reports', 
        component: ReportsComponent 
      },
      {
        path: 'drivers',
        component: DeliveryDriversListComponent
      },
      {
        path: 'deliveries',
        component: DeliveriesListComponent
      },
      {
        path: 'deliveries/track/:id',
        component: DeliveryTrackingComponent
      }
    ]
  },

  // Redirection par défaut
  { 
    path: '**', 
    redirectTo: 'login' 
  }
];