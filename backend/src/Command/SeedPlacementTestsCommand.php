<?php

namespace App\Command;

use App\Entity\Course;
use App\Entity\PlacementTest;
use App\Entity\PlacementQuestion;
use App\Entity\PlacementAnswer;
use App\Entity\SyllabusModule;
use App\Entity\Lab;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-placement-tests',
    description: 'Crée une formation complète (cours + programme + test de positionnement) pour tester.',
)]
class SeedPlacementTestsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $existing = $this->em->getRepository(PlacementTest::class)->findAll();
        if (count($existing) > 0) {
            $io->note('Il y a déjà ' . count($existing) . ' test(s) de positionnement. Pas d\'ajout.');
            return Command::SUCCESS;
        }

        $course = new Course();
        $course->setTitle('Microsoft Azure Fundamentals (AZ-900)');
        $course->setCode('AZ-900');
        $course->setLevel('Débutant');
        $course->setDuration('2 jours');
        $course->setFormat('Classe virtuelle');
        $course->setAccessDelay('Sous 48h');
        $course->setPrice('890');
        $course->setRole('Administrateur, Développeur, Chef de projet');
        $course->setProduct('Microsoft Azure');
        $course->setLanguage('fr');
        $course->setNextDate(new \DateTime('+2 weeks'));
        $course->setDescription(
            'Cette formation vous permet de maîtriser les concepts fondamentaux du cloud et de Microsoft Azure. ' .
            'Idéale pour préparer la certification Microsoft Certified: Azure Fundamentals (AZ-900).'
        );
        $course->setCertification('Microsoft Certified: Azure Fundamentals (AZ-900)');
        $course->setPopular(true);
        $course->setObjectives([
            'Comprendre les concepts du cloud (IaaS, PaaS, SaaS)',
            'Découvrir les services Azure (compute, stockage, réseau)',
            'Identifier les options de sécurité et de conformité',
            'Préparer l\'examen AZ-900',
        ]);
        $course->setOutcomes([
            'Maîtriser le vocabulaire et les concepts Azure',
            'Choisir les services adaptés à un cas d\'usage',
            'Être prêt à passer la certification AZ-900',
        ]);
        $course->setPrerequisites([
            'Aucun prérequis technique',
            'Curiosité pour le cloud et l\'informatique',
        ]);
        $course->setTargetRoles([
            'Administrateurs système',
            'Développeurs',
            'Chefs de projet IT',
        ]);
        $this->em->persist($course);

        $m1 = new SyllabusModule();
        $m1->setTitle('Découverte du cloud et d\'Azure');
        $m1->setDescription('Concepts cloud, modèles de déploiement, régions et abonnements Azure.');
        $m1->setOrderIndex(0);
        $m1->setCourse($course);
        $course->addSyllabus($m1);
        $this->em->persist($m1);

        $lab1 = new Lab();
        $lab1->setName('Explorer le portail Azure');
        $lab1->setDuration('30 min');
        $lab1->setModule($m1);
        $m1->addLab($lab1);
        $this->em->persist($lab1);

        $m2 = new SyllabusModule();
        $m2->setTitle('Services Azure : compute et stockage');
        $m2->setDescription('Machines virtuelles, App Services, Stockage Azure (blob, file, queue).');
        $m2->setOrderIndex(1);
        $m2->setCourse($course);
        $course->addSyllabus($m2);
        $this->em->persist($m2);

        $lab2 = new Lab();
        $lab2->setName('Créer un compte de stockage et un conteneur');
        $lab2->setDuration('45 min');
        $lab2->setModule($m2);
        $m2->addLab($lab2);
        $this->em->persist($lab2);

        $m3 = new SyllabusModule();
        $m3->setTitle('Réseau, sécurité et gouvernance');
        $m3->setDescription('Réseau virtuel, Azure AD, rôles RBAC, Policy et Blueprints.');
        $m3->setOrderIndex(2);
        $m3->setCourse($course);
        $course->addSyllabus($m3);
        $this->em->persist($m3);

        $lab3 = new Lab();
        $lab3->setName('Configurer un groupe de ressources et des tags');
        $lab3->setDuration('20 min');
        $lab3->setModule($m3);
        $m3->addLab($lab3);
        $this->em->persist($lab3);

        $this->em->flush();

        $test = new PlacementTest();
        $test->setCourse($course);
        $test->setTitle('Test de positionnement AZ-900');
        $test->setDescription('Répondez à ces questions pour évaluer votre niveau avant la formation. Le score minimum pour valider est 70%.');
        $test->setPassingScore(70);
        $test->setTimeLimit(15);
        $test->setIsActive(true);
        $course->setPlacementTest($test);
        $this->em->persist($test);

        $q1 = new PlacementQuestion();
        $q1->setPlacementTest($test);
        $q1->setQuestion('Qu\'est-ce que Microsoft Azure ?');
        $q1->setExplanation('Azure est la plateforme cloud de Microsoft (IaaS, PaaS, SaaS).');
        $q1->setOrderIndex(0);
        $test->addQuestion($q1);
        $this->em->persist($q1);

        $a1 = new PlacementAnswer();
        $a1->setQuestion($q1);
        $a1->setText('Une plateforme cloud publique');
        $a1->setScore('1.00');
        $a1->setIsCorrect(true);
        $a1->setOrderIndex(0);
        $q1->addAnswer($a1);
        $this->em->persist($a1);

        $a2 = new PlacementAnswer();
        $a2->setQuestion($q1);
        $a2->setText('Un langage de programmation');
        $a2->setScore('0.00');
        $a2->setIsCorrect(false);
        $a2->setOrderIndex(1);
        $q1->addAnswer($a2);
        $this->em->persist($a2);

        $q2 = new PlacementQuestion();
        $q2->setPlacementTest($test);
        $q2->setQuestion('Quel service Azure permet d\'exécuter du code sans gérer de serveur ?');
        $q2->setExplanation('Azure Functions est un service serverless (sans serveur).');
        $q2->setOrderIndex(1);
        $test->addQuestion($q2);
        $this->em->persist($q2);

        $a3 = new PlacementAnswer();
        $a3->setQuestion($q2);
        $a3->setText('Azure Functions');
        $a3->setScore('1.00');
        $a3->setIsCorrect(true);
        $a3->setOrderIndex(0);
        $q2->addAnswer($a3);
        $this->em->persist($a3);

        $a4 = new PlacementAnswer();
        $a4->setQuestion($q2);
        $a4->setText('Une machine virtuelle Azure');
        $a4->setScore('0.00');
        $a4->setIsCorrect(false);
        $a4->setOrderIndex(1);
        $q2->addAnswer($a4);
        $this->em->persist($a4);

        $q3 = new PlacementQuestion();
        $q3->setPlacementTest($test);
        $q3->setQuestion('Où sont stockées les identités et les accès dans Azure ?');
        $q3->setExplanation('Azure Active Directory (Azure AD) gère les identités et les accès.');
        $q3->setOrderIndex(2);
        $test->addQuestion($q3);
        $this->em->persist($q3);

        $a5 = new PlacementAnswer();
        $a5->setQuestion($q3);
        $a5->setText('Azure Active Directory');
        $a5->setScore('1.00');
        $a5->setIsCorrect(true);
        $a5->setOrderIndex(0);
        $q3->addAnswer($a5);
        $this->em->persist($a5);

        $a6 = new PlacementAnswer();
        $a6->setQuestion($q3);
        $a6->setText('Le portail Azure uniquement');
        $a6->setScore('0.00');
        $a6->setIsCorrect(false);
        $a6->setOrderIndex(1);
        $q3->addAnswer($a6);
        $this->em->persist($a6);

        $this->em->flush();

        $io->success([
            'Formation complète créée : Microsoft Azure Fundamentals (AZ-900)',
            '– 1 cours avec objectifs, prérequis, programme (3 modules, 3 labs)',
            '– 1 test de positionnement (3 questions, 15 min, score min 70 %)',
            'Rafraîchis la page Formations pour voir le cours, puis clique sur « Tester » pour faire le test.',
        ]);

        return Command::SUCCESS;
    }
}
