<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ExamVoucher;
use Doctrine\ORM\EntityManagerInterface;

/**
 * State Processor pour ExamVoucher
 * Gère la désérialisation et la persistance
 */
class ExamVoucherProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExamVoucher
    {
        // Si les données sont encore un tableau (le Denormalizer n'a pas été appelé)
        if (is_array($data)) {
            $voucher = new ExamVoucher();
            if (isset($data['code'])) $voucher->setCode($data['code']);
            if (isset($data['examCode'])) $voucher->setExamCode($data['examCode']);
            if (isset($data['type'])) $voucher->setType($data['type']);
            if (isset($data['price'])) {
                $price = is_numeric($data['price']) ? (string) $data['price'] : $data['price'];
                $voucher->setPrice($price ?: '0');
            } else {
                $voucher->setPrice('0');
            }
            if (isset($data['validityPeriod'])) $voucher->setValidityPeriod((int) $data['validityPeriod']);
            if (isset($data['description'])) $voucher->setDescription($data['description'] ?: null);
            if (isset($data['bookingSteps'])) {
                $bookingSteps = is_array($data['bookingSteps']) ? $data['bookingSteps'] : [];
                $voucher->setBookingSteps($bookingSteps);
            }
            if (isset($data['rescheduleRules'])) $voucher->setRescheduleRules($data['rescheduleRules'] ?: null);
            if (isset($data['redemptionInfo'])) $voucher->setRedemptionInfo($data['redemptionInfo'] ?: null);
            if (isset($data['scheduleLocation'])) $voucher->setScheduleLocation($data['scheduleLocation'] ?: null);
            if (isset($data['idRequirements'])) $voucher->setIdRequirements($data['idRequirements'] ?: null);
            if (isset($data['isActive'])) $voucher->setIsActive((bool) $data['isActive']);
        } else {
            /** @var ExamVoucher $voucher */
            $voucher = $data;
        }

        // Convertir le prix en string si c'est un nombre (DECIMAL attend une string)
        if ($voucher->getPrice() === null) {
            $voucher->setPrice('0');
        } elseif (is_numeric($voucher->getPrice())) {
            $voucher->setPrice((string) $voucher->getPrice());
        }

        // S'assurer que bookingSteps est un array
        if ($voucher->getBookingSteps() !== null && !is_array($voucher->getBookingSteps())) {
            $voucher->setBookingSteps([]);
        }

        // Mettre à jour la date de modification
        $voucher->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($voucher);
        $this->entityManager->flush();

        return $voucher;
    }

}
