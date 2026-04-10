import { Component, inject, signal } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-etudiant-layout',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './layout.html',
  styleUrl: './layout.css'
})
export class EtudiantLayout {
  private auth = inject(AuthService);
  user = typeof window !== 'undefined' ? JSON.parse(localStorage.getItem('user') || '{}') : {};
  sidebarOpen = signal(true);

  toggleSidebar() { this.sidebarOpen.update(v => !v); }
  logout()        { this.auth.logout(); }

  readonly menu = [
    { path: 'dashboard',      label: 'Tableau de bord',   icon: '⊞' },
    { path: 'theme',          label: 'Soumettre un thème', icon: '💡' },
    { path: 'rapport',        label: 'Déposer un rapport', icon: '📤' },
    { path: 'suivi',          label: 'Mes demandes',       icon: '📋' },
    { path: 'plagiat',        label: 'Résultat plagiat',   icon: '🔍' },
    { path: 'note',           label: 'Ma note',            icon: '🎯' },
    { path: 'notifications',  label: 'Notifications',      icon: '🔔' },
  ];
}
