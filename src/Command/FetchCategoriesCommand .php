<?php

namespace App\Command;

use App\Services\OpenFoodFactsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchCategoriesCommand extends Command
{
    protected static $defaultName = 'app:fetch-categories';
    private $openFoodFactsService;

    public function __construct(OpenFoodFactsService $openFoodFactsService)
    {
        parent::__construct();
        $this->openFoodFactsService = $openFoodFactsService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Fetches categories from Open Food Facts.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $categoriesData = $this->openFoodFactsService->fetchCategories();

        // Vous pouvez commenter le dd() et utiliser $output->writeln() pour afficher les résultats dans la console
        // dd($categoriesData);

        foreach ($categoriesData as $category) {
            $output->writeln($category['name']);
        }

        return Command::SUCCESS;
    }
}
