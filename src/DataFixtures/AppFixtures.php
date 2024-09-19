<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Note;
use App\Entity\Network;
use App\Entity\Like;
use App\Entity\View;
use App\Entity\Offer;
use App\Entity\Subscription;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    private $slugger;
    private $hasher;

    public function __construct(SluggerInterface $slugger, UserPasswordHasherInterface $hasher)
    {
        $this->slugger = $slugger;
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Categories
        $categories = [
            'HTML' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/html5/html5-plain.svg',
            'CSS' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/css3/css3-plain.svg',
            'JavaScript' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/javascript/javascript-plain.svg',
            'PHP' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-plain.svg',
            'SQL' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/postgresql/postgresql-plain.svg',
        ];
        $categoryEntities = [];
        foreach ($categories as $title => $icon) {
            $category = new Category();
            $category->setTitle($title)->setIcon($icon);
            $categoryEntities[] = $category;
            $manager->persist($category);
        }

        // Users
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $username = $faker->userName;
            $user->setEmail($this->slugger->slug($username) . '@' . $faker->freeEmailDomain())
                ->setUsername($username)
                ->setPassword($this->hasher->hashPassword($user, 'password'))
                ->setRoles(['ROLE_USER'])
                ->setCreatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-1 year')->format('Y-m-d H:i:s')))
                ->setUpdatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-1 month')->format('Y-m-d H:i:s')))
                ->setImage('https://avatar.iran.liara.run/public/' . $i);

            $users[] = $user;
            $manager->persist($user);
        }

        // Notes
        $notes = [];
        foreach ($users as $user) {
            for ($j = 0; $j < 3; $j++) {
                $note = new Note();
                $title = $faker->sentence(4);
                $note->setTitle($title)
                    ->setSlug($this->slugger->slug($title))
                    ->setContent($faker->paragraph(3))
                    ->setIsPublic($faker->boolean())
                    ->setAuthor($user)
                    ->setCategory($faker->randomElement($categoryEntities))
                    ->setCreatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-1 year')->format('Y-m-d H:i:s')))
                    ->setUpdatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-1 month')->format('Y-m-d H:i:s')));

                $notes[] = $note;
                $manager->persist($note);

                // Views
                $viewCount = $faker->numberBetween(1, 10);
                for ($k = 0; $k < $viewCount; $k++) {
                    $view = new View();
                    $view->setIpAddress($faker->ipv4)
                        ->setCreatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-1 month')->format('Y-m-d H:i:s')))
                        ->setUpdatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-1 week')->format('Y-m-d H:i:s')))
                        ->setNote($note);
                    $manager->persist($view);
                }
            }
        }

        // Likes
        foreach ($notes as $note) {
            $likeCount = $faker->numberBetween(0, 3);
            for ($k = 0; $k < $likeCount; $k++) {
                $like = new Like();
                $like->setNote($note)
                    ->setAuthor($faker->randomElement($users));
                $manager->persist($like);
            }
        }

        // Networks
        $networkTypes = ['Twitter', 'LinkedIn', 'GitHub', 'Facebook'];
        foreach ($users as $user) {
            $networkCount = $faker->numberBetween(0, 2);
            $shuffledNetworks = $faker->shuffleArray($networkTypes);
            for ($i = 0; $i < $networkCount; $i++) {
                $network = new Network();
                $network->setName($shuffledNetworks[$i])
                    ->setUrl($faker->url)
                    ->setAuthor($user);
                $manager->persist($network);
            }
        }

        // Offers
        $offerNames = ['Basic', 'Pro', 'Enterprise'];
        $offers = [];
        foreach ($offerNames as $name) {
            $offer = new Offer();
            $offer->setName($name)
                ->setPrice((string)$faker->randomFloat(2, 9.99, 99.99))
                ->setFeatures($faker->sentences(3, true));
            $offers[] = $offer;
            $manager->persist($offer);
        }

        // Subscriptions
        foreach ($users as $user) {
            if ($faker->boolean(70)) { // 70% chance for a user to have a subscription
                $subscription = new Subscription();
                $subscription->setAuthor($user)
                    ->addOffer($faker->randomElement($offers))
                    ->setStartDate($faker->dateTimeBetween('-6 months', 'now'))
                    ->setEndDate($faker->dateTimeBetween('+1 month', '+1 year'))
                    ->setCreatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-1 year')->format('Y-m-d H:i:s')))
                    ->setUpdatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-1 month')->format('Y-m-d H:i:s')));
                $manager->persist($subscription);
                $user->addSubscription($subscription);
            }
        }

        $manager->flush();
    }
}