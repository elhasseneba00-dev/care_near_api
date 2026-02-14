<?php

namespace App\OpenApi;

/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     title="CareNear API",
 *     version="1.0.0",
 *     description="CareNear backend API — Plateforme de mise en relation patients / infirmiers à domicile.",
 *     @OA\Contact(email="elhasseneba00@gmail.com")
 *   ),
 *   @OA\Server(
 *     url="/api/v1",
 *     description="Local dev server"
 *   )
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   description="Use Sanctum token as Bearer token"
 * )
 *
 * @OA\Tag(name="Auth", description="Inscription, connexion, déconnexion, profil courant")
 * @OA\Tag(name="Patient Profile", description="Profil et documents médicaux du patient")
 * @OA\Tag(name="Nurse Profile", description="Profil, diplôme et documents de l'infirmier")
 * @OA\Tag(name="Nurse Search", description="Recherche d'infirmiers par géolocalisation")
 * @OA\Tag(name="Care Requests", description="Demandes de soins (cycle de vie complet)")
 * @OA\Tag(name="Chat", description="Messagerie dans une demande de soins")
 * @OA\Tag(name="Reviews", description="Avis et notes laissés par les patients")
 * @OA\Tag(name="Favorites", description="Infirmiers favoris du patient")
 * @OA\Tag(name="Notifications", description="Notifications in-app")
 * @OA\Tag(name="Admin", description="Administration (ADMIN only)")
 */
class OpenApi {}
