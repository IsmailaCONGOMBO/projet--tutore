import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ThemeChefService, StatsDashboard } from '../../../core/services/theme-chef.service';

@Component({
  selector: 'app-chef-dashboard',
  imports: [RouterLink],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class ChefDashboard implements OnInit {
  private svc = inject(ThemeChefService);
  user  = typeof window !== 'undefined' ? JSON.parse(localStorage.getItem('user') || '{}') : {};
  stats = signal<StatsDashboard>({ en_attente: 0, valides: 0, rejetes: 0, total: 0 });
  loading = signal(true);

  ngOnInit() {
    this.svc.getStats().subscribe({
      next: (s) => { this.stats.set(s); this.loading.set(false); },
      error: ()  => this.loading.set(false)
    });
  }
}
