// src/app/app.routes.ts
import { Routes } from '@angular/router';
import { LoginComponent } from './features/auth/login/login.component';
import { LayoutComponent } from './shared/layout/layout.component';
import { DashboardComponent } from './features/dashboard/dashboard.component';
import { ReportsComponent } from './features/reports/reports.component';
import { AuthGuard } from './core/guards/auth.guard'; // Décommente quand tu auras le guard

export const routes: Routes = [
  { 
    path: 'login', 
    component: LoginComponent 
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
      // Ajoute tes autres routes ici
      // { path: 'drivers', loadChildren: () => import('./features/delivery-drivers/delivery-drivers.routes').then(m => m.DRIVERS_ROUTES) },
      // { path: 'deliveries', loadChildren: () => import('./features/deliveries/deliveries.routes').then(m => m.DELIVERIES_ROUTES) },
    ]
  },

  // Redirection par défaut
  { 
    path: '**', 
    redirectTo: 'login' 
  }
];