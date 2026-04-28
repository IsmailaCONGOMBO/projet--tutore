import { Component, inject, signal, OnInit, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RapportService, Rapport } from '../../../core/services/rapport.service';
import { UserService } from '../../../core/services/user.service';
import { FiliereService, Filiere } from '../../../core/services/filiere.service';
import { PromotionService, Promotion } from '../../../core/services/promotion.service';

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
  private filiereSvc = inject(FiliereService);
  private promotionSvc = inject(PromotionService);

  rapports = signal<Rapport[]>([]);
  enseignants = signal<any[]>([]);
  filieres = signal<Filiere[]>([]);
  promotions = signal<Promotion[]>([]);
  loading = signal(true);
  
  filterFiliere = signal<number>(0);
  filterPromotion = signal<number>(0);

  filteredRapports = computed(() => {
    let data = this.rapports();
    if (this.filterFiliere() > 0) {
      data = data.filter(r => (r.etudiant as any)?.filiere_id == this.filterFiliere());
    }
    if (this.filterPromotion() > 0) {
      data = data.filter(r => (r.etudiant as any)?.promotion_id == this.filterPromotion());
    }
    return data;
  });

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
      
      const current = this.selectedRapport();
      if (current) {
        const updated = data.find(r => r.id === current.id);
        if (updated) this.selectedRapport.set(updated);
      }
    });
    this.userSvc.getEnseignants().subscribe(data => this.enseignants.set(data));
    this.filiereSvc.getFilieres().subscribe(data => this.filieres.set(data));
    this.promotionSvc.getPromotions().subscribe(data => this.promotions.set(data));
  }

  analyser(id: number) {
    this.rapportSvc.analyserRapport(id).subscribe(res => {
      // On recharge tout mais on met à jour immédiatement le statut pour débloquer l'UI
      this.chargerDonnees();
    });
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
    this.selectedEnseignantId = r.enseignant_id || 0;
  }
}
