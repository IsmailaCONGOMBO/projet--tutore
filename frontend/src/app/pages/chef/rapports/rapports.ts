import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RapportService, Rapport } from '../../../core/services/rapport.service';
import { UserService } from '../../../core/services/user.service';

@Component({
  selector: 'app-chef-rapports',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './rapports.html',
  styleUrl: './rapports.css'
})
export class ChefRapports implements OnInit {
  private rapportSvc = inject(RapportService);
  private userSvc = inject(UserService);

  rapports = signal<Rapport[]>([]);
  enseignants = signal<any[]>([]);
  loading = signal(true);
  
  selectedRapport = signal<Rapport | null>(null);
  selectedEnseignantId = 0;

  ngOnInit() {
    this.chargerDonnees();
  }

  chargerDonnees() {
    this.loading.set(true);
    this.rapportSvc.getListTous().subscribe(data => {
      this.rapports.set(data);
      this.loading.set(false);
    });
    this.userSvc.getEnseignants().subscribe(data => this.enseignants.set(data));
  }

  analyser(id: number) {
    this.rapportSvc.analyserRapport(id).subscribe(() => this.chargerDonnees());
  }

  affecter() {
    const r = this.selectedRapport();
    if (!r || !this.selectedEnseignantId) return;
    this.rapportSvc.affecterEnseignant(r.id, this.selectedEnseignantId).subscribe(() => {
      this.chargerDonnees();
      this.selectedRapport.set(null);
    });
  }

  decisionFinale(id: number, decision: 'VALIDE_FINAL' | 'REJETE_FINAL') {
    this.rapportSvc.decisionFinale(id, decision).subscribe(() => this.chargerDonnees());
  }

  selectRapport(r: Rapport) {
    this.selectedRapport.set(r);
    this.selectedEnseignantId = r.enseignant?.user?.id || 0;
  }
}
