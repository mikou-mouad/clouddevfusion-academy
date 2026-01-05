import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FaqComponent } from './faq.component';

describe('FaqComponent', () => {
  let component: FaqComponent;
  let fixture: ComponentFixture<FaqComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FaqComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FaqComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should toggle faq', () => {
    const faq = component.faqs[0];
    expect(faq.open).toBe(false);
    component.toggleFaq(faq);
    expect(faq.open).toBe(true);
  });

  it('should filter faqs by category', () => {
    component.selectedCategory = 'Financement';
    expect(component.filteredFaqs.length).toBe(1);
    expect(component.filteredFaqs[0].category).toBe('Financement');
  });
});

