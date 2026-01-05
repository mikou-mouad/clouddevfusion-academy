<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/api')]
class FileUploadController extends AbstractController
{
    private string $publicDirectory;

    public function __construct(string $projectDir)
    {
        $this->publicDirectory = $projectDir . '/public/uploads/videos';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->publicDirectory)) {
            mkdir($this->publicDirectory, 0755, true);
        }
    }

    private function slugify(string $text): string
    {
        // Convertir en minuscules
        $text = mb_strtolower($text, 'UTF-8');
        
        // Remplacer les caractères spéciaux
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        
        // Supprimer les tirets en début et fin
        $text = trim($text, '-');
        
        return $text;
    }

    #[Route('/upload/video', name: 'api_upload_video', methods: ['POST'])]
    public function uploadVideo(Request $request): JsonResponse
    {
        try {
            $file = $request->files->get('video');
            
            if (!$file) {
                return new JsonResponse([
                    'error' => 'Aucun fichier vidéo fourni'
                ], 400);
            }

            if (!$file instanceof UploadedFile) {
                return new JsonResponse([
                    'error' => 'Fichier invalide'
                ], 400);
            }

            // Vérifier le type de fichier
            $allowedMimeTypes = [
                'video/mp4',
                'video/webm',
                'video/ogg',
                'video/quicktime',
                'video/x-msvideo'
            ];

            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                return new JsonResponse([
                    'error' => 'Type de fichier non autorisé. Formats acceptés: MP4, WebM, OGG, MOV, AVI'
                ], 400);
            }

            // Vérifier la taille (max 100MB)
            $maxSize = 100 * 1024 * 1024; // 100MB
            if ($file->getSize() > $maxSize) {
                return new JsonResponse([
                    'error' => 'Fichier trop volumineux. Taille maximale: 100MB'
                ], 400);
            }

            // Créer le dossier s'il n'existe pas
            if (!is_dir($this->publicDirectory)) {
                mkdir($this->publicDirectory, 0755, true);
            }

            // Générer un nom de fichier unique
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugify($originalFilename);
            $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
            $newFilename = ($safeFilename ? $safeFilename . '-' : 'video-') . uniqid() . '.' . $extension;

            // Déplacer le fichier
            try {
                $file->move($this->publicDirectory, $newFilename);
            } catch (FileException $e) {
                return new JsonResponse([
                    'error' => 'Erreur lors de l\'upload: ' . $e->getMessage()
                ], 500);
            }

            // Retourner l'URL du fichier
            $fileUrl = '/uploads/videos/' . $newFilename;

            return new JsonResponse([
                'success' => true,
                'url' => $fileUrl,
                'filename' => $newFilename,
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/upload/video/{filename}', name: 'api_delete_video', methods: ['DELETE'])]
    public function deleteVideo(string $filename): JsonResponse
    {
        try {
            $filePath = $this->publicDirectory . '/' . $filename;
            
            if (file_exists($filePath)) {
                unlink($filePath);
                return new JsonResponse(['success' => true, 'message' => 'Vidéo supprimée']);
            }

            return new JsonResponse(['error' => 'Fichier non trouvé'], 404);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la suppression'], 500);
        }
    }
}
