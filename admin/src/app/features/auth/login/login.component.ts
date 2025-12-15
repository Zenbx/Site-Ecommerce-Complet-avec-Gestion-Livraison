// src/app/features/auth/login/login.component.ts
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule
  ],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnInit {
  loginForm!: FormGroup;
  loading = false;
  submitted = false;
  error = '';
  returnUrl = '';

  // MODE TEST - Changez à false pour utiliser la vraie API
  TEST_MODE = true;

  constructor(
    private formBuilder: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    this.loginForm = this.formBuilder.group({
      email: ['admin@test.com', [Validators.required, Validators.email]],
      password: ['password123', [Validators.required, Validators.minLength(6)]]
    });

    this.returnUrl = this.route.snapshot.queryParams['returnUrl'] || '/dashboard';
  }

  get f() {
    return this.loginForm.controls;
  }

  onSubmit(): void {
    this.submitted = true;
    this.error = '';

    if (this.loginForm.invalid) {
      return;
    }

    this.loading = true;

    if (this.TEST_MODE) {
      // MODE TEST - Connexion simulée
      console.log('🧪 MODE TEST - Connexion simulée');
      setTimeout(() => {
        // Simuler un token et un utilisateur
        localStorage.setItem('auth_token', 'fake-test-token-12345');
        localStorage.setItem('current_user', JSON.stringify({
          id: 1,
          email: this.loginForm.value.email,
          name: 'Admin Test',
          role: 'admin'
        }));
        
        this.loading = false;
        this.router.navigate([this.returnUrl]);
      }, 500); // Petit délai pour simuler l'appel API
    } else {
      // MODE PRODUCTION - Vraie API
      this.authService.login(this.loginForm.value).subscribe({
        next: () => {
          this.router.navigate([this.returnUrl]);
        },
        error: (error) => {
          this.error = error.message || 'Identifiants incorrects';
          this.loading = false;
        }
      });
    }
  }
}