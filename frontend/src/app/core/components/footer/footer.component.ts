import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';


@Component({
    selector: 'app-footer',
    imports: [RouterLink],
    templateUrl: './footer.component.html',
    styleUrls: ['./footer.component.scss']
})
export class FooterComponent {
  currentYear = new Date().getFullYear();
  socialLinks = {
    linkedin: 'https://www.linkedin.com/company/94102030',
    youtube: 'https://www.youtube.com/@CloudDevFusion'
  }
}

