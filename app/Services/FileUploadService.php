<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload un fichier unique (diplôme).
     * Supprime l'ancien fichier s'il existe.
     *
     * @param  UploadedFile  $file
     * @param  string        $directory   ex: "diplomas/42"
     * @param  string|null   $oldPath     chemin de l'ancien fichier à supprimer
     * @return string        chemin relatif du nouveau fichier
     */
    public static function uploadSingle(
        UploadedFile $file,
        string $directory,
        ?string $oldPath = null
    ): string {
        // Supprimer l'ancien fichier
        if ($oldPath && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        // Générer un nom unique
        $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

        return $file->storeAs($directory, $filename, 'local');
    }

    /**
     * Upload plusieurs fichiers (documents médicaux).
     * Les ajoute aux documents existants.
     *
     * @param  array<UploadedFile>  $files
     * @param  string               $directory       ex: "medical_files/42"
     * @param  array                $existingPaths   chemins déjà enregistrés
     * @param  int                  $maxTotal        nombre max total de documents
     * @return array                tous les chemins (existants + nouveaux)
     */
    public static function uploadMultiple(
        array $files,
        string $directory,
        array $existingPaths = [],
        int $maxTotal = 10
    ): array {
        $paths = $existingPaths;

        foreach ($files as $file) {
            if (count($paths) >= $maxTotal) {
                break;
            }

            $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $filename, 'local');
            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * Supprime un fichier spécifique.
     */
    public static function delete(string $path): bool
    {
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->delete($path);
        }

        return false;
    }

    /**
     * Supprime tous les fichiers d'un répertoire.
     */
    public static function deleteDirectory(string $directory): bool
    {
        return Storage::disk('local')->deleteDirectory($directory);
    }
}
