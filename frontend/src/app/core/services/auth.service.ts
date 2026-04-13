import { Injectable, inject, PLATFORM_ID } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { tap } from 'rxjs/operators';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private http = inject(HttpClient);
  private router = inject(Router);
  private platformId = inject(PLATFORM_ID);
  private apiUrl = 'http://localhost:8000/api';
  private get isBrowser() { return isPlatformBrowser(this.platformId); }

  login(email: string, password: string) {
    return this.http.post<{ token: string; user: any }>(`${this.apiUrl}/login`, { email, password }).pipe(
      tap(res => {
        if (this.isBrowser) {
          localStorage.setItem('token', res.token);
          localStorage.setItem('user', JSON.stringify(res.user));
        }
      })
    );
  }

  logout() {
    if (this.isBrowser) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
    this.router.navigate(['/login']);
  }

  isLoggedIn(): boolean {
    return this.isBrowser && !!localStorage.getItem('token');
  }

  getToken(): string | null {
    return this.isBrowser ? localStorage.getItem('token') : null;
  }

  getUser(): any {
    return this.isBrowser ? JSON.parse(localStorage.getItem('user') || '{}') : {};
  }

  getRole(): string {
    return this.getUser()?.role ?? '';
  }

  redirectByRole() {
    const role = this.getRole();
    switch (role) {
      case 'etudiant':         this.router.navigate(['/etudiant/dashboard']);    break;
      case 'chef_departement': this.router.navigate(['/chef/dashboard']);        break;
      case 'enseignant':       this.router.navigate(['/enseignant/dashboard']); break;
      case 'admin':            this.router.navigate(['/admin/dashboard']);      break;
      default:                 this.router.navigate(['/admin/dashboard']);      break;
    }
  }
}
