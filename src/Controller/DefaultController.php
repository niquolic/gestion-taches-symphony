<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Tache;
use App\Entity\User;
use App\Form\AddTaskType;
use App\Repository\TacheRepository;

class DefaultController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    /**
     * @Route("/", name="app_default")
     */
    public function index(TacheRepository $taskRepository): Response
    {
        $user = $this->getUser();

        // Récupération des tâches correspondant à l'utilisateur connecté
        $tasks = $taskRepository->findBy(['id_user' => $user->getId()]);

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController', 'tasks' => $tasks,
        ]);
    }
    /**
     * @Route("/add_task", name="add_task")
     */
    public function add_task(Request $request)
    {
        $task = new Tache();
        $task -> setIdUser($this -> getUser() -> getId());
        $form = $this->createForm(AddTaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($task);
            $this->em->flush();
            return $this->redirectToRoute('app_default');
        }

        return $this->render('actions/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/delete_task/{id}", name="delete_task")
     */
    public function delete_task($id)
    {
        $task = $this->em->getRepository(Tache::class)->find($id);

        if (!$task) {
            throw $this->createNotFoundException(
                'Aucune tache trouvée'.$id
            );
        }

        $this->em->remove($task);
        $this->em->flush();

        return $this->redirectToRoute('app_default');
    }
}
