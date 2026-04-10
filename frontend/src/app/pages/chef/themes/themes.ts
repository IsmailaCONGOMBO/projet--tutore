import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SlicePipe } from '@angular/common';
import { ThemeChefService, ThemeChef } from '../../../core/services/theme-chef.service';

@Component({
  selector: 'app-chef-themes',
  imports: [FormsModule, SlicePipe],
  templateUrl: './themes.html',
  styleUrl: './themes.css'
})
export class ChefThemes implements OnInit {
  private svc = inject(ThemeChefService);

  themes     = signal<ThemeChef[]>([]);
  loading    = signal(true);
  success    = signal('');
  error      = signal('');

  // Modal rejet
  showModal    = signal(false);
  motif        = '';
  themeEnCours = signal<ThemeChef | null>(null);
  submitting   = signal(false);

  ngOnInit() { this.load(); }

  load() {
    this.loading.set(true);
    this.svc.getThemes('EN_ATTENTE').subscribe({
      next: (t) => { this.themes.set(t); this.loading.set(false); },
      error: ()  => this.loading.set(false)
    });
  }

  valider(theme: ThemeChef) {
    this.svc.valider(theme.id).subscribe({
      next: () => {
        this.themes.update(list => list.filter(t => t.id !== theme.id));
        this.notify(`✅ Thème "${theme.titre}" validé.`);
      },
      error: (e) => this.error.set(e.error?.message || 'Erreur lors de la validation.')
    });
  }

  ouvrirRejet(theme: ThemeChef) {
    this.themeEnCours.set(theme);
    this.motif = '';
    this.error.set('');
    this.showModal.set(true);
  }

  confirmerRejet() {
    if (!this.motif.trim()) { this.error.set('Le motif est obligatoire.'); return; }
    const theme = this.themeEnCours()!;
    this.submitting.set(true);
    this.svc.rejeter(theme.id, this.motif).subscribe({
      next: () => {
        this.submitting.set(false);
        this.showModal.set(false);
        this.themes.update(list => list.filter(t => t.id !== theme.id));
        this.notify(`❌ Thème "${theme.titre}" rejeté.`);
      },
      error: (e) => {
        this.submitting.set(false);
        this.error.set(e.error?.message || 'Erreur lors du rejet.');
      }
    });
  }

  private notify(msg: string) {
    this.success.set(msg);
    setTimeout(() => this.success.set(''), 4000);
  }
}
