<?php

namespace App\Controller;

use App\Entity\ExamVoucher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/exam_vouchers')]
class ExamVoucherController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'api_exam_vouchers_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $vouchers = $this->entityManager->getRepository(ExamVoucher::class)->findBy(['isActive' => true]);
            
            $data = array_map(function (ExamVoucher $voucher) {
                return [
                    'id' => $voucher->getId(),
                    'code' => $voucher->getCode(),
                    'examCode' => $voucher->getExamCode(),
                    'type' => $voucher->getType(),
                    'price' => $voucher->getPrice(),
                    'validityPeriod' => $voucher->getValidityPeriod(),
                    'description' => $voucher->getDescription(),
                    'bookingSteps' => $voucher->getBookingSteps() ?? [],
                    'rescheduleRules' => $voucher->getRescheduleRules(),
                    'redemptionInfo' => $voucher->getRedemptionInfo(),
                    'scheduleLocation' => $voucher->getScheduleLocation(),
                    'idRequirements' => $voucher->getIdRequirements(),
                    'isActive' => $voucher->isActive(),
                    'createdAt' => $voucher->getCreatedAt()?->format('c'),
                    'updatedAt' => $voucher->getUpdatedAt()?->format('c'),
                ];
            }, $vouchers);
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('', name: 'api_exam_vouchers_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }
        
        $voucher = new ExamVoucher();
        $voucher->setCode($data['code'] ?? '');
        $voucher->setExamCode($data['examCode'] ?? '');
        $voucher->setType($data['type'] ?? 'voucher-only');
        $voucher->setPrice($data['price'] ?? '0');
        $voucher->setValidityPeriod((int) ($data['validityPeriod'] ?? 365));
        $voucher->setDescription($data['description'] ?? null);
        $voucher->setBookingSteps($data['bookingSteps'] ?? []);
        $voucher->setRescheduleRules($data['rescheduleRules'] ?? null);
        $voucher->setRedemptionInfo($data['redemptionInfo'] ?? null);
        $voucher->setScheduleLocation($data['scheduleLocation'] ?? null);
        $voucher->setIdRequirements($data['idRequirements'] ?? null);
        $voucher->setIsActive($data['isActive'] ?? true);
        
        $this->entityManager->persist($voucher);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'id' => $voucher->getId(),
            'code' => $voucher->getCode(),
            'examCode' => $voucher->getExamCode(),
            'type' => $voucher->getType(),
            'price' => $voucher->getPrice(),
            'validityPeriod' => $voucher->getValidityPeriod(),
            'description' => $voucher->getDescription(),
            'bookingSteps' => $voucher->getBookingSteps(),
            'rescheduleRules' => $voucher->getRescheduleRules(),
            'redemptionInfo' => $voucher->getRedemptionInfo(),
            'scheduleLocation' => $voucher->getScheduleLocation(),
            'idRequirements' => $voucher->getIdRequirements(),
            'isActive' => $voucher->isActive(),
            'createdAt' => $voucher->getCreatedAt()?->format('c'),
            'updatedAt' => $voucher->getUpdatedAt()?->format('c'),
        ], 201);
    }

    #[Route('/{id}', name: 'api_exam_vouchers_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $voucher = $this->entityManager->getRepository(ExamVoucher::class)->find($id);
        
        if (!$voucher) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }
        
        return new JsonResponse([
            'id' => $voucher->getId(),
            'code' => $voucher->getCode(),
            'examCode' => $voucher->getExamCode(),
            'type' => $voucher->getType(),
            'price' => $voucher->getPrice(),
            'validityPeriod' => $voucher->getValidityPeriod(),
            'description' => $voucher->getDescription(),
            'bookingSteps' => $voucher->getBookingSteps(),
            'rescheduleRules' => $voucher->getRescheduleRules(),
            'redemptionInfo' => $voucher->getRedemptionInfo(),
            'scheduleLocation' => $voucher->getScheduleLocation(),
            'idRequirements' => $voucher->getIdRequirements(),
            'isActive' => $voucher->isActive(),
            'createdAt' => $voucher->getCreatedAt()?->format('c'),
            'updatedAt' => $voucher->getUpdatedAt()?->format('c'),
        ]);
    }

    #[Route('/{id}', name: 'api_exam_vouchers_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $voucher = $this->entityManager->getRepository(ExamVoucher::class)->find($id);
        
        if (!$voucher) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['code'])) $voucher->setCode($data['code']);
        if (isset($data['examCode'])) $voucher->setExamCode($data['examCode']);
        if (isset($data['type'])) $voucher->setType($data['type']);
        if (isset($data['price'])) $voucher->setPrice($data['price']);
        if (isset($data['validityPeriod'])) $voucher->setValidityPeriod((int) $data['validityPeriod']);
        if (isset($data['description'])) $voucher->setDescription($data['description']);
        if (isset($data['bookingSteps'])) $voucher->setBookingSteps($data['bookingSteps']);
        if (isset($data['rescheduleRules'])) $voucher->setRescheduleRules($data['rescheduleRules']);
        if (isset($data['redemptionInfo'])) $voucher->setRedemptionInfo($data['redemptionInfo']);
        if (isset($data['scheduleLocation'])) $voucher->setScheduleLocation($data['scheduleLocation']);
        if (isset($data['idRequirements'])) $voucher->setIdRequirements($data['idRequirements']);
        if (isset($data['isActive'])) $voucher->setIsActive((bool) $data['isActive']);
        
        $voucher->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
        
        return new JsonResponse([
            'id' => $voucher->getId(),
            'code' => $voucher->getCode(),
            'examCode' => $voucher->getExamCode(),
            'type' => $voucher->getType(),
            'price' => $voucher->getPrice(),
            'validityPeriod' => $voucher->getValidityPeriod(),
            'description' => $voucher->getDescription(),
            'bookingSteps' => $voucher->getBookingSteps(),
            'rescheduleRules' => $voucher->getRescheduleRules(),
            'redemptionInfo' => $voucher->getRedemptionInfo(),
            'scheduleLocation' => $voucher->getScheduleLocation(),
            'idRequirements' => $voucher->getIdRequirements(),
            'isActive' => $voucher->isActive(),
            'createdAt' => $voucher->getCreatedAt()?->format('c'),
            'updatedAt' => $voucher->getUpdatedAt()?->format('c'),
        ]);
    }

    #[Route('/{id}', name: 'api_exam_vouchers_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $voucher = $this->entityManager->getRepository(ExamVoucher::class)->find($id);
        
        if (!$voucher) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }
        
        $this->entityManager->remove($voucher);
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }
}
