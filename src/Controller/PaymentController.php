<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\PaymentType;
use App\Entity\Transaction;
use App\Service\StripeService;
use Symfony\Component\Mime\Email;
use App\Repository\OffreRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'app_payment')]
    public function index(
        // Injection de dépendances
        Request $request,
        StripeService $stripeService,
        OffreRepository $offres,
        ClientRepository $clients,
        MailerInterface $mailer,
        EntityManagerInterface $em,
    ): Response {
        // Création du formulaire
        $form = $this->createForm(PaymentType::class);
        $form->handleRequest($request);

        // Traitement du formulaire si soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData(); // Récupération des donnée
            $offre = $offres->findOneBy(['id' => $data['offre']->getId()]); // Récupération de l'offre
            $clientEmail = $clients->findOneBy(['id' => $data['client']->getId()])->getEmail(); // Récupération de l'email du client
            $client = $clients->findOneBy(['id' => $data['client']->getId()]); // Récupération du client


            // Création du lien
            $apiKey = $this->getParameter('STRIPE_API_KEY_SECRET'); //
            $link = $stripeService->makePayment(
                $apiKey,
                $offre->getMontant(),
                $offre->getTitre(),
                $clientEmail
            );

            // Envoi du mail
            $email = (new Email())
                ->from('jyindou@example.com')
                ->to($clientEmail)
                ->priority(Email::PRIORITY_HIGH)
                ->subject('Merci de procéder au paiement de votre offre')
                ->html('<div style="background-color: #f4f4f4; padding: 20px; text-align: center; font-family: Arial, sans-serif;">
                        <h1>Bonjour '.$client->getNomComplet().'</h1><br><br>
                        <p>Vous avez sélectioné l\'offre suivante : '.$offre->getTitre().'</p>
                        <p>Le montant à régler est de '.$offre->getMontant().' €</p>
                        <p>Voici le lien pour effectuer le reglement de votre offre :</p>
                        <a href='.$link.' target="_blank">Payer</a><br>
                        <hr>
                        <p> Ce lien est valide pour une durée limitée.</p>
                ');
            $mailer->send($email);

            // Enregistrement de la transaction
            $transaction = new Transaction();
            $transaction
                ->setClient($data['client'])
                ->setMontant($offre->getMontant())
                ->setStatut('En attente')
                ->setDate(new \DateTime());
            $em->persist($transaction);
            $em->flush();
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
