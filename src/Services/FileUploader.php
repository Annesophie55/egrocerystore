<?php
namespace App\Services;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    public function upload(UploadedFile $imageFile, $targetDirectory): string
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = 'images/'.$safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();


        try {
            $imageFile->move(
                $targetDirectory,
                $newFilename
            );
        } catch (FileException $e) {
             
            throw new \Exception('Could not upload the file: ' . $e->getMessage());
        
        }

        return $newFilename;
    }

}