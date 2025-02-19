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
use Symfony\Component\Yaml\Yaml;

#[Route('/api', name: 'api_')]
final class UserController extends AbstractController
{
    private const ALLOWED_FORMAT_TYPES = ['json', 'yml', 'yaml'];

    public function __construct(
        private readonly UserRepository      $userRepository,
        private readonly SerializerInterface $serializer,
    )
    {
    }

    #[Route('/users', name: 'users_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $type = $request->query->get('type', 'json');

        if (!in_array($type, self::ALLOWED_FORMAT_TYPES, true)) {
            return new JsonResponse(['error' => 'Invalid type. Allowed types: json, yaml, yml'], Response::HTTP_BAD_REQUEST);
        }

        $users = $this->userRepository->findAll();

        return $this->formatResponse($users, $type);
    }

    #[Route('/users/{id}', name: 'users_show', methods: ['GET'])]
    public function show(int $id, Request $request): Response
    {
        $type = $request->query->get('type', 'json');

        if (!in_array($type, self::ALLOWED_FORMAT_TYPES, true)) {
            return new JsonResponse(['error' => 'Invalid type. Allowed types: json, yaml, yml'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->find($id);

        return $this->formatResponse($user, $type);
    }

    #[Route('/users', name: 'users_store', methods: ['POST'])]
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

            $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);

            return JsonResponse::fromJsonString($jsonUser, Response::HTTP_CREATED);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        $result['status'] = 'error';
        $result['errors'] = $errors;

        return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
    }

    private function formatResponse(mixed $data, string $type): Response
    {
        $serializedData = $this->serializer->serialize($data, 'json', ['groups' => 'user:read']);

        if ($type === 'yaml' || $type === 'yml') {
            $dataArray = json_decode($serializedData, true);
            $yamlData = Yaml::dump($dataArray, 2, 4, Yaml::DUMP_OBJECT_AS_MAP);
            return new Response($yamlData, Response::HTTP_OK, ['Content-Type' => 'application/x-yaml']);
        }

        return new Response($serializedData, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}
