import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ThemeService, Theme } from '../../../core/services/theme.service';

@Component({
  selector: 'app-admin-validation-themes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './validation-themes.html',
  styleUrl: './validation-themes.css'
})
export class AdminValidationThemes implements OnInit {
  private svc = inject(ThemeService);

  themes = signal<Theme[]>([]);
  loading = signal(false);
  selectedTheme = signal<Theme | null>(null);
  
  showRejectModal = signal(false);
  motifRejet = '';
  processingAction = signal(false);
  message = signal<{ type: 'success' | 'error', text: string } | null>(null);

  ngOnInit() {
    this.chargerThemes();
  }

  chargerThemes() {
    this.loading.set(true);
    this.svc.getThemesAdmin().subscribe({
      next: (data) => {
        this.themes.set(data);
        this.loading.set(false);
      },
      error: () => this.loading.set(false)
    });
  }

  valider(t: Theme) {
    this.validerFinal(t);
  }

  rejeter(t: Theme) {
    // Si on veut garder la logique de modal
    this.ouvrirRejet(t);
    // Ou si on veut un rejet rapide (pour le style premium sans modal complexe pour l'instant)
    // Pour l'instant on garde ouvrirRejet pour la sécurité
  }

  validerFinal(t: Theme) {
    if (!confirm('Voulez-vous accorder la validation finale à ce thème ?')) return;
    
    this.processingAction.set(true);
    this.svc.validerAdmin(t.id).subscribe({
      next: (res) => {
        this.message.set({ type: 'success', text: res.message });
        this.chargerThemes();
        this.selectedTheme.set(null);
        this.processingAction.set(false);
      },
      error: (e) => {
        this.message.set({ type: 'error', text: e.error?.message || 'Erreur lors de la validation.' });
        this.processingAction.set(false);
      }
    });
  }

  ouvrirRejet(t: Theme) {
    this.selectedTheme.set(t);
    this.motifRejet = '';
    this.showRejectModal.set(true);
  }

  confirmerRejet() {
    if (!this.motifRejet.trim()) return;
    const theme = this.selectedTheme();
    if (!theme) return;

    this.svc.rejeterAdmin(theme.id, this.motifRejet).subscribe({
      next: () => {
        this.message.set({ type: 'success', text: 'Thème rejeté définitivement.' });
        this.showRejectModal.set(false);
        this.chargerThemes();
        this.selectedTheme.set(null);
      },
      error: (e) => {
        this.message.set({ type: 'error', text: e.error?.message || 'Erreur lors du rejet.' });
      }
    });
  }
}
