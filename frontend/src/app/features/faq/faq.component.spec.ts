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
  const faq = {
    id: 1,
    category: 'Financement',
    question: 'Question test',
    answer: 'Réponse test',
    published: true,
    open: false
  };

  component.toggleFaq(faq);

  expect(faq.open).toBe(true);
  });

  it('should filter faqs by category', () => {
  component.faqs = [
    {
      id: 1,
      category: 'Prérequis',
      question: 'Question 1',
      answer: 'Réponse 1',
      published: true,
      open: false
    },
    {
      id: 2,
      category: 'Financement',
      question: 'Question 2',
      answer: 'Réponse 2',
      published: true,
      open: false
    }
  ];

  component.selectedCategory = 'Financement';

  expect(component.filteredFaqs.length).toBe(1);
  expect(component.filteredFaqs[0].category).toBe('Financement');
  });
});

