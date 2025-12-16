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

  TEST_MODE = true;

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
        role: 'admin'
      };
      localStorage.setItem('auth_token', 'fake-token');
      localStorage.setItem('current_user', JSON.stringify(user));

      setTimeout(() => {
        this.loading = false;
        this.router.navigate([this.returnUrl]);
      }, 500);
    } else {
      this.authService.login(this.loginForm.value).subscribe({
        next: () => this.router.navigate([this.returnUrl]),
        error: err => {
          this.error = err.message || 'Identifiants incorrects';
          this.loading = false;
        }
      });
    }
  }
}
