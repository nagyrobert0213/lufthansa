<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'api_')]
final class UserController extends AbstractController
{

    public function __construct(
        private readonly UserRepository      $userRepository,
        private readonly SerializerInterface $serializer,
    )
    {
    }

    #[Route('/user', name: 'users', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        $json = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);

        return JsonResponse::fromJsonString($json);
    }

    #[Route('/user/{id}', name: 'user', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        return new JsonResponse($this->userRepository->find($id));
    }

    #[Route('/user', name: 'user_store', methods: ['POST'])]
    public function store(
        Request                     $request,
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse
    {
        $result = [];
        $data = $request->request->all();

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));

            $entityManager->persist($user);
            $entityManager->flush();

            $result['status'] = 'success';
            $result['user'] = $user;
            return new JsonResponse($result);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        $result['status'] = 'error';
        $result['errors'] = $errors;

        return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
    }
}
