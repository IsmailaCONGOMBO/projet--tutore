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
    { path: '/admin/dashboard', icon: 'analytics', label: 'Tableau de bord' },
    { path: '/admin/utilisateurs', icon: 'group', label: 'Utilisateurs' },
    { path: '/admin/validation-themes', icon: 'fact_check', label: 'Validation des thèmes' },
    { path: '/admin/validation-notes', icon: 'rule', label: 'Validation des notes' },
    { path: '/admin/rapports-corrections', icon: 'inventory', label: 'Rapports & Corrections' },
    { path: '/admin/notifications', icon: 'notifications', label: 'Notifications' },
    { path: '/admin/statistiques', icon: 'show_chart', label: 'Statistiques' },
    { path: '/admin/parametres', icon: 'settings', label: 'Configuration' },
  ];

  toggleSidebar() {
    this.sidebarOpen.update(v => !v);
  }

  logout() {
    this.auth.logout();
  }
}
