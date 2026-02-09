<?php
declare(strict_types=1);

namespace Rbac\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Rbac\Model\Table\UsersRolesTable;

/**
 * UsersRolesTable Test Case
 *
 * Tests the UsersRolesTable pivot model for user-role associations
 */
class UsersRolesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Rbac\Model\Table\UsersRolesTable
     */
    protected $UsersRoles;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Rbac.UsersRoles',
        'plugin.Rbac.Roles',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Rbac.UsersRoles') ? [] : ['className' => UsersRolesTable::class];
        $this->UsersRoles = TableRegistry::getTableLocator()->get('Rbac.UsersRoles', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->UsersRoles);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->assertSame('users_roles', $this->UsersRoles->getTable());
        $this->assertTrue($this->UsersRoles->hasBehavior('Timestamp'));
    }

    /**
     * Test belongsTo Roles association
     *
     * Verifies that user-role assignments can load the associated role
     *
     * @return void
     */
    public function testBelongsToRoles(): void
    {
        // Load first user-role assignment with role data
        $userRole = $this->UsersRoles->find()
            ->contain(['Roles'])
            ->where(['user_id' => '550e8400-e29b-41d4-a716-446655440001'])
            ->first();

        $this->assertNotNull($userRole);
        $this->assertNotEmpty($userRole->role);
        $this->assertSame('admin', $userRole->role->name);
        $this->assertSame(2, $userRole->role->id);
    }

    /**
     * Test that associations to both Users and Roles exist
     *
     * @return void
     */
    public function testAssociationsExist(): void
    {
        $this->assertTrue($this->UsersRoles->associations()->has('Users'));
        $this->assertTrue($this->UsersRoles->associations()->has('Roles'));

        $usersAssoc = $this->UsersRoles->getAssociation('Users');
        $rolesAssoc = $this->UsersRoles->getAssociation('Roles');

        $this->assertSame('CakeDC/Users.Users', $usersAssoc->getClassName());
        $this->assertSame('Rbac.Roles', $rolesAssoc->getClassName());
    }

    /**
     * Test validation requires user_id
     *
     * @return void
     */
    public function testValidationRequiresUserId(): void
    {
        $userRole = $this->UsersRoles->newEntity([
            'role_id' => 3,
        ]);

        $this->assertFalse($this->UsersRoles->save($userRole));
        $this->assertNotEmpty($userRole->getErrors());
        $this->assertArrayHasKey('user_id', $userRole->getErrors());
    }

    /**
     * Test validation requires role_id
     *
     * @return void
     */
    public function testValidationRequiresRoleId(): void
    {
        $userRole = $this->UsersRoles->newEntity([
            'user_id' => '550e8400-e29b-41d4-a716-446655440003',
        ]);

        $this->assertFalse($this->UsersRoles->save($userRole));
        $this->assertNotEmpty($userRole->getErrors());
        $this->assertArrayHasKey('role_id', $userRole->getErrors());
    }

    /**
     * Test that valid user-role assignment can be saved
     *
     * @return void
     */
    public function testSaveValidUserRole(): void
    {
        $userRole = $this->UsersRoles->newEntity([
            'user_id' => '550e8400-e29b-41d4-a716-446655440003',
            'role_id' => 3,
        ]);

        $result = $this->UsersRoles->save($userRole);
        $this->assertNotFalse($result);
        $this->assertEmpty($userRole->getErrors());
    }
}
