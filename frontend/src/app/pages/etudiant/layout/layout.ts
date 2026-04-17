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
    { path: 'dashboard',      label: 'Tableau de bord',   icon: 'dashboard' },
    { path: 'theme',          label: 'Soumettre un thème', icon: 'lightbulb' },
    { path: 'rapport',        label: 'Déposer un rapport', icon: 'upload_file' },
    { path: 'suivi',          label: 'Mes demandes',       icon: 'assignment' },
    { path: 'plagiat',        label: 'Résultat plagiat',   icon: 'find_in_page' },
    { path: 'note',           label: 'Ma note',            icon: 'stars' },
    { path: 'notifications',  label: 'Notifications',      icon: 'notifications' },
  ];
}
