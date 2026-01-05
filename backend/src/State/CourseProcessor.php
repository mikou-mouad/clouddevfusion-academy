<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Course;
use App\Entity\Lab;
use App\Entity\SyllabusModule;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CourseProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?AuditLogService $auditLogService = null,
        private ?Security $security = null
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Course
    {
        /** @var Course $course */
        $course = $data;

        // Convertir le prix en string si c'est un nombre (DECIMAL attend une string)
        // S'assurer que le prix n'est jamais null (utiliser '0' par défaut)
        if ($course->getPrice() === null) {
            $course->setPrice('0');
        } elseif (is_numeric($course->getPrice())) {
            $course->setPrice((string) $course->getPrice());
        }
        
        // S'assurer que product n'est jamais null (utiliser une valeur par défaut)
        if ($course->getProduct() === null || $course->getProduct() === '') {
            $course->setProduct('azure-administrator');
        }

        // Si c'est une mise à jour, charger l'entité existante et supprimer les anciens modules
        if (isset($uriVariables['id'])) {
            $existingCourse = $this->entityManager->getRepository(Course::class)->find($uriVariables['id']);
            if ($existingCourse) {
                // Supprimer les anciens modules et leurs labs
                foreach ($existingCourse->getSyllabus() as $module) {
                    foreach ($module->getLabs() as $lab) {
                        $this->entityManager->remove($lab);
                    }
                    $this->entityManager->remove($module);
                }
                $this->entityManager->flush();
                
                // Le createdAt est géré automatiquement par Doctrine, pas besoin de le copier
                // On garde juste l'entité existante pour référence si nécessaire
            }
        }

        // Traiter les modules du syllabus depuis la collection
        // API Platform désérialise automatiquement le JSON en objets SyllabusModule
        $syllabusCollection = $course->getSyllabus();
        $syllabusArray = $syllabusCollection->toArray();
        
        // Vider la collection pour reconstruire proprement
        $course->getSyllabus()->clear();

        // Traiter le syllabus seulement s'il contient des données
        if (!empty($syllabusArray)) {
            foreach ($syllabusArray as $index => $moduleData) {
            $module = null;
            
            try {
                if (is_array($moduleData)) {
                    // Si c'est un tableau (données JSON brutes)
                    $moduleTitle = $moduleData['title'] ?? '';
                    if (empty($moduleTitle)) {
                        continue; // Ignorer les modules sans titre
                    }
                    
                    $module = new SyllabusModule();
                    $module->setTitle($moduleTitle);
                    $module->setDescription($moduleData['description'] ?? null);
                    
                    // Traiter les labs
                    if (isset($moduleData['labs']) && is_array($moduleData['labs'])) {
                        foreach ($moduleData['labs'] as $labData) {
                            if (is_array($labData)) {
                                $labName = $labData['name'] ?? '';
                                if (empty($labName)) {
                                    continue; // Ignorer les labs sans nom
                                }
                                
                                $lab = new Lab();
                                $lab->setName($labName);
                                $lab->setDuration($labData['duration'] ?? null);
                                $lab->setModule($module);
                                $module->addLab($lab);
                            }
                        }
                    }
                } elseif ($moduleData instanceof SyllabusModule) {
                    // Si c'est déjà une instance de SyllabusModule (cas normal avec API Platform)
                    $moduleTitle = $moduleData->getTitle();
                    if (empty($moduleTitle)) {
                        continue; // Ignorer les modules sans titre
                    }
                    
                    // Créer une nouvelle instance pour éviter les problèmes de persistance
                    $module = new SyllabusModule();
                    $module->setTitle($moduleTitle);
                    $module->setDescription($moduleData->getDescription());
                    
                    // Traiter les labs
                    foreach ($moduleData->getLabs() as $labData) {
                        if ($labData instanceof Lab) {
                            $labName = $labData->getName();
                            if (empty($labName)) {
                                continue; // Ignorer les labs sans nom
                            }
                            
                            // Créer une nouvelle instance pour éviter les problèmes de persistance
                            $lab = new Lab();
                            $lab->setName($labName);
                            $lab->setDuration($labData->getDuration());
                            $lab->setModule($module);
                            $module->addLab($lab);
                        } elseif (is_array($labData)) {
                            $labName = $labData['name'] ?? '';
                            if (empty($labName)) {
                                continue; // Ignorer les labs sans nom
                            }
                            
                            $lab = new Lab();
                            $lab->setName($labName);
                            $lab->setDuration($labData['duration'] ?? null);
                            $lab->setModule($module);
                            $module->addLab($lab);
                        }
                    }
                }
                
                if ($module) {
                    $module->setOrderIndex($index);
                    $module->setCourse($course);
                    $course->addSyllabus($module);
                }
            } catch (\Exception $e) {
                // Logger l'erreur mais continuer avec les autres modules
                error_log('Error processing syllabus module: ' . $e->getMessage());
                continue;
            }
            }
        }

        // Mettre à jour la date de modification
        $course->setUpdatedAt(new \DateTimeImmutable());

        // Déterminer l'action (create ou update)
        $isUpdate = isset($uriVariables['id']) && $uriVariables['id'];
        
        // Enregistrer l'audit log (si le service est disponible)
        if ($this->auditLogService && $this->security) {
            try {
                $user = $this->security->getUser();
                if ($isUpdate && isset($existingCourse)) {
                    // Calculer les changements pour une mise à jour
                    $changes = [];
                    if ($existingCourse->getTitle() !== $course->getTitle()) {
                        $changes['title'] = ['old' => $existingCourse->getTitle(), 'new' => $course->getTitle()];
                    }
                    if ($existingCourse->getPrice() !== $course->getPrice()) {
                        $changes['price'] = ['old' => $existingCourse->getPrice(), 'new' => $course->getPrice()];
                    }
                    
                    $this->auditLogService->logUpdate(
                        'Course',
                        $course->getId(),
                        $course->getTitle(),
                        $user,
                        $changes
                    );
                } else {
                    $this->auditLogService->logCreate(
                        'Course',
                        null, // ID sera disponible après flush
                        $course->getTitle(),
                        $user
                    );
                }
            } catch (\Exception $e) {
                // Ne pas bloquer l'opération si l'audit échoue
                error_log('Erreur lors de l\'enregistrement du log d\'audit: ' . $e->getMessage());
            }
        }

        $this->entityManager->persist($course);
        $this->entityManager->flush();
        
        // Mettre à jour le log de création avec l'ID réel
        if (!$isUpdate) {
            try {
                // Recharger le log le plus récent pour cet utilisateur et cette entité
                // et mettre à jour l'entityId
                // Note: Pour simplifier, on pourrait aussi faire un flush séparé dans AuditLogService
            } catch (\Exception $e) {
                // Ignorer l'erreur
            }
        }

        return $course;
    }
}

