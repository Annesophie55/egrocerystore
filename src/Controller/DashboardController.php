<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use Symfony\Component\Mime\Email;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('admin/dashboard')]
class DashboardController extends AbstractController
{
    private $orderItemRepository;
    private $entityManager;
    private $mailerInterface;

    public function __construct(OrderItemRepository $orderItemRepository, EntityManagerInterface $entityManager, MailerInterface $mailerInterface)
    {
        $this->orderItemRepository = $orderItemRepository;
        $this->entityManager = $entityManager;
        $this->mailerInterface = $mailerInterface;
    }
    #[Route('/', name: 'app_dashboard', methods:'GET')]
    public function index(): Response
    {
        $orderItems = $this->orderItemRepository->findBy([
            'status' => ['En préparation', 'Préparé']
        ]);
        return $this->render('dashboard/index.html.twig', [
            'orderItems' => $orderItems,
        ]);
    }

    private function allItemsArePrepared(Order $order): bool {
        foreach ($order->getOrderItems() as $item) {
            if ($item->getStatus() !== 'préparé') {
                return false;
            }
        }
        return true;
    }
    

    #[Route('/update-status/{id}', name: 'update_status', methods: ['POST', 'GET'])]
    public function updateStatus(OrderItem $orderItem, Request $request): Response
    {
        $newStatus = $request->request->get('status');
        if ($newStatus) {
            $orderItem->setStatus($newStatus);
            $this->entityManager->flush();

            $order = $orderItem->getorders();
            if ($order && $this->allItemsArePrepared($order)) {
                foreach ($order->getOrderItems() as $item) {
                    $item->setStatus('Terminé');
                }
                $this->entityManager->flush();
                
                // Envoyer un email au client
                $email = (new Email())
                    ->from('anso.jade2013@gmail.com')
                    ->to($order->getUser()->getEmail())
                    ->subject('Votre order est prête')
                    ->text('Bonjour, votre order est maintenant prête à être récupérée.');
                
                $this->mailerInterface->send($email);
                
                $this->addFlash('success', 'Tous les articles sont préparés, la order est marquée comme terminée et le client a été notifié.');
            } else {
                $this->addFlash('success', 'Le statut de l\'article a été mis à jour.');
            }
            }

            return $this->redirectToRoute('app_dashboard');
            }
}
