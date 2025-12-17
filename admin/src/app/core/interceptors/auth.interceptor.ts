// src/app/core/interceptors/auth.interceptor.ts

import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { AuthService } from '../services/auth.service';

/**
 * Intercepteur fonctionnel pour gérer l'authentification
 * 
 * Dans Angular moderne, les intercepteurs sont des fonctions plutôt que des classes.
 * Cette approche est plus simple et plus légère. La fonction reçoit la requête HTTP
 * et le handler suivant, et retourne un Observable de la réponse.
 * 
 * La fonction inject() est une nouvelle API d'Angular qui permet d'injecter des
 * dépendances directement dans une fonction plutôt que dans un constructeur de classe.
 */


export const authInterceptor: HttpInterceptorFn = (req, next) => {
  // Injecter les services nécessaires avec la nouvelle API inject()
  const authService = inject(AuthService);
  const router = inject(Router);

  // Récupérer le token d'authentification
  const token = authService.getToken();
  
  // Cloner la requête pour ajouter le header Authorization si un token existe
  let authReq = req;
  if (token) {
    authReq = req.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`
      }
    });
    
    console.log('🔐 Requête interceptée avec token:', req.url);
  }

  // Passer la requête au handler suivant et gérer les erreurs
  return next(authReq).pipe(
    catchError((error: HttpErrorResponse) => {
      // Gestion des erreurs 401 Unauthorized (token expiré ou invalide)
      if (error.status === 401 && !req.url.includes('/auth/admin/login')) {
        console.warn('⚠️ Token expiré ou invalide, redirection vers login');
        
        // Nettoyer les données d'authentification
        localStorage.removeItem('auth_token');
        localStorage.removeItem('current_admin');
        
        // Rediriger vers la page de login
        router.navigate(['/login'], {
          queryParams: { returnUrl: router.url }
        });
      }

      // Logger les autres types d'erreurs pour faciliter le débogage
      if (error.status === 0) {
        console.error('❌ Impossible de joindre le serveur. Vérifiez que Laravel tourne sur http://localhost:8000');
      } else if (error.status === 403) {
        console.error('🚫 Accès interdit');
      } else if (error.status === 500) {
        console.error('💥 Erreur serveur');
      }

      // Propager l'erreur
      return throwError(() => error);
    })
  );
};