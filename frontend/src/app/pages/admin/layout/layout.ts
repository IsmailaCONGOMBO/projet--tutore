import { Component, computed, signal, inject } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-admin-layout',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './layout.html',
  styleUrl: './layout.css'
})
export class AdminLayout {
  private auth = inject(AuthService);
  user = this.isBrowser ? JSON.parse(localStorage.getItem('user') || '{}') : {};
  private get isBrowser() { return typeof window !== 'undefined'; }

  sidebarOpen = signal(true);

  menu = [
    { path: '/admin/dashboard', icon: '📊', label: 'Tableau de bord' },
    { path: '/admin/utilisateurs', icon: '👥', label: 'Utilisateurs' },
    { path: '/admin/validation-themes', icon: '📝', label: 'Validation des thèmes' },
    { path: '/admin/validation-notes', icon: '✅', label: 'Validation des notes' },
    { path: '/admin/rapports-corrections', icon: '📚', label: 'Rapports & Corrections' },
    { path: '/admin/notifications', icon: '🔔', label: 'Notifications' },
    { path: '/admin/statistiques', icon: '📈', label: 'Statistiques' },
    { path: '/admin/parametres', icon: '⚙️', label: 'Configuration' },
  ];

  toggleSidebar() {
    this.sidebarOpen.update(v => !v);
  }

  logout() {
    this.auth.logout();
  }
}
