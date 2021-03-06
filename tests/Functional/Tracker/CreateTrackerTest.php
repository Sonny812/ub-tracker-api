<?php
/**
 * @author    Nickolay Mikhaylov <sonny@milton.pro>
 * @copyright Copyright (c) 2019, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional\Tracker;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Request\UnableToProcessRequestObjectException;
use App\Tests\Functional\AbstractApiTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Create tracker test
 */
class CreateTrackerTest extends AbstractApiTest
{
    public function testDeveloperCantCreateTracker(): void
    {
        $this->expectException(AccessDeniedException::class);

        $client = self::$client;
        $client->catchExceptions(false);
        $client->setServerParameter(self::AUTH_PARAMETER_NAME, self::$roleTokenMap['developer']);

        $client->request(Request::METHOD_POST, sprintf('/api/project/%d/tracker/', $this->getExistingProjectId()));

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * @param array $developers
     * @param array $links
     *
     * @dataProvider provideCreateTrackerData
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testCreateTracker(?array $developers, ?array $links): void
    {
        $client = self::$client;
        $client->setServerParameter(self::AUTH_PARAMETER_NAME, self::$roleTokenMap['qa']);

        $client->request(
            Request::METHOD_POST,
            sprintf('/api/project/%d/tracker/', $this->getExistingProjectId()), [], [], [], json_encode(array_filter([
                    'developers' => $developers,
                    'links'      => $links,
                ], function ($value) {
                    return null !== $value;
                })
            )
        );

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param array $developers
     * @param array $links
     * @param int   $expectedCode
     *
     * @dataProvider provideCreateTrackerInvalidData
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     *
     */
    public function testCreateTrackerWithInvalidData(?array $developers, ?array $links, int $expectedCode = Response::HTTP_BAD_REQUEST): void
    {
        if ($expectedCode === Response::HTTP_UNPROCESSABLE_ENTITY) {
            $this->expectException(UnableToProcessRequestObjectException::class);
        }

        $client = self::$client;
        $client->setServerParameter(self::AUTH_PARAMETER_NAME, self::$roleTokenMap['qa']);
        $client->catchExceptions(false);

        $client->request(
            Request::METHOD_POST,
            sprintf('/api/project/%d/tracker/', $this->getExistingProjectId()), [], [], [], json_encode(array_filter([
                    'developers' => $developers,
                    'links'      => $links,
                ], function ($value) {
                    return null !== $value;
                })
            )
        );

        $response = $client->getResponse();

        $this->assertEquals($expectedCode, $response->getStatusCode(), $response->getContent());
    }

    public function testCreateTrackerForNotExistingProject(): void
    {
        $client = self::$client;

        $client->setServerParameter(self::AUTH_PARAMETER_NAME, self::$roleTokenMap['qa']);

        $client->request(Request::METHOD_POST, sprintf('/api/project/%d/tracker/', 2052050200));

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @return \Generator
     */
    public function provideCreateTrackerData(): \Generator
    {
        yield 'With all data' => [
            [3, 4],
            [['title' => 'github', 'url' => 'https://github.com/Sonny812/ub-tracker-api/']],
        ];

        yield 'Without links' => [
            [3, 4],
            null,
        ];

        yield 'Without data' => [null, null];
    }

    /**
     * @return \Generator
     */
    public function provideCreateTrackerInvalidData(): \Generator
    {
        yield 'With invalid  url in link' => [
            [3, 4],
            [['title' => 'github', 'url' => 'invalid url']],
        ];

        yield 'With not a developer' => [
            [3, 1],
            [['title' => 'github', 'url' => 'https://github.com/Sonny812/ub-tracker-api/']],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }

    /**
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getExistingProjectId(): int
    {
        return $this
            ->getProjectRepository()
            ->createQueryBuilder('o')
            ->select('o.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return \App\Repository\ProjectRepository
     */
    private function getProjectRepository(): ProjectRepository
    {
        return self::$container->get('doctrine.orm.default_entity_manager')->getRepository(Project::class);
    }
}
