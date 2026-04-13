import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ThemeService, Theme } from '../../../core/services/theme.service';

@Component({
  selector: 'app-chef-themes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './themes.html',
  styleUrl: './themes.css'
})
export class ChefThemes implements OnInit {
  private svc = inject(ThemeService);

  themes = signal<Theme[]>([]);
  loading = signal(false);
  
  selectedTheme = signal<Theme | null>(null);
  similarity = signal<{ score: number; titre: string } | null>(null);
  checkingSimilarity = signal(false);

  showRejectModal = signal(false);
  motifRejet = '';

  message = signal<{ type: 'success' | 'error', text: string } | null>(null);

  ngOnInit() {
    this.chargerThemes();
  }

  chargerThemes() {
    this.loading.set(true);
    this.svc.getThemesChef().subscribe({
      next: (data) => {
        this.themes.set(data);
        this.loading.set(false);
      },
      error: () => this.loading.set(false)
    });
  }

  checkSimilarity(t: Theme) {
    this.selectedTheme.set(t);
    this.checkingSimilarity.set(true);
    this.similarity.set(null);
    
    this.svc.checkSimilarity(t.id).subscribe({
      next: (res) => {
        this.similarity.set(res);
        this.checkingSimilarity.set(false);
      },
      error: () => this.checkingSimilarity.set(false)
    });
  }

  valider(t: Theme) {
    if (!confirm('Voulez-vous valider ce thème et le transmettre à l\'Admin ?')) return;
    
    this.svc.validerChef(t.id).subscribe({
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

    this.svc.rejeterChef(theme.id, this.motifRejet).subscribe({
      next: () => {
        this.message.set({ type: 'success', text: 'Thème rejeté avec succès.' });
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
