<?php
namespace App\Services;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class FileUploader
{
    private $projectDir;

    public function __construct(ContainerBagInterface $containerBag)
    {
        // Récupérer le chemin racine du projet
        $this->projectDir = $containerBag->get('kernel.project_dir');
    }

    /**
     * Télécharge une image depuis une URL et l'enregistre localement
     *
     * @param string $url URL de l'image à télécharger
     * @param string $directory Chemin relatif du dossier de destination (par exemple "images/products")
     * @return string|null Chemin relatif de l'image enregistrée ou null en cas d'échec
     */
    public function download(string $url, string $directory): ?string
    {
        try {
            // Créer un client HTTP
            $client = HttpClient::create();
            $response = $client->request('GET', $url);

            // Vérifier si la requête a réussi
            if ($response->getStatusCode() !== 200) {
                throw new \Exception("L'image n'a pas pu être téléchargée depuis l'URL : $url");
            }

            // Récupérer le contenu de l'image
            $imageData = $response->getContent();

            // Générer un nom unique pour l'image
            $imageName = md5(uniqid()) . '.jpg'; // Changez l'extension si nécessaire
            $fullDirectory = $this->projectDir . '/public/' . $directory;

            // Créer le dossier de destination s'il n'existe pas
            if (!is_dir($fullDirectory)) {
                mkdir($fullDirectory, 0777, true);
            }

            // Chemin complet pour sauvegarder l'image
            $imagePath = $fullDirectory . '/' . $imageName;

            // Sauvegarder l'image localement
            file_put_contents($imagePath, $imageData);

            // Retourner le chemin relatif qui sera enregistré en BDD
            return '/' . $directory . '/' . $imageName;
        } catch (\Exception $e) {
            // En cas d'erreur, loggez l'erreur et retournez null
            return null;
        }
    }
}
