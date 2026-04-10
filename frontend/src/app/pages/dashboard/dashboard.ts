import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-dashboard',
  imports: [RouterLink],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class Dashboard {
  private auth = inject(AuthService);
  user = this.isBrowser ? JSON.parse(localStorage.getItem('user') || '{}') : {};
  private get isBrowser() { return typeof window !== 'undefined'; }

  logout() {
    this.auth.logout();
  }
}
