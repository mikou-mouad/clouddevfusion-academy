import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RouterModule } from '@angular/router';
import { TrainingDescriptionComponent } from './training-description.component';

describe('TrainingDescriptionComponent', () => {
  let component: TrainingDescriptionComponent;
  let fixture: ComponentFixture<TrainingDescriptionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TrainingDescriptionComponent, RouterModule.forRoot([])]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TrainingDescriptionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have trainer certifications', () => {
    expect(component.trainerCertifications.length).toBeGreaterThan(0);
  });

  it('should have delivery formats', () => {
    expect(component.deliveryFormats.length).toBeGreaterThan(0);
  });

  it('should have quality KPIs', () => {
    expect(component.qualityKPIs.length).toBeGreaterThan(0);
  });
});

