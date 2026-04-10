import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SlicePipe } from '@angular/common';
import { ThemeChefService, ThemeChef } from '../../../core/services/theme-chef.service';

@Component({
  selector: 'app-chef-unicite',
  imports: [FormsModule, SlicePipe],
  templateUrl: './unicite.html',
  styleUrl: './unicite.css'
})
export class ChefUnicite {
  private svc = inject(ThemeChefService);

  motCle     = '';
  resultats  = signal<ThemeChef[] | null>(null);
  loading    = signal(false);
  error      = signal('');

  rechercher() {
    if (!this.motCle.trim()) { this.error.set('Saisissez un mot-clé.'); return; }
    this.loading.set(true);
    this.error.set('');
    this.resultats.set(null);
    this.svc.rechercher(this.motCle).subscribe({
      next: (r) => { this.resultats.set(r); this.loading.set(false); },
      error: ()  => { this.error.set('Erreur lors de la recherche.'); this.loading.set(false); }
    });
  }
}
