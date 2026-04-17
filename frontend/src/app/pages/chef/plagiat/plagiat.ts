import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { RapportChefService } from '../../../core/services/rapport-chef.service';

export interface AnalyseRapport {
  rapport_id: number;
  titre: string;
  etudiant: { name: string; email: string };
  taux_plagiat: number;
  statut_rapport: 'EN_ATTENTE' | 'ACCEPTE' | 'REJETE';
  date_analyse: string;
  passages_suspects: { texte: string; source: string; similarite: number }[];
}

@Component({
  selector: 'app-chef-plagiat',
  imports: [RouterLink, FormsModule],
  templateUrl: './plagiat.html',
  styleUrl: './plagiat.css'
})
export class ChefPlagiat implements OnInit {
  private route = inject(ActivatedRoute);
  private svc   = inject(RapportChefService);

  rapportId    = signal<number>(0);
  analyse      = signal<AnalyseRapport | null>(null);
  loading      = signal(true);
  error        = signal('');

  showModal    = signal(false);
  typeDecision = signal<'ACCEPTE' | 'REJETE' | null>(null);
  motifRejet   = '';
  submitting   = signal(false);
  success      = signal('');
  decisionError = signal('');

  ngOnInit() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.rapportId.set(id);
    this.svc.getAnalyse(id).subscribe({
      next: (a) => { this.analyse.set(a); this.loading.set(false); },
      error: ()  => { this.loading.set(false); }
    });
  }

  get taux(): number { return this.analyse()?.taux_plagiat ?? 0; }

  get tauxColor(): string {
    if (this.taux < 20) return '#16a34a';
    if (this.taux < 40) return '#d97706';
    return '#dc2626';
  }

  get tauxLabel(): string {
    if (this.taux < 20) return 'Acceptable — Rapport recevable';
    if (this.taux < 40) return 'Attention — Vérification recommandée';
    return 'Taux élevé — Rapport à rejeter';
  }

  get tauxBg(): string {
    if (this.taux < 20) return '#f0fdf4';
    if (this.taux < 40) return '#fffbeb';
    return '#fef2f2';
  }

  ouvrirDecision(type: 'ACCEPTE' | 'REJETE') {
    this.typeDecision.set(type);
    this.motifRejet = '';
    this.decisionError.set('');
    this.showModal.set(true);
  }

  confirmerDecision() {
    if (this.typeDecision() === 'REJETE' && !this.motifRejet.trim()) {
      this.decisionError.set('Le motif de rejet est obligatoire.');
      return;
    }
    this.submitting.set(true);
    this.svc.decision(this.rapportId(), {
      type: this.typeDecision()!,
      motif: this.motifRejet || undefined
    }).subscribe({
      next: () => {
        this.submitting.set(false);
        this.showModal.set(false);
        const msg = this.typeDecision() === 'ACCEPTE'
          ? 'Rapport accepté avec succès.'
          : 'Rapport rejeté. L\'étudiant sera notifié.';
        this.success.set(msg);
        this.analyse.update(a => a ? { ...a, statut_rapport: this.typeDecision()! } : a);
      },
      error: (e) => {
        this.submitting.set(false);
        this.decisionError.set(e.error?.message || 'Erreur lors de la décision.');
      }
    });
  }
}
