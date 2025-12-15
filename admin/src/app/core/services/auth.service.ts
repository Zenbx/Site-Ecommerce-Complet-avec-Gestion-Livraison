// src/app/core/services/auth.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap, of, delay } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';
import { User, LoginRequest, LoginResponse } from '../models/user.model';
import { UserRole, ROLE_PERMISSIONS } from '../models/role.enum';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject: BehaviorSubject<User | null>;
  public currentUser$: Observable<User | null>;
  private tokenKey = 'auth_token';
  private userKey = 'current_user';
  
  // 🧪 MODE MOCK - Changez à false pour utiliser la vraie API
  private MOCK_MODE = true;

  constructor(
    private http: HttpClient,
    private router: Router
  ) {
    const storedUser = localStorage.getItem(this.userKey);
    this.currentUserSubject = new BehaviorSubject<User | null>(
      storedUser ? JSON.parse(storedUser) : null
    );
    this.currentUser$ = this.currentUserSubject.asObservable();
  }

  public get currentUserValue(): User | null {
    return this.currentUserSubject.value;
  }

  public get token(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  login(credentials: LoginRequest): Observable<LoginResponse> {
    if (this.MOCK_MODE) {
      return this.mockLogin(credentials);
    }

    return this.http.post<LoginResponse>(
      `${environment.apiUrl}/auth/login`,
      credentials
    ).pipe(
      tap(response => {
        this.storeAuthData(response);
      })
    );
  }

  private mockLogin(credentials: LoginRequest): Observable<LoginResponse> {
    // Simule différents utilisateurs avec différents rôles
    const mockUsers: { [key: string]: LoginResponse } = {
      'admin@test.com': {
        user: {
          id: 1,
          firstName: 'Admin',
          lastName: 'Système',
          email: 'admin@test.com',
          role: UserRole.ADMIN,
          isActive: true,
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString()
        },
        token: 'mock-admin-token-' + Date.now(),
        expiresIn: 3600
      },
      'gestionnaire@test.com': {
        user: {
          id: 2,
          firstName: 'Jean',
          lastName: 'Gestionnaire',
          email: 'gestionnaire@test.com',
          role: UserRole.MANAGER,
          isActive: true,
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString()
        },
        token: 'mock-manager-token-' + Date.now(),
        expiresIn: 3600
      },
      'superviseur@test.com': {
        user: {
          id: 3,
          firstName: 'Marie',
          lastName: 'Superviseur',
          email: 'superviseur@test.com',
          role: UserRole.SUPERVISOR,
          isActive: true,
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString()
        },
        token: 'mock-supervisor-token-' + Date.now(),
        expiresIn: 3600
      }
    };

    const response = mockUsers[credentials.email];

    if (!response) {
      // Simule une erreur de connexion
      return new Observable(observer => {
        setTimeout(() => {
          observer.error({ message: 'Email ou mot de passe incorrect' });
        }, 500);
      });
    }

    return of(response).pipe(
      delay(500),
      tap(res => {
        this.storeAuthData(res);
        console.log('🧪 Mock Login:', res.user.role);
      })
    );
  }

  private storeAuthData(response: LoginResponse): void {
    localStorage.setItem(this.tokenKey, response.token);
    localStorage.setItem(this.userKey, JSON.stringify(response.user));
    this.currentUserSubject.next(response.user);
  }

  logout(): void {
    if (!this.MOCK_MODE) {
      this.http.post(`${environment.apiUrl}/auth/logout`, {}).subscribe({
        complete: () => this.clearAuthData(),
        error: () => this.clearAuthData()
      });
    } else {
      this.clearAuthData();
    }
  }

  private clearAuthData(): void {
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.userKey);
    this.currentUserSubject.next(null);
    this.router.navigate(['/login']);
  }

  isAuthenticated(): boolean {
    return !!this.token && !!this.currentUserValue;
  }

  hasRole(roles: UserRole[]): boolean {
    const user = this.currentUserValue;
    if (!user) return false;
    return roles.includes(user.role);
  }

  hasPermission(permission: string): boolean {
    const user = this.currentUserValue;
    if (!user) return false;
    
    // Admin a tous les droits
    if (user.role === UserRole.ADMIN) return true;
    
    // Vérifier les permissions spécifiques au rôle
    const rolePermissions = ROLE_PERMISSIONS[user.role];
    if (!rolePermissions) return false;
    
    // Si le rôle a '*', il a toutes les permissions
    if (rolePermissions.includes('*')) return true;
    
    return rolePermissions.includes(permission);
  }

  canAccessRoute(routePermission: string): boolean {
    return this.hasPermission(routePermission);
  }

  refreshToken(): Observable<{ token: string }> {
    if (this.MOCK_MODE) {
      const mockToken = { token: 'mock-refreshed-token-' + Date.now() };
      return of(mockToken).pipe(
        delay(300),
        tap(response => {
          localStorage.setItem(this.tokenKey, response.token);
        })
      );
    }

    return this.http.post<{ token: string }>(
      `${environment.apiUrl}/auth/refresh`,
      {}
    ).pipe(
      tap(response => {
        localStorage.setItem(this.tokenKey, response.token);
      })
    );
  }

  // Méthode utilitaire pour afficher le rôle en français
  getRoleLabel(role: UserRole): string {
    const labels = {
      [UserRole.ADMIN]: 'Administrateur',
      [UserRole.MANAGER]: 'Gestionnaire',
      [UserRole.SUPERVISOR]: 'Superviseur'
    };
    return labels[role] || role;
  }
}