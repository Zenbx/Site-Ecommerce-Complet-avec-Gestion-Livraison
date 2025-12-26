// src/app/services/auth.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, BehaviorSubject, throwError } from 'rxjs';
import { map, catchError, tap } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

/**
 * Interface représentant un administrateur dans notre système
 * 
 * TypeScript nous permet de définir des interfaces qui décrivent la forme
 * des objets que nous manipulons. Cela nous donne de l'autocomplétion dans
 * l'éditeur et détecte les erreurs à la compilation plutôt qu'à l'exécution.
 */
export interface Admin {
  id: number;
  name: string;
  email: string;
  role: 'ADMIN' | 'GESTIONNAIRE' | 'SUPERVISEUR';
  created_at: string;
  updated_at: string;
}

/**
 * Interface pour la réponse de login de notre API Laravel
 * 
 * Cette interface décrit exactement la structure JSON que notre API retourne
 * quand un admin se connecte avec succès. Avoir cette interface nous permet
 * de typer nos fonctions et d'avoir la sécurité du typage TypeScript.
 */
export interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    admin: Admin;
    token: string;
    token_type: string;
  };
}

/**
 * Service d'authentification pour l'application admin
 * 
 * Ce service gère toutes les opérations liées à l'authentification des admins,
 * incluant le login, le logout, la vérification de l'état de connexion, et la
 * persistance du token dans le localStorage du navigateur.
 * 
 * Le décorateur @Injectable avec providedIn: 'root' signifie que ce service
 * est un singleton disponible partout dans l'application. Il n'y aura qu'une
 * seule instance de ce service partagée par tous les composants.
 */
@Injectable({
  providedIn: 'root'
})
export class AuthService {
  /**
   * URL de base de l'API importée depuis le fichier environment
   * 
   * En utilisant environment.apiUrl plutôt qu'une URL codée en dur, nous
   * pouvons facilement changer l'URL selon l'environnement sans toucher ce code.
   */
  private apiUrl = `${environment.apiUrl}/auth/admin`;

  /**
   * BehaviorSubject qui garde l'état de l'admin connecté
   * 
   * Un BehaviorSubject est un type spécial d'Observable dans RxJS qui garde
   * toujours la dernière valeur émise. Cela nous permet d'avoir un état réactif
   * de l'admin connecté. Quand cet état change, tous les composants qui sont
   * abonnés seront automatiquement notifiés et pourront réagir.
   * 
   * Nous initialisons avec null et typons comme Admin | null pour indiquer
   * qu'il peut soit contenir un admin soit être null quand personne n'est connecté.
   */
  private currentAdminSubject: BehaviorSubject<Admin | null>;
  
  /**
   * Observable public du currentAdminSubject
   * 
   * Nous exposons une version Observable du BehaviorSubject pour que les
   * composants puissent s'abonner aux changements mais ne puissent pas émettre
   * de nouvelles valeurs directement. Seul ce service peut changer l'admin connecté.
   */
  public currentAdmin$: Observable<Admin | null>;

  /**
   * Constructeur du service
   * 
   * Angular injecte automatiquement les dépendances demandées dans le constructeur.
   * HttpClient est le service Angular pour faire des requêtes HTTP vers des APIs.
   * Router est le service de routage Angular qui nous permet de naviguer entre pages.
   * 
   * @param http - Le service HttpClient injecté par Angular
   * @param router - Le service Router injecté par Angular
   */
  constructor(
    private http: HttpClient,
    private router: Router
  ) {
    /**
     * Initialisation du BehaviorSubject avec l'admin stocké dans localStorage
     * 
     * Quand l'application démarre, nous vérifions si un token et des données admin
     * sont stockés dans le localStorage du navigateur. Si oui, cela signifie que
     * l'admin était connecté lors de la dernière session et nous restaurons son état.
     * Sinon, nous initialisons avec null pour indiquer qu'aucun admin n'est connecté.
     */
    const storedAdmin = this.getStoredAdmin();
    this.currentAdminSubject = new BehaviorSubject<Admin | null>(storedAdmin);
    this.currentAdmin$ = this.currentAdminSubject.asObservable();
  }

  /**
   * Récupérer la valeur actuelle de l'admin connecté
   * 
   * Cette méthode getter nous permet d'accéder facilement à l'admin connecté
   * de manière synchrone sans avoir à s'abonner à l'Observable. C'est utile
   * quand nous avons juste besoin de vérifier rapidement qui est connecté.
   * 
   * @returns L'admin actuellement connecté ou null
   */
  public get currentAdminValue(): Admin | null {
    return this.currentAdminSubject.value;
  }

  /**
   * Connecter un admin avec email et mot de passe
   * 
   * Cette méthode envoie une requête POST à l'endpoint de login de l'API Laravel
   * avec les identifiants fournis. Si la connexion réussit, nous stockons le token
   * et les données de l'admin dans le localStorage et mettons à jour notre état.
   * 
   * L'utilisation d'Observable avec RxJS est le pattern standard dans Angular pour
   * gérer les opérations asynchrones comme les requêtes HTTP. Les composants qui
   * appellent cette méthode doivent s'abonner à l'Observable retourné pour recevoir
   * la réponse quand elle arrive.
   * 
   * @param email - L'email de l'admin
   * @param password - Le mot de passe de l'admin
   * @returns Un Observable qui émettra la réponse de l'API
   */
  login(email: string, password: string): Observable<LoginResponse> {
    // Log pour le débogage - vous verrez ceci dans la console du navigateur
    console.log('Tentative de connexion vers:', `${this.apiUrl}/login`);

    // Faire la requête POST vers l'API
    return this.http.post<LoginResponse>(
      `${this.apiUrl}/login`,
      { email, password }
    ).pipe(
      // L'opérateur tap nous permet d'exécuter du code avec la réponse
      // sans modifier la réponse elle-même
      tap(response => {
        // Vérifier que la réponse contient ce que nous attendons
        if (response.success && response.data.token) {
          // Stocker le token dans le localStorage pour persistance
          // Le localStorage est une API du navigateur qui garde les données
          // même quand l'utilisateur ferme et rouvre le navigateur
          localStorage.setItem('auth_token', response.data.token);
          
          // Stocker les données de l'admin en JSON
          localStorage.setItem('current_admin', JSON.stringify(response.data.admin));
          
          // Mettre à jour notre BehaviorSubject pour notifier tous les abonnés
          this.currentAdminSubject.next(response.data.admin);
          
          console.log('Connexion réussie, token stocké');
        }
      }),
      // L'opérateur catchError nous permet de gérer les erreurs
      catchError(error => {
        console.error('Erreur lors de la connexion:', error);
        // Retourner l'erreur sous forme d'Observable pour que le composant
        // puisse aussi la gérer et afficher un message approprié à l'utilisateur
        return throwError(() => error);
      })
    );
  }

  /**
   * Déconnecter l'admin actuel
   * 
   * Cette méthode appelle l'endpoint de logout sur l'API (qui invalidera le token
   * côté serveur), puis nettoie toutes les données locales et navigue vers la page
   * de login.
   * 
   * @returns Un Observable qui émettra quand le logout est complet
   */
  logout(): Observable<any> {
    const token = this.getToken();
    
    // Faire la requête POST vers l'endpoint de logout si nous avons un token
    const logoutRequest = token 
      ? this.http.post(`${this.apiUrl}/logout`, {})
      : throwError(() => new Error('Pas de token'));

    return logoutRequest.pipe(
      // Que la requête réussisse ou échoue, nous nettoyons quand même
      tap(() => this.clearAuthData()),
      catchError(error => {
        // Même en cas d'erreur côté serveur, nous nettoyons localement
        this.clearAuthData();
        return throwError(() => error);
      })
    );
  }

  /**
   * Nettoyer toutes les données d'authentification
   * 
   * Cette méthode privée est appelée lors du logout pour supprimer toutes
   * les traces de la session de l'admin dans le navigateur et mettre à jour
   * notre état pour refléter qu'aucun admin n'est connecté.
   */
  private clearAuthData(): void {
    // Supprimer le token et les données admin du localStorage
    localStorage.removeItem('auth_token');
    localStorage.removeItem('current_admin');
    
    // Mettre à jour le BehaviorSubject pour notifier qu'aucun admin n'est connecté
    this.currentAdminSubject.next(null);
    
    // Naviguer vers la page de login
    this.router.navigate(['/login']);
    
    console.log('Données d\'authentification nettoyées');
  }

  /**
   * Récupérer le profil de l'admin connecté depuis l'API
   * 
   * Cette méthode est utile pour rafraîchir les données de l'admin depuis
   * le serveur, par exemple après avoir modifié le profil.
   * 
   * @returns Un Observable qui émettra les données de l'admin
   */
  getProfile(): Observable<Admin> {
    return this.http.get<{success: boolean; data: Admin}>(
      `${this.apiUrl}/me`
    ).pipe(
      map(response => response.data),
      tap(admin => {
        // Mettre à jour notre état local avec les nouvelles données
        localStorage.setItem('current_admin', JSON.stringify(admin));
        this.currentAdminSubject.next(admin);
      }),
      catchError(error => {
        console.error('Erreur lors de la récupération du profil:', error);
        // Si l'API retourne 401, cela signifie que le token est invalide ou expiré
        if (error.status === 401) {
          this.clearAuthData();
        }
        return throwError(() => error);
      })
    );
  }

  /**
   * Récupérer le token stocké dans le localStorage
   * 
   * @returns Le token ou null s'il n'existe pas
   */
  getToken(): string | null {
    return localStorage.getItem('auth_token');
  }

  /**
   * Vérifier si un admin est actuellement connecté
   * 
   * Cette méthode vérifie simplement si nous avons un token stocké.
   * Une vérification plus robuste serait de vérifier aussi que le token
   * n'est pas expiré, mais cela nécessiterait de décoder le JWT.
   * 
   * @returns true si un token existe, false sinon
   */
  isLoggedIn(): boolean {
    return !!this.getToken();
  }

  /**
   * Récupérer les données de l'admin stockées dans le localStorage
   * 
   * Cette méthode privée est appelée lors de l'initialisation du service
   * pour restaurer l'état de l'admin si il était connecté.
   * 
   * @returns Les données de l'admin ou null
   */
  private getStoredAdmin(): Admin | null {
    const adminJson = localStorage.getItem('current_admin');
    if (adminJson) {
      try {
        return JSON.parse(adminJson);
      } catch (error) {
        console.error('Erreur lors du parsing des données admin:', error);
        // Si les données sont corrompues, les supprimer
        localStorage.removeItem('current_admin');
        return null;
      }
    }
    return null;
  }

  /**
   * Alias de isLoggedIn() pour compatibilité avec les guards
   * 
   * Certains guards dans votre application cherchent cette méthode au lieu de isLoggedIn().
   * Plutôt que de changer tous les guards, nous créons simplement un alias qui appelle
   * la méthode existante. C'est une solution élégante qui maintient la compatibilité.
   */
  isAuthenticated(): boolean {
    return this.isLoggedIn();
  }

  /**
   * Alias de currentAdminValue pour compatibilité avec les composants
   * 
   * Certains composants cherchent currentUserValue au lieu de currentAdminValue.
   * Nous créons un getter qui retourne la même chose pour maintenir la compatibilité.
   */
  get currentUserValue(): Admin | null {
    return this.currentAdminValue;
  }

  /**
   * Propriété token pour compatibilité avec d'autres services
   * 
   * Le service WebSocket dans votre application cherche une propriété token directement.
   * Nous exposons le token via un getter pour que d'autres services puissent y accéder.
   */
  get token(): string | null {
    return this.getToken();
  }

  /**
   * Vérifier si l'admin a un rôle spécifique
   * 
   * Cette méthode est utilisée par le RoleGuard pour vérifier les permissions.
   * Elle accepte soit un seul rôle soit un tableau de rôles et vérifie si l'admin
   * connecté a l'un de ces rôles.
   * 
   * @param roles - Un rôle ou un tableau de rôles autorisés
   * @returns true si l'admin a l'un des rôles, false sinon
   */
  hasRole(roles: string | string[]): boolean {
    const admin = this.currentAdminValue;
    if (!admin) return false;

    // Convertir en tableau si c'est une chaîne unique
    const rolesArray = Array.isArray(roles) ? roles : [roles];
    
    // Vérifier si le rôle de l'admin est dans la liste des rôles autorisés
    // On compare en minuscules pour être insensible à la casse
    return rolesArray.some(role => 
      role.toLowerCase() === admin.role.toLowerCase()
    );
  }

  /**
   * Obtenir le libellé français d'un rôle
   * 
   * Cette méthode traduit les codes de rôles en libellés lisibles par l'utilisateur
   * pour l'affichage dans l'interface. Elle est utilisée par les composants qui
   * affichent des informations sur les rôles.
   * 
   * @param role - Le code du rôle (ADMIN, GESTIONNAIRE, SUPERVISEUR)
   * @returns Le libellé français du rôle
   */
  getRoleLabel(role: 'ADMIN' | 'GESTIONNAIRE' | 'SUPERVISEUR'): string {
    const labels: Record<string, string> = {
      'ADMIN': 'Administrateur',
      'GESTIONNAIRE': 'Gestionnaire',
      'SUPERVISEUR': 'Superviseur'
    };
    
    return labels[role] || role;
  }
}