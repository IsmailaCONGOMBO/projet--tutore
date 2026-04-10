import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RapportService } from '../../../core/services/rapport.service';

@Component({
  selector: 'app-rapport',
  imports: [FormsModule],
  templateUrl: './rapport.html',
  styleUrl: './rapport.css'
})
export class EtudiantRapport {
  private svc = inject(RapportService);

  fichier: File | null = null;
  titre    = '';
  loading  = signal(false);
  success  = signal('');
  error    = signal('');
  dragOver = signal(false);

  onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    if (input.files?.[0]) this.setFile(input.files[0]);
  }

  onDrop(e: DragEvent) {
    e.preventDefault();
    this.dragOver.set(false);
    const f = e.dataTransfer?.files[0];
    if (f && f.type === 'application/pdf') this.setFile(f);
    else this.error.set('Seuls les fichiers PDF sont acceptés.');
  }

  private setFile(f: File) {
    if (f.type !== 'application/pdf') { this.error.set('Seuls les fichiers PDF sont acceptés.'); return; }
    if (f.size > 20 * 1024 * 1024)   { this.error.set('Fichier trop volumineux (max 20 Mo).'); return; }
    this.fichier = f;
    this.error.set('');
  }

  get fileSize(): string {
    if (!this.fichier) return '';
    const kb = this.fichier.size / 1024;
    return kb > 1024 ? `${(kb/1024).toFixed(1)} Mo` : `${kb.toFixed(0)} Ko`;
  }

  submit() {
    if (!this.fichier) { this.error.set('Veuillez sélectionner un fichier PDF.'); return; }
    this.loading.set(true);
    this.error.set('');
    const form = new FormData();
    form.append('fichier', this.fichier);
    if (this.titre) form.append('titre', this.titre);

    this.svc.deposer(form).subscribe({
      next: () => {
        this.loading.set(false);
        this.success.set('Rapport déposé avec succès ! Il sera analysé sous peu.');
        this.fichier = null;
        this.titre = '';
        setTimeout(() => this.success.set(''), 5000);
      },
      error: (e: any) => {
        this.loading.set(false);
        this.error.set(e.error?.message || 'Erreur lors du dépôt.');
      }
    });
  }
}
