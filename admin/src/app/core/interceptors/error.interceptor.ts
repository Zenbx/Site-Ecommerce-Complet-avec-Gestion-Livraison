// src/app/core/interceptors/error.interceptor.ts

import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor,
  HttpErrorResponse
} from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { AuthService } from '../services/auth.service';
import { Router } from '@angular/router';

@Injectable()
export class ErrorInterceptor implements HttpInterceptor {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  intercept(
    request: HttpRequest<unknown>,
    next: HttpHandler
  ): Observable<HttpEvent<unknown>> {
    return next.handle(request).pipe(
      catchError((error: HttpErrorResponse) => {
        let errorMessage = 'Une erreur est survenue';

        if (error.error instanceof ErrorEvent) {
          // Erreur côté client
          errorMessage = `Erreur: ${error.error.message}`;
        } else {
          // Erreur côté serveur
          switch (error.status) {
            case 401:
              // Token expiré ou invalide
              this.authService.logout();
              this.router.navigate(['/auth/login']);
              errorMessage = 'Session expirée. Veuillez vous reconnecter.';
              break;
            case 403:
              errorMessage = 'Accès refusé';
              break;
            case 404:
              errorMessage = 'Ressource non trouvée';
              break;
            case 422:
              errorMessage = error.error.message || 'Données invalides';
              break;
            case 500:
              errorMessage = 'Erreur serveur';
              break;
            default:
              errorMessage = error.error.message || errorMessage;
          }
        }

        console.error(errorMessage, error);
        return throwError(() => new Error(errorMessage));
      })
    );
  }
}