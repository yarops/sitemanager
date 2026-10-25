<?php

namespace common\tests\unit\ispmanager;

use common\components\ispmanager\UserTraffic;
use common\models\Server;
use frontend\controllers\ServerController;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\db\Connection;
use yii\web\Application;
use yii\web\NotFoundHttpException;

class UserDiffController extends ServerController
{
    public array $data = [];

    public function render($view, $params = [])
    {
        $this->data = $params;
        return $view;
    }
}

class UserDiffTraffic extends UserTraffic
{
    public ?string $body = '<doc/>';
    public int $calls = 0;

    protected function request(Server $server): string
    {
        $this->calls++;
        if ($this->body === null) {
            throw new \RuntimeException('Transport failure');
        }
        return $this->body;
    }
}

class UserDiffTest extends TestCase
{
    private $previousApp;
    private UserDiffController $controller;
    private UserDiffTraffic $traffic;

    protected function setUp(): void
    {
        $this->previousApp = Yii::$app;
        $app = new Application([
            'id' => 'user-diff-test',
            'basePath' => Yii::getAlias('@frontend'),
            'components' => [
                'db' => ['class' => Connection::class, 'dsn' => 'sqlite::memory:'],
                'request' => [
                    'cookieValidationKey' => 'test', 'scriptUrl' => '/index.php',
                    'scriptFile' => __FILE__, 'hostInfo' => 'https://test.example',
                ],
                'i18n' => ['translations' => ['frontend' => [
                    'class' => 'yii\i18n\PhpMessageSource', 'basePath' => '@common/messages',
                ]]],
            ],
        ]);
        $app->db->createCommand()->createTable('server', ['id' => 'pk', 'title' => 'string'])->execute();
        $app->db->createCommand()->createTable('server_user', [
            'id' => 'pk', 'server_id' => 'integer', 'user_login' => 'string', 'is_archived' => 'integer',
        ])->execute();
        $app->db->createCommand()->insert('server', ['id' => 124, 'title' => 'Test server'])->execute();
        $app->db->createCommand()->batchInsert('server_user', ['server_id', 'user_login', 'is_archived'], [
            [124, 'shared', 0], [124, 'crm-only', 0], [124, 'crm-only', 0],
            [124, 'archived', 1], [125, 'other-server', 0],
        ])->execute();
        $this->traffic = new UserDiffTraffic();
        Yii::$container->setSingleton(UserTraffic::class, $this->traffic);
        $this->controller = new UserDiffController('server', $app);
        $app->controller = $this->controller;
    }

    protected function tearDown(): void
    {
        Yii::$container->clear(UserTraffic::class);
        Yii::$app->db->close();
        Yii::$app = $this->previousApp;
    }

    public function testComparesBothDirectionsByLoginOnSelectedServer(): void
    {
        // Missing traffic values must not hide users from the comparison.
        $this->traffic->body = '<doc><elem><name>shared</name></elem>'
            . '<elem><name>isp-only</name></elem><elem><name>archived</name></elem>'
            . '<elem><name>other-server</name></elem></doc>';
        self::assertSame('checkUsersDiff', $this->controller->actionCheckUsersDiff(124));
        self::assertSame(['archived', 'isp-only', 'other-server'], $this->controller->data['diffIsp']);
        self::assertSame(['crm-only'], $this->controller->data['diffCrm']);
        self::assertSame(4, $this->controller->data['ispCount']);
        self::assertSame(2, $this->controller->data['crmCount']);
        self::assertSame(1, $this->traffic->calls);
    }

    public function testEmptyIspListIsAValidComparison(): void
    {
        $this->controller->actionCheckUsersDiff(124);
        self::assertTrue($this->controller->data['available']);
        self::assertSame(['crm-only', 'shared'], $this->controller->data['diffCrm']);
        self::assertSame([], $this->controller->data['diffIsp']);
    }

    public function testFailuresDoNotReportAllCrmUsersAsMissing(): void
    {
        foreach ([null, '<doc><error type="auth"/></doc>', 'invalid xml'] as $body) {
            $this->traffic->body = $body;
            $this->controller->actionCheckUsersDiff(124);
            self::assertFalse($this->controller->data['available']);
            self::assertSame([], $this->controller->data['diffCrm']);
            $html = Yii::$app->view->renderFile(
                Yii::getAlias('@frontend/views/server/checkUsersDiff.php'), $this->controller->data
            );
            self::assertStringContainsString('Сравнение не выполнено', $html);
            self::assertStringNotContainsString('Расхождений нет', $html);
        }
    }

    public function testViewEscapesLoginsAndShowsMatchingState(): void
    {
        $this->traffic->body = '<doc><elem><name>&lt;script&gt;alert(1)&lt;/script&gt;</name></elem></doc>';
        $this->controller->actionCheckUsersDiff(124);
        $view = Yii::getAlias('@frontend/views/server/checkUsersDiff.php');
        $html = Yii::$app->view->renderFile($view, $this->controller->data);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);

        $this->traffic->body = '<doc><elem><name>shared</name></elem><elem><name>crm-only</name></elem></doc>';
        $this->controller->actionCheckUsersDiff(124);
        $html = Yii::$app->view->renderFile($view, $this->controller->data);
        self::assertSame(2, substr_count($html, 'Расхождений нет.'));
    }

    public function testMissingServerDoesNotRequestIsp(): void
    {
        try {
            $this->controller->actionCheckUsersDiff(999);
            self::fail('Expected a missing server error');
        } catch (NotFoundHttpException $exception) {
            self::assertSame(0, $this->traffic->calls);
        }
    }
}
