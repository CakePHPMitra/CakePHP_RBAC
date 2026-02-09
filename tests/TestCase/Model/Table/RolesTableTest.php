<?php
declare(strict_types=1);

namespace Rbac\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Rbac\Model\Table\RolesTable;

/**
 * RolesTable Test Case
 *
 * Tests the RolesTable model including associations, validation, and constraints
 */
class RolesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Rbac\Model\Table\RolesTable
     */
    protected $Roles;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Rbac.Roles',
        'plugin.Rbac.Permissions',
        'plugin.Rbac.RolesPermissions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Rbac.Roles') ? [] : ['className' => RolesTable::class];
        $this->Roles = TableRegistry::getTableLocator()->get('Rbac.Roles', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Roles);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->assertSame('roles', $this->Roles->getTable());
        $this->assertSame('name', $this->Roles->getDisplayField());
        $this->assertSame('id', $this->Roles->getPrimaryKey());
        $this->assertTrue($this->Roles->hasBehavior('Timestamp'));
    }

    /**
     * Test belongsToMany Permissions association
     *
     * Verifies that roles can load their associated permissions via eager loading
     *
     * @return void
     */
    public function testBelongsToManyPermissions(): void
    {
        // Load admin role (id=2) with permissions
        $role = $this->Roles->get(2, contain: ['Permissions']);

        $this->assertNotEmpty($role->permissions);
        $this->assertCount(2, $role->permissions); // Admin has 2 RBAC permissions

        // Verify permission names
        $permissionNames = array_column($role->permissions, 'name');
        $this->assertContains('rbac.roles.view', $permissionNames);
        $this->assertContains('rbac.roles.edit', $permissionNames);
    }

    /**
     * Test validation requires name
     *
     * @return void
     */
    public function testValidationRequiresName(): void
    {
        $role = $this->Roles->newEntity([
            'description' => 'Test role without name',
            'is_system' => false,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->assertFalse($this->Roles->save($role));
        $this->assertNotEmpty($role->getErrors());
        $this->assertArrayHasKey('name', $role->getErrors());
    }

    /**
     * Test validation unique name constraint
     *
     * @return void
     */
    public function testValidationUniqueNameConstraint(): void
    {
        // Try to create a role with duplicate name 'admin' (exists in fixture)
        $role = $this->Roles->newEntity([
            'name' => 'admin', // Duplicate name
            'description' => 'Duplicate admin role',
            'is_system' => false,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $result = $this->Roles->save($role);
        $this->assertFalse($result);
        $this->assertNotEmpty($role->getErrors());
        $this->assertArrayHasKey('name', $role->getErrors());
    }

    /**
     * Test system role flag is set correctly
     *
     * Verifies that the superadmin role from fixtures has is_system=true
     *
     * @return void
     */
    public function testSystemRoleFlagSet(): void
    {
        $superadmin = $this->Roles->get(1);

        $this->assertTrue($superadmin->is_system);
        $this->assertSame('superadmin', $superadmin->name);
        $this->assertSame(1, $superadmin->sort_order);
    }

    /**
     * Test that default role flag is set correctly
     *
     * Verifies that the user role is marked as default
     *
     * @return void
     */
    public function testDefaultRoleFlagSet(): void
    {
        $userRole = $this->Roles->get(3);

        $this->assertTrue($userRole->is_default);
        $this->assertSame('user', $userRole->name);
    }

    /**
     * Test that active role flag works correctly
     *
     * @return void
     */
    public function testActiveRoleFlag(): void
    {
        $role = $this->Roles->get(1);
        $this->assertTrue($role->is_active);
    }

    /**
     * Test belongsTo ParentRoles association exists
     *
     * @return void
     */
    public function testBelongsToParentRolesAssociation(): void
    {
        $this->assertTrue($this->Roles->associations()->has('ParentRoles'));
        $association = $this->Roles->getAssociation('ParentRoles');
        $this->assertSame('Rbac.Roles', $association->getClassName());
    }

    /**
     * Test hasMany ChildRoles association exists
     *
     * @return void
     */
    public function testHasManyChildRolesAssociation(): void
    {
        $this->assertTrue($this->Roles->associations()->has('ChildRoles'));
        $association = $this->Roles->getAssociation('ChildRoles');
        $this->assertSame('Rbac.Roles', $association->getClassName());
    }
}
