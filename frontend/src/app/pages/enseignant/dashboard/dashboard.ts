import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../core/services/auth.service';
import { RapportService, Rapport } from '../../../core/services/rapport.service';

@Component({
  selector: 'app-enseignant-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class EnseignantDashboard implements OnInit {
  private auth = inject(AuthService);
  private rapportService = inject(RapportService);

  user = this.auth.getUser();
  nbAssignes = signal(0);
  nbCorriges = signal(0);
  nbNotes    = signal(0);

  ngOnInit() {
    this.rapportService.getRapportsAssignes().subscribe({
      next: (rapports: Rapport[]) => {
        this.nbAssignes.set(rapports.length);
        this.nbCorriges.set(rapports.filter(r => r.statut === 'CORRIGE').length);
        this.nbNotes.set(rapports.filter(r => r.statut === 'NOTE').length);
      }
    });
  }
}
