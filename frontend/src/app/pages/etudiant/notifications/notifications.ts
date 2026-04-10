import { Component, inject, signal, OnInit } from '@angular/core';
import { SlicePipe } from '@angular/common';
import { NotificationService, Notification } from '../../../core/services/notification.service';

@Component({
  selector: 'app-notifications',
  imports: [SlicePipe],
  templateUrl: './notifications.html',
  styleUrl: './notifications.css'
})
export class EtudiantNotifications implements OnInit {
  private svc = inject(NotificationService);

  notifs   = signal<Notification[]>([]);
  loading  = signal(true);

  get nbNonLus() { return this.notifs().filter(n => !n.lu).length; }

  ngOnInit() {
    this.svc.getMes().subscribe({
      next: (n: Notification[]) => { this.notifs.set(n); this.loading.set(false); },
      error: ()  => this.loading.set(false)
    });
  }

  marquerLu(n: Notification) {
    if (n.lu) return;
    this.svc.marquerLu(n.id).subscribe(() => {
      this.notifs.update(list => list.map(x => x.id === n.id ? { ...x, lu: true } : x));
    });
  }

  marquerTous() {
    this.svc.marquerTousLus().subscribe(() => {
      this.notifs.update(list => list.map(x => ({ ...x, lu: true })));
    });
  }
}
