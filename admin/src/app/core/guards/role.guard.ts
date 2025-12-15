// src/app/core/guards/role.guard.ts

import { Injectable } from '@angular/core';
import { Router, CanActivate, ActivatedRouteSnapshot } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { UserRole } from '../models/role.enum';

@Injectable({
  providedIn: 'root'
})
export class RoleGuard implements CanActivate {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  canActivate(route: ActivatedRouteSnapshot): boolean {
    const allowedRoles = route.data['roles'] as UserRole[];
    
    if (!this.authService.isAuthenticated()) {
      this.router.navigate(['/auth/login']);
      return false;
    }

    if (allowedRoles && !this.authService.hasRole(allowedRoles)) {
      // Redirection vers une page d'accès refusé ou le dashboard
      this.router.navigate(['/dashboard']);
      return false;
    }

    return true;
  }
}