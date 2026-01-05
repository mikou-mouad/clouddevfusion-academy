import { Component } from '@angular/core';
import { RouterOutlet, Router, NavigationEnd } from '@angular/router';
import { HeaderComponent } from './core/components/header/header.component';
import { FooterComponent } from './core/components/footer/footer.component';
import { CommonModule, ViewportScroller } from '@angular/common';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, HeaderComponent, FooterComponent, CommonModule],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent {
  title = 'CloudAcademy By CloudDev Fusion';
  isAdminRoute = false;

  constructor(
    private router: Router,
    private viewportScroller: ViewportScroller
  ) {
    // Écouter les changements de route
    this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((event: any) => {
      this.isAdminRoute = event.url.startsWith('/admin');
      // Faire défiler vers le haut de la page lors de la navigation
      this.viewportScroller.scrollToPosition([0, 0]);
    });

    // Vérifier la route initiale
    this.isAdminRoute = this.router.url.startsWith('/admin');
  }
}

