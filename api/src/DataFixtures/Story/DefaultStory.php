<?php

declare(strict_types=1);

namespace App\DataFixtures\Story;

use App\DataFixtures\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class DefaultStory extends Story
{
    public function build(): void
    {
        // Create default user
        UserFactory::createOne([
            'email' => 'john.doe@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);

        // Create admin user
        UserFactory::createOneAdmin();
    }
}
