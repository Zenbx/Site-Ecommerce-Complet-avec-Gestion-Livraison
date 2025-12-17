import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnInit {
  loginForm!: FormGroup;
  loading = false;
  submitted = false;
  error = '';
  returnUrl = '';

  TEST_MODE = false;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]]
    });

    this.returnUrl = this.route.snapshot.queryParams['returnUrl'] || '/dashboard';
  }

  get f() { return this.loginForm.controls; }

 onSubmit(): void {
  this.submitted = true;
  this.error = '';

  if (this.loginForm.invalid) return;

  this.loading = true;

  if (this.TEST_MODE) {
    // Mode test simulé
    const user = {
      id: 1,
      email: this.loginForm.value.email,
      name: 'Admin Test',
      role: 'ADMIN'
    };
    localStorage.setItem('auth_token', 'fake-token');
    localStorage.setItem('current_admin', JSON.stringify(user));

    setTimeout(() => {
      this.loading = false;
      this.router.navigate([this.returnUrl]);
    }, 500);
  } else {
    // Mode réel avec API
    const { email, password } = this.loginForm.value;
    this.authService.login(email, password).subscribe({
      next: (response) => {
        console.log('✅ Connexion réussie:', response);
        this.router.navigate([this.returnUrl]);
      },
      error: (err) => {
        console.error('❌ Erreur de connexion:', err);
        this.error = err.error?.message || 'Identifiants incorrects';
        this.loading = false;
      }
    });
  }
}
}
