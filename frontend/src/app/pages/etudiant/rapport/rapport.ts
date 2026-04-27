import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RapportService, Rapport } from '../../../core/services/rapport.service';
import { ThemeService, Theme } from '../../../core/services/theme.service';

@Component({
  selector: 'app-etudiant-rapport',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './rapport.html',
  styleUrl: './rapport.css'
})
export class EtudiantRapport implements OnInit {
  private rapportSvc = inject(RapportService);
  private themeSvc = inject(ThemeService);

  rapports = signal<Rapport[]>([]);
  themes = signal<Theme[]>([]);
  loading = signal(false);
  
  // Formulaire Soumission
  selectedThemeId = 0;
  selectedFile: File | null = null;
  titre = '';

  // Mode Test (Volatile)
  testFile: File | null = null;
  testResult = signal<{ taux: number; status: string } | null>(null);
  testReportHtml = signal('');
  showTestDetails = signal(false);
  testing = signal(false);

  message = signal<{ type: 'success' | 'error', text: string } | null>(null);

  ngOnInit() {
    this.chargerDonnees();
  }

  chargerDonnees() {
    this.loading.set(true);
    this.rapportSvc.getMes().subscribe(data => this.rapports.set(data));
    this.themeSvc.getMes().subscribe(data => {
      // Uniquement les thèmes validés définitivement peuvent accueillir un rapport
      this.themes.set(data.filter(t => t.statut === 'VALIDE_ADMIN'));
      this.loading.set(false);
    });
  }

  onFileSelected(event: any, isTest: boolean = false) {
    const file = event.target.files[0];
    if (isTest) this.testFile = file;
    else this.selectedFile = file;
  }

  lancerTest() {
    if (!this.testFile) return;
    this.testing.set(true);
    this.testResult.set(null);

    const fd = new FormData();
    fd.append('fichier', this.testFile);

    this.rapportSvc.testerRapport(fd).subscribe({
      next: (res) => {
        this.testResult.set({ 
          taux: res.result.taux_global, 
          status: res.result.decision 
        });
        this.testReportHtml.set(res.html_report);
        this.testing.set(false);
      },
      error: (err) => {
        console.error('Erreur test rapide', err);
        this.message.set({ 
          type: 'error', 
          text: err.error?.message || 'L\'analyse a échoué. Vérifiez la taille du PDF (max 20Mo).' 
        });
        this.testing.set(false);
      }
    });
  }

  soumettre() {
    if (!this.selectedFile || !this.titre) return;
    this.loading.set(true);

    const fd = new FormData();
    fd.append('fichier', this.selectedFile);
    fd.append('titre', this.titre);
    if (this.selectedThemeId) fd.append('theme_id', this.selectedThemeId.toString());

    this.rapportSvc.soumettreRapport(fd).subscribe({
      next: () => {
        this.message.set({ type: 'success', text: 'Rapport soumis officiellement !' });
        this.chargerDonnees();
        this.resetForm();
      },
      error: (e) => {
        this.message.set({ type: 'error', text: e.error?.message || 'Erreur lors du dépôt.' });
        this.loading.set(false);
      }
    });
  }

  resetForm() {
    this.selectedFile = null;
    this.titre = '';
    this.selectedThemeId = 0;
  }

  getStatusClass(s: string): string {
    if (s.includes('VALIDE')) return 'status-success';
    if (s.includes('REJETE')) return 'status-danger';
    return 'status-warning';
  }
}
