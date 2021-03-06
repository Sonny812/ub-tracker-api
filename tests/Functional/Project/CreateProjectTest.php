<?php
/**
 * @author    Nickolay Mikhaylov <sonny@milton.pro>
 * @copyright Copyright (c) 2019, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional\Project;

use App\Tests\Functional\AbstractApiTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Create project test
 */
class CreateProjectTest extends AbstractApiTest
{
    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testDeveloperCantCreateProject(): void
    {
        $this->expectException(AccessDeniedException::class);

        $client = self::$client;
        $client->catchExceptions(false);
        $client->setServerParameter(self::AUTH_PARAMETER_NAME, self::$roleTokenMap['developer']);

        $client->request(Request::METHOD_POST, '/api/project/', [], [], [], json_encode([
            'title'   => 'test',
            'locales' => ['en'],
        ]));

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * @param string|null $title
     * @param array|null  $locales
     * @param array|null  $links
     *
     * @dataProvider provideCreateProjectData
     */
    public function testCreateProject(?string $title, ?array $locales, ?array $links): void
    {
        $client = self::$client;
        $client->setServerParameter(self::AUTH_PARAMETER_NAME, self::$roleTokenMap['qa']);

        $client->request(Request::METHOD_POST, '/api/project/', [], [], [], json_encode([
            'title'   => $title,
            'locales' => $locales,
            'links'   => $links,
        ]));

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param string|null $title
     * @param array|null  $locales
     * @param array|null  $links
     * @param int         $expectedResult
     *
     * @dataProvider provideCreateProjectInvalidData
     */
    public function testCreateProjectWithInvalidData(
        ?string $title,
        ?array $locales,
        ?array $links,
        int $expectedResult = Response::HTTP_BAD_REQUEST
    ): void {
        $client = self::$client;
        $client->setServerParameter(self::AUTH_PARAMETER_NAME, self::$roleTokenMap['qa']);

        $client->request(Request::METHOD_POST, '/api/project/', [], [], [], json_encode([
            'title'   => $title,
            'locales' => $locales,
            'links'   => $links,
        ]));

        $response = $client->getResponse();

        $this->assertEquals($expectedResult, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @return \Generator
     */
    public function provideCreateProjectData(): \Generator
    {
        yield 'With all data' => [
            'Test',
            ['ru', 'en'],
            [['title' => 'github', 'url' => 'https://github.com/Sonny812/ub-tracker-api']],
        ];

        yield 'Without links' => [
            'Test',
            ['ru', 'en'],
            null,
        ];
    }

    /**
     * @return \Generator
     */
    public function provideCreateProjectInvalidData(): \Generator
    {
        yield 'With blank title' => [
            '',
            ['en'],
            null,
        ];

        yield 'Without locales' => [
            'Test',
            null,
            [['title' => 'github', 'url' => 'https://github.com/Sonny812/ub-tracker-api']],
        ];

        yield 'With invalid url in link' => [
            'Test',
            ['en'],
            [['title' => 'github', 'url' => 'invalid url']],
        ];
    }
}
