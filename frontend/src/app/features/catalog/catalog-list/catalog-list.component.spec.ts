import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { CatalogListComponent } from './catalog-list.component';

describe('CatalogListComponent', () => {
  let component: CatalogListComponent;
  let fixture: ComponentFixture<CatalogListComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CatalogListComponent, RouterModule.forRoot([]), FormsModule]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CatalogListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should filter courses by role', () => {
  component.courses = [
    {
      id: 1,
      title: 'Cours Administrateur',
      description: 'Description test',
      role: 'administrator'
    } as any,
    {
      id: 2,
      title: 'Cours Développeur',
      description: 'Description test',
      role: 'developer'
    } as any
  ];

  component.filters.role = 'administrator';

  expect(component.filteredCourses.length).toBe(1);
  expect(component.filteredCourses[0].role).toBe('administrator');
  });

  it('should reset filters', () => {
    component.filters.role = 'developer';
    component.resetFilters();
    expect(component.filters.role).toBe('');
  });
});

