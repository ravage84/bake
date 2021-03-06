<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         0.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Bake\Test\TestCase\Shell\Task;

use Bake\Shell\Task\BakeTemplateTask;
use Bake\Test\App\Controller\PostsController;
use Bake\Test\App\Model\Table\ArticlesTable;
use Bake\Test\App\Model\Table\CategoryThreadsTable;
use Bake\Test\TestCase\TestCase;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Response;
use Cake\Http\ServerRequest as Request;
use Cake\ORM\TableRegistry;

/**
 * TestTaskTest class
 */
class TestTaskTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var string
     */
    public $fixtures = [
        'core.Articles',
        'core.Tags',
        'core.ArticlesTags',
        'core.Authors',
        'core.Comments',
    ];

    /**
     * @var \Bake\Shell\Task\TestTask|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $Task;

    /**
     * @var \Cake\Console\ConsoleIo|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_compareBasePath = Plugin::path('Bake') . 'tests' . DS . 'comparisons' . DS . 'Test' . DS;
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();

        $this->Task = $this->getMockBuilder('Bake\Shell\Task\TestTask')
            ->setMethods(['in', 'err', 'createFile', '_stop', 'isLoadableClass'])
            ->setConstructorArgs([$this->io])
            ->getMock();

        $this->Task->name = 'Test';
        $this->Task->BakeTemplate = new BakeTemplateTask($this->io);
        $this->Task->BakeTemplate->interactive = false;
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Task);
    }

    /**
     * Test that with no args execute() outputs the types you can generate
     * tests for.
     *
     * @return void
     */
    public function testExecuteNoArgsPrintsTypeOptions()
    {
        $this->Task = $this->getMockBuilder('Bake\Shell\Task\TestTask')
            ->disableOriginalConstructor()
            ->setMethods(['outputTypeChoices'])
            ->getMock();

        $this->Task->expects($this->once())
            ->method('outputTypeChoices');

        $this->Task->main();
    }

    /**
     * Test outputTypeChoices method
     *
     * @return void
     */
    public function testOutputTypeChoices()
    {
        $this->io->expects($this->at(0))
            ->method('out')
            ->with($this->stringContains('You must provide'));
        $this->io->expects($this->at(1))
            ->method('out')
            ->with($this->stringContains('1. Entity'));
        $this->io->expects($this->at(2))
            ->method('out')
            ->with($this->stringContains('2. Table'));
        $this->io->expects($this->at(3))
            ->method('out')
            ->with($this->stringContains('3. Controller'));
        $this->Task->outputTypeChoices();
    }

    /**
     * Test that with no args execute() outputs the types you can generate
     * tests for.
     *
     * @return void
     */
    public function testExecuteOneArgPrintsClassOptions()
    {
        $this->Task = $this->getMockBuilder('Bake\Shell\Task\TestTask')
            ->disableOriginalConstructor()
            ->setMethods(['outputClassChoices'])
            ->getMock();

        $this->Task->expects($this->once())
            ->method('outputClassChoices');

        $this->Task->main('Entity');
    }

    /**
     * test execute with type and class name defined
     *
     * @return void
     */
    public function testExecuteWithTwoArgs()
    {
        $this->Task->expects($this->once())->method('createFile')
            ->with(
                $this->stringContains('TestCase' . DS . 'Model' . DS . 'Table' . DS . 'TestTaskTagTableTest.php'),
                $this->stringContains('class TestTaskTagTableTest extends TestCase')
            );
        $this->Task->main('Table', 'TestTaskTag');
    }

    /**
     * test execute with plugin syntax
     *
     * @return void
     */
    public function testExecuteWithPluginName()
    {
        $this->_loadTestPlugin('TestBake');

        $this->Task
            ->expects($this->once())->method('createFile')
            ->with(
                $this->stringContains(
                    'Plugin' . DS . 'TestBake' . DS . 'tests' . DS . 'TestCase' . DS . 'Model' . DS . 'Table' . DS . 'ArticlesTableTest.php'
                ),
                $this->matchesRegularExpression(
                    '/namespace TestBake\\\\Test\\\\TestCase\\\\Model\\\\Table;.*?class ArticlesTableTest extends TestCase/s'
                )
            );
        $this->Task->main('Table', 'TestBake.Articles');
    }

    /**
     * test execute with type and class name defined
     *
     * @return void
     */
    public function testExecuteWithAll()
    {
        $this->Task->expects($this->exactly(5))->method('createFile')
            ->withConsecutive(
                [
                    $this->stringContains('TestCase' . DS . 'Model' . DS . 'Table' . DS . 'ArticlesTableTest.php'),
                    $this->stringContains('class ArticlesTableTest extends TestCase')
                ],
                [
                    $this->stringContains('TestCase' . DS . 'Model' . DS . 'Table' . DS . 'AuthorsTableTest.php'),
                    $this->stringContains('class AuthorsTableTest extends TestCase')
                ],
                [
                    $this->stringContains('TestCase' . DS . 'Model' . DS . 'Table' . DS . 'BakeArticlesTableTest.php'),
                    $this->stringContains('class BakeArticlesTableTest extends TestCase')
                ],
                [
                    $this->stringContains('TestCase' . DS . 'Model' . DS . 'Table' . DS . 'CategoryThreadsTableTest.php'),
                    $this->stringContains('class CategoryThreadsTableTest extends TestCase')
                ],
                [
                    $this->stringContains('TestCase' . DS . 'Model' . DS . 'Table' . DS . 'TemplateTaskCommentsTableTest.php'),
                    $this->stringContains('class TemplateTaskCommentsTableTest extends TestCase')
                ]
            );
        $this->Task->params['all'] = true;
        $this->Task->main('Table');
    }

    /**
     * Test generating class options for table.
     *
     * @return void
     */
    public function testOutputClassOptionsForTable()
    {
        $expected = [
            'ArticlesTable',
            'AuthorsTable',
            'BakeArticlesTable',
            'CategoryThreadsTable',
            'TemplateTaskCommentsTable'
        ];

        $this->io->expects($this->exactly(8))
            ->method('out')
            ->withConsecutive(
                ['You must provide a class to bake a test for. Some possible options are:', 2],
                ['1. ArticlesTable'],
                ['2. AuthorsTable'],
                ['3. BakeArticlesTable'],
                ['4. CategoryThreadsTable'],
                ['5. TemplateTaskCommentsTable'],
                [''],
                ['Re-run your command as `cake bake Table <classname>`']
            );
        $choices = $this->Task->outputClassChoices('Table');
        $this->assertSame($expected, $choices);
    }

    /**
     * Test generating class options for table.
     *
     * @return void
     */
    public function testOutputClassOptionsForTablePlugin()
    {
        $this->loadPlugins(['BakeTest']);
        $this->Task->plugin = 'BakeTest';

        $expected = [
            'AuthorsTable',
            'BakeArticlesTable',
            'BakeTestCommentsTable',
            'CommentsTable'
        ];

        $choices = $this->Task->outputClassChoices('Table');
        $this->assertSame($expected, $choices);
    }

    /**
     * Test that method introspection pulls all relevant non parent class
     * methods into the test case.
     *
     * @return void
     */
    public function testMethodIntrospection()
    {
        $result = $this->Task->getTestableMethods('Bake\Test\App\Model\Table\ArticlesTable');
        $expected = ['initialize', 'findpublished', 'dosomething', 'dosomethingelse'];
        $this->assertEquals($expected, array_map('strtolower', $result));
    }

    /**
     * test that the generation of fixtures works correctly.
     *
     * @return void
     */
    public function testFixtureArrayGenerationFromModel()
    {
        $subject = new ArticlesTable();
        $result = $this->Task->generateFixtureList($subject);
        $expected = [
            'app.Articles',
            'app.Authors',
            'app.Tags',
            'app.ArticlesTags'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that the generation of fixtures works correctly.
     *
     * @return void
     */
    public function testFixtureArrayGenerationIgnoreSelfAssociation()
    {
        TableRegistry::getTableLocator()->clear();
        $subject = new CategoryThreadsTable();
        $result = $this->Task->generateFixtureList($subject);
        $expected = [
            'app.CategoryThreads',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that the generation of fixtures works correctly.
     *
     * @return void
     */
    public function testFixtureGenerationFromController()
    {
        $subject = new PostsController(new Request(), new Response());
        $result = $this->Task->generateFixtureList($subject);
        $expected = [
            'app.Posts',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test generation of fixtures skips invalid modelClass
     *
     * @return void
     */
    public function testFixtureGenerationFromControllerInvalid()
    {
        $subject = new PostsController(new Request(), new Response());
        $subject->modelClass = 'View';
        $result = $this->Task->generateFixtureList($subject);
        $expected = [];
        $this->assertEquals($expected, $result);
    }

    /**
     * Dataprovider for class name generation.
     *
     * @return array
     */
    public static function realClassProvider()
    {
        return [
            ['Entity', 'Article', 'App\Model\Entity\Article'],
            ['Entity', 'ArticleEntity', 'App\Model\Entity\ArticleEntity'],
            ['Table', 'Posts', 'App\Model\Table\PostsTable'],
            ['Table', 'PostsTable', 'App\Model\Table\PostsTable'],
            ['Controller', 'Posts', 'App\Controller\PostsController'],
            ['Controller', 'PostsController', 'App\Controller\PostsController'],
            ['Behavior', 'Timestamp', 'App\Model\Behavior\TimestampBehavior'],
            ['Behavior', 'TimestampBehavior', 'App\Model\Behavior\TimestampBehavior'],
            ['Helper', 'Form', 'App\View\Helper\FormHelper'],
            ['Helper', 'FormHelper', 'App\View\Helper\FormHelper'],
            ['Component', 'Auth', 'App\Controller\Component\AuthComponent'],
            ['Component', 'AuthComponent', 'App\Controller\Component\AuthComponent'],
            ['Shell', 'Example', 'App\Shell\ExampleShell'],
            ['Shell', 'ExampleShell', 'App\Shell\ExampleShell'],
            ['Task', 'Example', 'App\Shell\Task\ExampleTask'],
            ['Task', 'ExampleTask', 'App\Shell\Task\ExampleTask'],
            ['Cell', 'Example', 'App\View\Cell\ExampleCell'],
            ['Cell', 'ExampleCell', 'App\View\Cell\ExampleCell'],
        ];
    }

    /**
     * test that resolving class names works
     *
     * @dataProvider realClassProvider
     * @return void
     */
    public function testGetRealClassname($type, $name, $expected)
    {
        $result = $this->Task->getRealClassname($type, $name);
        $this->assertEquals($expected, $result);
    }

    /**
     * test resolving class names with plugins
     *
     * @return void
     */
    public function testGetRealClassnamePlugin()
    {
        $this->_loadTestPlugin('TestBake');
        $this->Task->plugin = 'TestBake';
        $result = $this->Task->getRealClassname('Helper', 'Asset');
        $expected = 'TestBake\View\Helper\AssetHelper';
        $this->assertEquals($expected, $result);
    }

    /**
     * test resolving class names with prefix
     *
     * @return void
     */
    public function testGetRealClassnamePrefix()
    {
        $this->Task->params['prefix'] = 'api/public';
        $result = $this->Task->getRealClassname('Controller', 'Posts');
        $expected = 'App\Controller\Api\Public\PostsController';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test baking a test for a concrete model with fixtures arg
     *
     * @return void
     */
    public function testBakeFixturesParam()
    {
        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $this->Task->params['fixtures'] = 'app.Posts, app.Comments, app.Users,';
        $result = $this->Task->bake('Table', 'Articles');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * Test baking a test for a concrete model with no-fixtures arg
     *
     * @return void
     */
    public function testBakeNoFixtureParam()
    {
        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $this->Task->params['no-fixture'] = true;
        $result = $this->Task->bake('Table', 'Articles');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * Test baking a test for a cell.
     *
     * @return void
     */
    public function testBakeCellTest()
    {
        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $result = $this->Task->bake('Cell', 'Articles');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * Test baking a test for a command.
     *
     * @return void
     */
    public function testBakeCommandTest()
    {
        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $result = $this->Task->bake('Command', 'Example');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * Test baking a test for a concrete model.
     *
     * @return void
     */
    public function testBakeModelTest()
    {
        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $result = $this->Task->bake('Table', 'Articles');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * test baking controller test files
     *
     * @return void
     */
    public function testBakeControllerTest()
    {
        Configure::write('App.namespace', 'Bake\Test\App');

        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $result = $this->Task->bake('Controller', 'PostsController');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * test baking controller test files
     *
     * @return void
     */
    public function testBakePrefixControllerTest()
    {
        Configure::write('App.namespace', 'Bake\Test\App');

        $this->Task->expects($this->once())
            ->method('createFile')
            ->with($this->stringContains('Controller' . DS . 'Admin' . DS . 'PostsControllerTest.php'))
            ->will($this->returnValue(true));

        $result = $this->Task->bake('controller', 'Admin\Posts');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * test baking controller test files with prefix CLI option
     *
     * @return void
     */
    public function testBakePrefixControllerTestWithCliOption()
    {
        Configure::write('App.namespace', 'Bake\Test\App');

        $this->Task->params['prefix'] = 'Admin';
        $this->Task->expects($this->once())
            ->method('createFile')
            ->with($this->stringContains('Controller' . DS . 'Admin' . DS . 'PostsControllerTest.php'))
            ->will($this->returnValue(true));

        $result = $this->Task->bake('controller', 'Posts');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * test baking component test files,
     *
     * @return void
     */
    public function testBakeComponentTest()
    {
        Configure::write('App.namespace', 'Bake\Test\App');

        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $result = $this->Task->bake('Component', 'Apple');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * test baking behavior test files,
     *
     * @return void
     */
    public function testBakeBehaviorTest()
    {
        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $result = $this->Task->bake('Behavior', 'Example');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * test baking helper test files,
     *
     * @return void
     */
    public function testBakeHelperTest()
    {
        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $result = $this->Task->bake('Helper', 'Example');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * Test baking a test for a shell.
     *
     * @return void
     */
    public function testBakeShellTest()
    {
        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $result = $this->Task->bake('Shell', 'Articles');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * Test baking a test for a shell task.
     *
     * @return void
     */
    public function testBakeShellTaskTest()
    {
        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $result = $this->Task->bake('Task', 'Articles');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * Test baking a test for a shell helper.
     *
     * @return void
     */
    public function testBakeShellHelperTest()
    {
        $this->Task->expects($this->once())
            ->method('createFile')
            ->will($this->returnValue(true));

        $result = $this->Task->bake('shell_helper', 'Example');
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * Test baking an unknown class type.
     *
     * @return void
     */
    public function testBakeUnknownClass()
    {
        $result = $this->Task->bake('Foo', 'Example');
        $this->assertFalse($result);
    }

    /**
     * test Constructor generation ensure that constructClasses is called for controllers
     *
     * @return void
     */
    public function testGenerateConstructor()
    {
        $result = $this->Task->generateConstructor('Controller', 'PostsController');
        $expected = ['', '', ''];
        $this->assertEquals($expected, $result);

        $result = $this->Task->generateConstructor('Table', 'App\Model\\Table\PostsTable');
        $expected = [
            "\$config = TableRegistry::getTableLocator()->exists('Posts') ? [] : ['className' => PostsTable::class];",
            "TableRegistry::getTableLocator()->get('Posts', \$config);",
            ''
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Task->generateConstructor('Helper', 'FormHelper');
        $expected = ["\$view = new View();", "new FormHelper(\$view);", ''];
        $this->assertEquals($expected, $result);

        $result = $this->Task->generateConstructor('Entity', 'TestBake\Model\Entity\Article');
        $expected = ["", "new Article();", ''];
        $this->assertEquals($expected, $result);

        $result = $this->Task->generateConstructor('ShellHelper', 'TestBake\Shell\Helper\ExampleHelper');
        $expected = [
            "\$this->stub = new ConsoleOutput();\n        \$this->io = new ConsoleIo(\$this->stub);",
            "new ExampleHelper(\$this->io);",
            ''
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Task->generateConstructor('Form', 'TestBake\Form\ExampleForm');
        $expected = [
            '',
            "new ExampleForm();",
            ''
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test generateUses()
     *
     * @return void
     */
    public function testGenerateUses()
    {
        $result = $this->Task->generateUses('Table', 'App\Model\Table\PostsTable');
        $expected = [
            'Cake\ORM\TableRegistry',
            'App\Model\Table\PostsTable',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Task->generateUses('Controller', 'App\Controller\PostsController');
        $expected = [
            'App\Controller\PostsController',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Task->generateUses('Helper', 'App\View\Helper\FormHelper');
        $expected = [
            'Cake\View\View',
            'App\View\Helper\FormHelper',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Task->generateUses('Component', 'App\Controller\Component\AuthComponent');
        $expected = [
            'Cake\Controller\ComponentRegistry',
            'App\Controller\Component\AuthComponent',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Task->generateUses('ShellHelper', 'App\Shell\Helper\ExampleHelper');
        $expected = [
            'Cake\TestSuite\Stub\ConsoleOutput',
            'Cake\Console\ConsoleIo',
            'App\Shell\Helper\ExampleHelper',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that mock class generation works for the appropriate classes
     *
     * @return void
     */
    public function testMockClassGeneration()
    {
        $result = $this->Task->hasMockClass('Controller');
        $this->assertTrue($result);
    }

    /**
     * test bake() with a -plugin param
     *
     * @return void
     */
    public function testBakeWithPlugin()
    {
        $this->Task->plugin = 'TestTest';

        $this->loadPlugins(['TestTest' => ['path' => APP . 'Plugin' . DS . 'TestTest' . DS]]);
        $path = APP . 'Plugin/TestTest/tests/TestCase/View/Helper/FormHelperTest.php';
        $path = str_replace('/', DS, $path);
        $this->Task->expects($this->once())->method('createFile')
            ->with($path, $this->anything());

        $this->Task->bake('Helper', 'Form');
    }

    /**
     * Provider for test case file names.
     *
     * @return array
     */
    public static function caseFileNameProvider()
    {
        return [
            ['Table', 'App\Model\Table\PostsTable', 'TestCase/Model/Table/PostsTableTest.php'],
            ['Entity', 'App\Model\Entity\Article', 'TestCase/Model/Entity/ArticleTest.php'],
            ['Helper', 'App\View\Helper\FormHelper', 'TestCase/View/Helper/FormHelperTest.php'],
            ['Controller', 'App\Controller\PostsController', 'TestCase/Controller/PostsControllerTest.php'],
            ['Controller', 'App\Controller\Admin\PostsController', 'TestCase/Controller/Admin/PostsControllerTest.php'],
            ['Behavior', 'App\Model\Behavior\TreeBehavior', 'TestCase/Model/Behavior/TreeBehaviorTest.php'],
            [
                'Component',
                'App\Controller\Component\AuthComponent',
                'TestCase/Controller/Component/AuthComponentTest.php'
            ],
            ['entity', 'App\Model\Entity\Article', 'TestCase/Model/Entity/ArticleTest.php'],
            ['table', 'App\Model\Table\PostsTable', 'TestCase/Model/Table/PostsTableTest.php'],
            ['helper', 'App\View\Helper\FormHelper', 'TestCase/View/Helper/FormHelperTest.php'],
            ['controller', 'App\Controller\PostsController', 'TestCase/Controller/PostsControllerTest.php'],
            ['behavior', 'App\Model\Behavior\TreeBehavior', 'TestCase/Model/Behavior/TreeBehaviorTest.php'],
            [
                'component',
                'App\Controller\Component\AuthComponent',
                'TestCase/Controller/Component/AuthComponentTest.php'
            ],
            ['Shell', 'App\Shell\ExampleShell', 'TestCase/Shell/ExampleShellTest.php'],
            ['shell', 'App\Shell\ExampleShell', 'TestCase/Shell/ExampleShellTest.php'],
        ];
    }

    /**
     * Test filename generation for each type + plugins
     *
     * @dataProvider caseFileNameProvider
     * @return void
     */
    public function testTestCaseFileName($type, $class, $expected)
    {
        $result = $this->Task->testCaseFileName($type, $class);
        $this->assertPathEquals(ROOT . DS . 'tests' . DS . $expected, $result);
    }

    /**
     * Test filename generation for plugins.
     *
     * @return void
     */
    public function testTestCaseFileNamePlugin()
    {
        $this->Task->path = DS . 'my/path/tests/';

        $this->loadPlugins([
            'TestTest' => ['path' => APP . 'Plugin' . DS . 'TestTest' . DS]
        ]);
        $this->Task->plugin = 'TestTest';
        $class = 'TestBake\Model\Entity\Post';
        $result = $this->Task->testCaseFileName('entity', $class);

        $expected = APP . 'Plugin/TestTest/tests/TestCase/Model/Entity/PostTest.php';
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Data provider for mapType() tests.
     *
     * @return array
     */
    public static function mapTypeProvider()
    {
        return [
            ['Controller', 'Controller'],
            ['Component', 'Controller\Component'],
            ['Table', 'Model\Table'],
            ['Entity', 'Model\Entity'],
            ['Behavior', 'Model\Behavior'],
            ['Helper', 'View\Helper'],
            ['ShellHelper', 'Shell\Helper'],
        ];
    }

    /**
     * Test that mapType returns the correct package names.
     *
     * @dataProvider mapTypeProvider
     * @return void
     */
    public function testMapType($original, $expected)
    {
        $this->assertEquals($expected, $this->Task->mapType($original));
    }
}
