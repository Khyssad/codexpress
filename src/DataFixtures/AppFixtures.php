<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Category; // Make sure to import your entities
use App\Entity\User;
use App\Entity\Note;
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

        // Array of categories
        $categories = [
            'HTML' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/html5/html5-plain.svg',
            'CSS' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/css3/css3-plain.svg',
            'JavaScript' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/javascript/javascript-plain.svg',
            'PHP' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-plain.svg',
            'SQL' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/postgresql/postgresql-plain.svg',
            'JSON' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/json/json-plain.svg',
            'Python' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/python/python-plain.svg',
            'Ruby' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/ruby/ruby-plain.svg',
            'C++' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/cplusplus/cplusplus-plain.svg',
            'Go' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/go/go-wordmark.svg',
            'bash' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/bash/bash-plain.svg',
            'Markdown' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/markdown/markdown-original.svg',
            'Java' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/java/java-original-wordmark.svg',
        ];
        $categoryArray = [];
        foreach ($categories as $title => $icon) {
            $category = new Category();
            $category
                ->setTitle($title) // Corrected variable name
                ->setIcon($icon); // Assuming there's a setIcon method for the icon field
                array_push($categoryArray, $category);
            $manager->persist($category);
        }

        // Creating users
        for ($i = 0; $i < 10; $i++) {
            $username = $faker->userName;
            $usernameFinal = $this->slugger->slug($username);
            $user = new User();
            $user
                ->setEmail($usernameFinal . '@' . $faker->freeEmailDomain()) // Added '@' to email
                ->setUsername($username)
                ->setPassword(
                    $this->hasher->hashPassword($user, 'admin')
                )
                ->setRoles(['ROLE_USER']); // Corrected the setRoles method

            $manager->persist($user); // Persist the user

            // Creating notes
            for ($j = 0; $j < 10; $j++) {
                $note = new Note();
                $note
                    ->setTitle($faker->sentence())
                    ->setSlug($this->slugger->slug($note->getTitle()))
                    ->setContent($faker->paragraphs(4, true))
                    ->setPublic($faker->boolean())
                    ->setViews($faker->numberBetween(100, 1000))
                    ->setAuthor($user) // Assuming this method exists
                    ->addCategory($faker->randomElement($categoryArray)); // Adjust to use the correct categories

                $manager->persist($note);
            }
        }

        // Flush all changes to the database
        $manager->flush();
    }
}

