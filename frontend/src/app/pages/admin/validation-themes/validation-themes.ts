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

  validerFinal(t: Theme) {
    if (!confirm('Voulez-vous accorder la validation finale à ce thème ?')) return;
    
    this.svc.validerAdmin(t.id).subscribe({
      next: (res) => {
        this.message.set({ type: 'success', text: res.message });
        this.chargerThemes();
        this.selectedTheme.set(null);
      },
      error: (e) => {
        this.message.set({ type: 'error', text: e.error?.message || 'Erreur lors de la validation.' });
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
