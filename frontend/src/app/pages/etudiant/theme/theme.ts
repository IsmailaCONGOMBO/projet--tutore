import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ThemeService } from '../../../core/services/theme.service';

@Component({
  selector: 'app-theme',
  imports: [FormsModule],
  templateUrl: './theme.html',
  styleUrl: './theme.css'
})
export class EtudiantTheme {
  private svc = inject(ThemeService);

  titre       = '';
  description = '';
  loading     = signal(false);
  success     = signal('');
  error       = signal('');

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
        this.success.set('Thème soumis avec succès ! En attente de validation.');
        this.titre = '';
        this.description = '';
        setTimeout(() => this.success.set(''), 4000);
      },
      error: (e) => {
        this.loading.set(false);
        this.error.set(e.error?.message || 'Erreur lors de la soumission.');
      }
    });
  }
}
