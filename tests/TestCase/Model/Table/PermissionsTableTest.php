<?php
declare(strict_types=1);

namespace Rbac\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Rbac\Model\Table\PermissionsTable;

/**
 * PermissionsTable Test Case
 *
 * Tests the PermissionsTable model including associations, validation, and constraints
 */
class PermissionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Rbac\Model\Table\PermissionsTable
     */
    protected $Permissions;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Rbac.Permissions',
        'plugin.Rbac.Roles',
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
        $config = TableRegistry::getTableLocator()->exists('Rbac.Permissions') ? [] : ['className' => PermissionsTable::class];
        $this->Permissions = TableRegistry::getTableLocator()->get('Rbac.Permissions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Permissions);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->assertSame('permissions', $this->Permissions->getTable());
        $this->assertSame('name', $this->Permissions->getDisplayField());
        $this->assertSame('id', $this->Permissions->getPrimaryKey());
        $this->assertTrue($this->Permissions->hasBehavior('Timestamp'));
    }

    /**
     * Test belongsToMany Roles association
     *
     * Verifies that permissions can load their associated roles via eager loading
     *
     * @return void
     */
    public function testBelongsToManyRoles(): void
    {
        // Load rbac.roles.view permission (id=1) with roles
        $permission = $this->Permissions->get(1, contain: ['Roles']);

        $this->assertNotEmpty($permission->roles);
        $this->assertCount(1, $permission->roles); // Only admin role has this permission

        // Verify role name
        $this->assertSame('admin', $permission->roles[0]->name);
    }

    /**
     * Test validation requires name
     *
     * @return void
     */
    public function testValidationRequiresName(): void
    {
        $permission = $this->Permissions->newEntity([
            'description' => 'Test permission without name',
            'is_active' => true,
        ]);

        $this->assertFalse($this->Permissions->save($permission));
        $this->assertNotEmpty($permission->getErrors());
        $this->assertArrayHasKey('name', $permission->getErrors());
    }

    /**
     * Test validation name format with valid dotted format
     *
     * @return void
     */
    public function testValidationNameFormatValid(): void
    {
        $permission = $this->Permissions->newEntity([
            'name' => 'posts.create',
            'description' => 'Create posts',
            'is_active' => true,
        ]);

        $result = $this->Permissions->save($permission);
        $this->assertNotFalse($result);
        $this->assertEmpty($permission->getErrors());
    }

    /**
     * Test validation name format with invalid characters
     *
     * @return void
     */
    public function testValidationNameFormatInvalid(): void
    {
        // Test with invalid characters (space, hyphen, underscore)
        $invalidNames = [
            'posts create',     // space not allowed
            'posts-create',     // hyphen not allowed
            'posts_create',     // underscore not allowed
            'posts@create',     // special char not allowed
        ];

        foreach ($invalidNames as $invalidName) {
            $permission = $this->Permissions->newEntity([
                'name' => $invalidName,
                'description' => 'Test invalid name',
                'is_active' => true,
            ]);

            $this->assertFalse($this->Permissions->save($permission), "Name '{$invalidName}' should be invalid");
            $this->assertNotEmpty($permission->getErrors());
            $this->assertArrayHasKey('name', $permission->getErrors());
        }
    }

    /**
     * Test validation unique name constraint
     *
     * @return void
     */
    public function testValidationUniqueNameConstraint(): void
    {
        // Try to create a permission with duplicate name 'rbac.roles.view' (exists in fixture)
        $permission = $this->Permissions->newEntity([
            'name' => 'rbac.roles.view', // Duplicate name
            'description' => 'Duplicate permission',
            'is_active' => true,
        ]);

        $result = $this->Permissions->save($permission);
        $this->assertFalse($result);
        $this->assertNotEmpty($permission->getErrors());
        $this->assertArrayHasKey('name', $permission->getErrors());
    }

    /**
     * Test permission name pattern constant
     *
     * @return void
     */
    public function testNamePatternConstant(): void
    {
        $this->assertSame('/^[a-zA-Z0-9.]+$/', PermissionsTable::NAME_PATTERN);
    }

    /**
     * Test that is_active flag works correctly
     *
     * @return void
     */
    public function testActivePermissionFlag(): void
    {
        $permission = $this->Permissions->get(1);
        $this->assertTrue($permission->is_active);
    }
}
