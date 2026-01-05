<?php

namespace App\Serializer;

use App\Entity\ExamVoucher;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ExamVoucherDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private ObjectNormalizer $normalizer
    ) {
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): ExamVoucher
    {
        // Convertir le prix en string si c'est un nombre
        if (isset($data['price'])) {
            if (is_numeric($data['price'])) {
                $data['price'] = (string) $data['price'];
            }
        } else {
            $data['price'] = '0';
        }

        // S'assurer que bookingSteps est un array
        if (isset($data['bookingSteps']) && !is_array($data['bookingSteps'])) {
            $data['bookingSteps'] = [];
        }

        // S'assurer que validityPeriod est un integer
        if (isset($data['validityPeriod'])) {
            $data['validityPeriod'] = (int) $data['validityPeriod'];
        }

        // S'assurer que isActive est un boolean
        if (isset($data['isActive'])) {
            $data['isActive'] = (bool) $data['isActive'];
        }

        // Désérialiser l'entité
        return $this->normalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === ExamVoucher::class && is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ExamVoucher::class => false,
        ];
    }
}
