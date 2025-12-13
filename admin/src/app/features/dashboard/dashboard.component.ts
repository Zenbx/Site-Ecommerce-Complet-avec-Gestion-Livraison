import { Component, OnInit } from '@angular/core';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit {
  userName: string = '';
  userEmail: string = '';
  userRole: string = '';

  constructor(private authService: AuthService) {}

  ngOnInit(): void {
    const user = this.authService.currentUserValue;
    if (user) {
      this.userName = user.name;
      this.userEmail = user.email;
      this.userRole = user.role.toUpperCase();
    }
  }

  logout(): void {
    this.authService.logout();
  }
}