<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\WeeklyPlan;
use App\Entity\Recipe;
use Symfony\Component\HttpFoundation\Request;

class WeeklyPlanController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    /**
     * @Route("/api/weekly-plan/", name="app_weekly_plan")
     */
    public function show(Request $request, SerializerInterface $serializer)
    {
        $user = $this->getUser();
        $weeklyPlan = $this->entityManager->getRepository(WeeklyPlan::class)->findBy(['user' => $user->getId()]);

        $jsonContent = $serializer->serialize($weeklyPlan, 'json', ['groups' => 'weekly_plan']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @Route("/api/weekly-plan/update/", name="app_update_weekly_plan")
     */
    public function createWeeklyPlan(Request $request, SerializerInterface $serializer)
    {
        $user = $this->getUser();
        $content = json_decode($request->getContent(), true);
        $recipe = $this->entityManager->getRepository(Recipe::class)->find($content['recipeId']);

        if ($content['id']) {
            $weeklyPlan = $this->entityManager->getRepository(WeeklyPlan::class)->find($content['id']);

            $this->setWeeklyPlanContent($weeklyPlan, $content, $recipe);

            $this->updateDatabase($weeklyPlan);
        } else {
            $weeklyPlan = new WeeklyPlan();
            $weeklyPlan->setUser($user);

            $this->setWeeklyPlanContent($weeklyPlan, $content, $recipe);

            $this->updateDatabase($weeklyPlan);
        }

        $jsonContent = $serializer->serialize($weeklyPlan, 'json', ['groups' => 'weekly_plan']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    private function setWeeklyPlanContent($weeklyPlan, $content, $recipe)
    {
        if (!$content['day']) {
            throw new \Exception('Day is required');
        } else {
            $weeklyPlan->setWeekday($content['day']['weekday']);
            $weeklyPlan->setWeekDaySort($content['day']['weekDaySort']);
        }
        if (!$content['meal']) {
            throw new \Exception('Meal is required');
        } else {
            $weeklyPlan->setMeal($content['meal']['meal']);
            $weeklyPlan->setMealSort($content['meal']['mealSort']);
        }

        $weeklyPlan->addRecipe($recipe);
    }

    /**
     * @Route("/api/weekly-plan/remove/", name="app_remove_weekly_plan")
     */
    public function removeWeeklyPlan(Request $request, SerializerInterface $serializer)
    {
        $user = $this->getUser()->getId();
        $content = json_decode($request->getContent(), true);

        if ($content && $user == $content['user']) {
            $weeklyPlan = $this->entityManager->getRepository(WeeklyPlan::class)->findOneBy(['id' => $content['id']]);

            $this->entityManager->remove($weeklyPlan);
            $this->entityManager->flush();
        }
        
        $weeklyPlans = $this->entityManager->getRepository(WeeklyPlan::class)->findBy(['user' => $user]);

        $jsonContent = $serializer->serialize($weeklyPlans, 'json', ['groups' => 'weekly_plan']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    public function updateDatabase($object)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();
    }

    /**
     * @Route("/api/weekly-plan/showRecipes", name="app_weekly_plan_show_recipe")
     */
    public function showRecipesToWeeklyPlan(Request $request, SerializerInterface $serializer)
    {
        $user = $this->getUser();
        $content = json_decode($request->getContent(), true);
        $recipes = $this->entityManager->getRepository(Recipe::class)->getRecipesforWeeklyPlan($user);

        $jsonContent = $serializer->serialize($recipes, 'json', ['groups' => 'add_weekly_plan']);

        return new Response($jsonContent, Response::HTTP_OK);
    }
}
