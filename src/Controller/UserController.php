<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/user')]
class UserController extends AbstractController
{

    #[Route('/', name: 'app_user')]
    public function index(): Response
    {


        return $this->render('user/index.html.twig', [

        ]);
    }

}
