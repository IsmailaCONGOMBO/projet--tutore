import { Component, inject, signal } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-chef-layout',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './layout.html',
  styleUrl: './layout.css'
})
export class ChefLayout {
  private auth = inject(AuthService);
  user = typeof window !== 'undefined' ? JSON.parse(localStorage.getItem('user') || '{}') : {};
  sidebarOpen = signal(true);

  toggleSidebar() { this.sidebarOpen.update(v => !v); }
  logout()        { this.auth.logout(); }

  readonly menu = [
    { path: 'dashboard',  label: 'Tableau de bord',        icon: '⊞' },
    { path: 'themes',     label: 'Thèmes soumis',           icon: '💡' },
    { path: 'unicite',    label: 'Vérification d\'unicité', icon: '🔎' },
    { path: 'rapports',   label: 'Rapports déposés',        icon: '📄' },
    { path: 'historique', label: 'Historique décisions',    icon: '📋' },
  ];
}
