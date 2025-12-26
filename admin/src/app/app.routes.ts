// src/app/app.routes.ts
import { Routes } from '@angular/router';
import { LoginComponent } from './features/auth/login/login.component';
import { RegisterComponent } from './features/auth/register/register.component';
import { AccessDeniedComponent } from './shared/access-denied/access-denied.component';
import { LayoutComponent } from './shared/layout/layout.component';
import { DashboardComponent } from './features/dashboard/dashboard.component';
import { ProductsComponent } from './features/products/products.component';
import { OrdersListComponent } from './features/orders/orders-list.component';
import { DeliveryDriversListComponent } from './features/delivery-drivers/delivery-drivers-list/delivery-drivers-list.component';
import { DriverFormComponent } from './features/delivery-drivers/driver-form/driver-form.component';
import { DeliveriesListComponent } from './features/deliveries/deliveries-list/deliveries-list.component'
import { DeliveryDetailsComponent } from './features/deliveries/delivery-details/delivery-details.component';
import { DeliveryTrackingComponent } from './features/deliveries/delivery-tracking/delivery-tracking.component';
import { MapComponent } from './features/map/map.component';
import { DeliveryProofComponent } from './features/deliveries/delivery-proof/delivery-proof.component';
import { ReportsComponent } from './features/reports/reports.component';
import { AuthGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  {
    path: 'login',
    component: LoginComponent
  },
  {
    path: 'register',
    component: RegisterComponent
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
        path: 'products',
        component: ProductsComponent
      },
      {
        path: 'orders',
        component: OrdersListComponent
      },
      {
        path: 'drivers',
        component: DeliveryDriversListComponent
      },
      {
        path: 'drivers/new',
        component: DriverFormComponent
      },
      {
        path: 'drivers/edit/:id',
        component: DriverFormComponent
      },
      {
        path: 'deliveries',
        component: DeliveriesListComponent
      },
      {
        path: 'deliveries/:id',
        component: DeliveryDetailsComponent
      },
      {
        path: 'deliveries/track/:id',
        component: DeliveryTrackingComponent
      },
      {
        path: 'deliveries/proof/:id',
        component: DeliveryProofComponent
      },
      {
        path: 'map',
        component: MapComponent
      },
      {
        path: 'reports',
        component: ReportsComponent
      }
    ]
  },

  // Redirection par défaut
  {
    path: '**',
    redirectTo: 'login'
  }
];