import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ThemeService, Theme } from '../../../core/services/theme.service';

@Component({
  selector: 'app-theme',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './theme.html',
  styleUrl: './theme.css'
})
export class EtudiantTheme implements OnInit {
  private svc = inject(ThemeService);

  titre       = '';
  description = '';
  loading     = signal(false);
  success     = signal('');
  error       = signal('');
  mesThemes   = signal<Theme[]>([]);

  ngOnInit() {
    this.chargerMesThemes();
  }

  chargerMesThemes() {
    this.svc.getMes().subscribe(data => this.mesThemes.set(data));
  }

  submit() {
    if (!this.titre.trim() || !this.description.trim()) {
      this.error.set('Veuillez remplir tous les champs.');
      return;
    }
    this.loading.set(true);
    this.error.set('');
    
    this.svc.soumettre({ titre: this.titre, description: this.description }).subscribe({
      next: () => {
        this.loading.set(false);
        this.success.set('Thème soumis avec succès ! En attente de validation par le Chef.');
        this.titre = '';
        this.description = '';
        this.chargerMesThemes();
        setTimeout(() => this.success.set(''), 4000);
      },
      error: (e) => {
        this.loading.set(false);
        this.error.set(e.error?.message || 'Erreur lors de la soumission.');
      }
    });
  }

  getStatusLabel(s: string): string {
    switch(s) {
      case 'EN_ATTENTE_CHEF': return 'En attente (Chef)';
      case 'VALIDE_CHEF':     return 'Validé (Chef) - En attente Admin';
      case 'REJETE_CHEF':     return 'Rejeté par le Chef';
      case 'VALIDE_ADMIN':    return 'Validé Définitivement';
      case 'REJETE_ADMIN':    return 'Rejeté par l\'Admin';
      default: return s;
    }
  }

  getStatusClass(s: string): string {
    if (s.startsWith('VALIDE')) return 'status-valide';
    if (s.startsWith('REJETE')) return 'status-rejete';
    return 'status-attente';
  }
}
