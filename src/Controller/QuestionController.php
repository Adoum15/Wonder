<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Question;
use App\Form\CommentType;
use App\Form\QuestionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/question', name: 'question_')]
class QuestionController extends AbstractController
{
    #[Route('/ask', name: 'form')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(Request $resquest, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $question = new Question();
        $formQuestion = $this->createForm(QuestionType::class, $question);
        $formQuestion->handleRequest($resquest);

        if ($formQuestion->isSubmitted() && $formQuestion->isValid()) {
            $question->setNbrOfResponse(0);
            $question->setRating(0);
            $question->setAuthor($user);
            $question->setCreatedAt(new \DateTimeImmutable());
            $em->persist($question);
            $em->flush();
            $this->addFlash('success', 'votre question a bien été enregistrée');
            return $this->redirectToRoute('home');
        }

        
        return $this->render('question/index.html.twig', [
            'form' => $formQuestion->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(Request $request, Question $question, EntityManagerInterface $em): Response
    {
        $options = [
            'question' => $question
        ];
        $user = $this->getUser();
        if($user){
            $comment = new Comment();
            $commentForm = $this->createForm(CommentType::class, $comment);
            $commentForm->handleRequest($request);
             if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $comment->setCreatedAt(new \DateTimeImmutable());
                $comment->setRating(0);
                $comment->setQuestion($question);
                $comment->setAuthor($user);
                $question->setNbrOfResponse($question->getNbrOfResponse() + 1);
                $em->persist($comment);
                $em->flush();
                $this->addFlash('success', 'votre commentaire a bien été enregistré');
                return $this->redirect($request->getUri());
            }
            $options['form'] = $commentForm->createView();
        }

        return $this->render('question/show.html.twig', $options);
    }

    #[Route('/{id}/{score}', name: 'rating')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function ratingQuestion( Request $request,Question $question, int $score, EntityManagerInterface $em){
        $question->setRating($question->getRating() + $score);
        $em->flush();
        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('home');
    }
 
    
    #[Route('/comment/{id}/{score}', name: 'comment_rating')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function ratingComment( Request $request,Comment $comment, int $score, EntityManagerInterface $em){
        $comment->setRating($comment->getRating() + $score);
        $em->flush();
        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('home');
    }

}

