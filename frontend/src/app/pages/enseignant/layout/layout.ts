import { Component, inject, signal } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-enseignant-layout',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './layout.html',
  styleUrl: './layout.css'
})
export class EnseignantLayout {
  private auth = inject(AuthService);
  user = this.auth.getUser();
  sidebarOpen = signal(true);

  toggleSidebar() { this.sidebarOpen.update(v => !v); }
  logout()        { this.auth.logout(); }

  readonly menu = [
    { path: 'dashboard',  label: 'Tableau de bord',        icon: '⊞' },
    { path: 'rapports',   label: 'Rapports assignés',      icon: '📄' },
    { path: 'archives',   label: 'Rapports archivés',      icon: '📋' },
    { path: 'notes',      label: 'Attribution de notes',   icon: '⭐' },
  ];
}
