import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, of, throwError } from 'rxjs';
import { delay, tap } from 'rxjs/operators';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';
import { User, LoginRequest, LoginResponse, UserRole } from '../models/user.model';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl;
  private currentUserSubject: BehaviorSubject<User | null>;
  public currentUser: Observable<User | null>;

  constructor(
    private http: HttpClient,
    private router: Router
  ) {
    const storedUser = localStorage.getItem('currentUser');
    this.currentUserSubject = new BehaviorSubject<User | null>(
      storedUser ? JSON.parse(storedUser) : null
    );
    this.currentUser = this.currentUserSubject.asObservable();
  }

  public get currentUserValue(): User | null {
    return this.currentUserSubject.value;
  }

  login(credentials: LoginRequest): Observable<LoginResponse> {
    // Si on utilise des données mockées
    if (environment.useMockData) {
      return this.mockLogin(credentials);
    }
    
    // Sinon, appel réel à l'API
    return this.http.post<LoginResponse>(`${this.apiUrl}/login`, credentials)
      .pipe(
        tap(response => this.handleLoginSuccess(response))
      );
  }

  // ========================================
  // MOCK LOGIN - Simulation de connexion
  // ========================================
  private mockLogin(credentials: LoginRequest): Observable<LoginResponse> {
    console.log('🔧 MODE MOCK ACTIVÉ - Tentative de connexion');
    console.log('📧 Email:', credentials.email);
    
    // Compte Admin
    if (credentials.email === 'admin@test.com' && credentials.password === 'password') {
      const mockResponse: LoginResponse = {
        token: 'mock-jwt-token-admin-' + Date.now(),
        user: {
          id: 1,
          name: 'Administrateur Principal',
          email: credentials.email,
          role: UserRole.ADMIN
        }
      };
      
      console.log('✅ Connexion réussie - Rôle: ADMIN');
      
      return of(mockResponse).pipe(
        delay(800), // Simule le délai réseau
        tap(response => this.handleLoginSuccess(response))
      );
    }
    
    // Compte Gestionnaire
    if (credentials.email === 'gestionnaire@test.com' && credentials.password === 'password') {
      const mockResponse: LoginResponse = {
        token: 'mock-jwt-token-gestionnaire-' + Date.now(),
        user: {
          id: 2,
          name: 'Gestionnaire Logistique',
          email: credentials.email,
          role: UserRole.GESTIONNAIRE
        }
      };
      
      console.log('✅ Connexion réussie - Rôle: GESTIONNAIRE');
      
      return of(mockResponse).pipe(
        delay(800),
        tap(response => this.handleLoginSuccess(response))
      );
    }
    
    // Compte Superviseur
    if (credentials.email === 'superviseur@test.com' && credentials.password === 'password') {
      const mockResponse: LoginResponse = {
        token: 'mock-jwt-token-superviseur-' + Date.now(),
        user: {
          id: 3,
          name: 'Superviseur Livraisons',
          email: credentials.email,
          role: UserRole.SUPERVISEUR
        }
      };
      
      console.log('✅ Connexion réussie - Rôle: SUPERVISEUR');
      
      return of(mockResponse).pipe(
        delay(800),
        tap(response => this.handleLoginSuccess(response))
      );
    }
    
    // Identifiants incorrects
    console.log('❌ Échec de connexion - Identifiants incorrects');
    return throwError(() => ({
      error: { message: 'Email ou mot de passe incorrect' }
    })).pipe(delay(500));
  }

  // ========================================
  // GESTION DU SUCCÈS DE CONNEXION
  // ========================================
  private handleLoginSuccess(response: LoginResponse): void {
    localStorage.setItem('token', response.token);
    localStorage.setItem('currentUser', JSON.stringify(response.user));
    this.currentUserSubject.next(response.user);
    console.log('👤 Utilisateur connecté:', response.user);
  }

  // ========================================
  // DÉCONNEXION
  // ========================================
  logout(): void {
    console.log('🚪 Déconnexion en cours...');
    
    if (!environment.useMockData) {
      // Appel API pour invalider le token côté serveur
      this.http.post(`${this.apiUrl}/logout`, {}).subscribe();
    }
    
    // Nettoyer le localStorage
    localStorage.removeItem('token');
    localStorage.removeItem('currentUser');
    this.currentUserSubject.next(null);
    
    console.log('✅ Déconnexion réussie');
    
    // Rediriger vers login
    this.router.navigate(['/login']);
  }

  // ========================================
  // MÉTHODES UTILITAIRES
  // ========================================
  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  getToken(): string | null {
    return localStorage.getItem('token');
  }

  getUserRole(): UserRole | null {
    const user = this.currentUserValue;
    return user ? user.role : null;
  }

  hasRole(roles: UserRole[]): boolean {
    const userRole = this.getUserRole();
    return userRole ? roles.includes(userRole) : false;
  }

  getCurrentUserName(): string {
    const user = this.currentUserValue;
    return user ? user.name : '';
  }

  getCurrentUserEmail(): string {
    const user = this.currentUserValue;
    return user ? user.email : '';
  }
}