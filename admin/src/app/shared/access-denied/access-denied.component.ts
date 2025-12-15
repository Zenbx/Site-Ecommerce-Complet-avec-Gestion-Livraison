// src/app/shared/components/access-denied/access-denied.component.ts
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, ActivatedRoute } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-access-denied',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="access-denied-container">
      <div class="access-denied-card">
        <div class="icon-container">
          <span class="icon">🚫</span>
        </div>
        <h1>Accès refusé</h1>
        <p class="message">
          Vous n'avez pas les permissions nécessaires pour accéder à cette page.
        </p>
        <div class="details" *ngIf="requiredRoles">
          <p class="detail-label">Rôles requis :</p>
          <div class="roles-list">
            <span *ngFor="let role of requiredRoles" class="role-badge">
              {{ getRoleLabel(role) }}
            </span>
          </div>
        </div>
        <div class="current-role" *ngIf="currentUser">
          <p class="detail-label">Votre rôle actuel :</p>
          <span class="role-badge current">{{ getRoleLabel(currentUser.role) }}</span>
        </div>
        <div class="actions">
          <button class="btn btn-primary" (click)="goToDashboard()">
            Retour au tableau de bord
          </button>
          <button class="btn btn-secondary" (click)="goBack()">
            Retour
          </button>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .access-denied-container {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: #f5f7fa;
      padding: 20px;
    }

    .access-denied-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      padding: 48px;
      max-width: 500px;
      width: 100%;
      text-align: center;
    }

    .icon-container {
      margin-bottom: 24px;
    }

    .icon {
      font-size: 80px;
      display: inline-block;
      animation: shake 0.5s;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }

    h1 {
      font-size: 32px;
      font-weight: 700;
      color: #1a202c;
      margin-bottom: 16px;
    }

    .message {
      font-size: 16px;
      color: #4a5568;
      margin-bottom: 32px;
      line-height: 1.6;
    }

    .details, .current-role {
      margin-bottom: 24px;
    }

    .detail-label {
      font-size: 14px;
      color: #718096;
      margin-bottom: 8px;
      font-weight: 500;
    }

    .roles-list {
      display: flex;
      gap: 8px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .role-badge {
      padding: 6px 16px;
      background: #e0e7ff;
      color: #4338ca;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
    }

    .role-badge.current {
      background: #fef3c7;
      color: #92400e;
    }

    .actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      margin-top: 32px;
    }

    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
      background: white;
      color: #4a5568;
      border: 1px solid #e2e8f0;
    }

    .btn-secondary:hover {
      background: #f7fafc;
    }

    @media (max-width: 768px) {
      .access-denied-card {
        padding: 32px 24px;
      }

      .actions {
        flex-direction: column;
      }

      .btn {
        width: 100%;
      }
    }
  `]
})
export class AccessDeniedComponent implements OnInit {
  requiredRoles: string[] = [];
  currentUser = this.authService.currentUserValue;

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private authService: AuthService
  ) {}

  ngOnInit(): void {
    const rolesParam = this.route.snapshot.queryParams['requiredRoles'];
    if (rolesParam) {
      this.requiredRoles = rolesParam.split(',');
    }
  }

  getRoleLabel(role: string): string {
    return this.authService.getRoleLabel(role as any);
  }

  goToDashboard(): void {
    this.router.navigate(['/dashboard']);
  }

  goBack(): void {
    window.history.back();
  }
}