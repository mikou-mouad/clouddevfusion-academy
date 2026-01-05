<?php

namespace App\Serializer;

class CircularReferenceHandler
{
    public function __invoke($object)
    {
        // Retourner l'ID de l'objet pour éviter la référence circulaire
        if (method_exists($object, 'getId')) {
            return $object->getId();
        }
        return null;
    }
}

