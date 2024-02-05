<?php

namespace App\Controller;

use App\Form\PaymentType;
use App\Service\StripeService;
use App\Repository\OffreRepository;
use App\Repository\ClientRepository;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'app_payment')]
    public function index(
        Request $request,
        StripeService $stripeService,
        OffreRepository $offres,
        ClientRepository $clients,
        TransactionRepository $transactions,
    ): Response {
        // Création du formulaire
        $form = $this->createForm(PaymentType::class);
        $form->handleRequest($request);

        // Traitement du formulaire si soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $offre = $offres->findOneBy(['id' => $data['offre']->getId()]);
            $clientEmail = $clients->findOneBy(['id' => $data['client']->getId()])->getEmail();

            // Création du lien
            $apiKey = $this->getParameter('STRIPE_API_KEY_SECRET');
            $link = $stripeService->makePayment(
                $apiKey,
                $offre->getMontant(),
                $offre->getTitre(),
                $clientEmail
            );
            // Envoie du lien au client
        }

        // Affichage du formulaire ou page d'erreur
        return $this->render('payment/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/success', name: 'payment_success')]
    public function success(): Response
    {
        return $this->render('payment/success.html.twig');
    }

    #[Route('/cancel', name: 'payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}
